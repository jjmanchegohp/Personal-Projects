<?php

define('DATA_FILE', __DIR__ . '/wishlist.json');

function load_items(): array {
    if (!file_exists(DATA_FILE)) return [];
    $json = file_get_contents(DATA_FILE);
    return json_decode($json, true) ?? [];
}

function save_items(array $items): void {
    file_put_contents(DATA_FILE, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function add_item(string $url, string $text, string $tag): array {
    $items = load_items();
    $item = [
        'id'   => uniqid('wl_', true),
        'url'  => trim($url),
        'text' => trim($text),
        'tag'  => trim($tag),
        'done' => false,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    array_unshift($items, $item);
    save_items($items);
    return $item;
}

function toggle_item(string $id): ?array {
    $items = load_items();
    foreach ($items as &$item) {
        if ($item['id'] === $id) {
            $item['done'] = !$item['done'];
            save_items($items);
            return $item;
        }
    }
    return null;
}

function delete_item(string $id): bool {
    $items = load_items();
    $filtered = array_filter($items, fn($i) => $i['id'] !== $id);
    if (count($filtered) === count($items)) return false;
    save_items(array_values($filtered));
    return true;
}

function clear_done(): int {
    $items = load_items();
    $before = count($items);
    $items = array_values(array_filter($items, fn($i) => !$i['done']));
    save_items($items);
    return $before - count($items);
}

function handle_api(): void {
    header('Content-Type: application/json; charset=utf-8');
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    try {
        switch (true) {

            case $method === 'GET' && $action === 'list':
                echo json_encode(['ok' => true, 'items' => load_items()]);
                break;

            case $method === 'POST' && $action === 'add':
                $body = json_decode(file_get_contents('php://input'), true) ?? [];
                if (empty($body['text']) && empty($body['url'])) {
                    http_response_code(400);
                    echo json_encode(['ok' => false, 'error' => 'text o url requerido']);
                    break;
                }
                $item = add_item($body['url'] ?? '', $body['text'] ?? '', $body['tag'] ?? '');
                echo json_encode(['ok' => true, 'item' => $item]);
                break;

            case $method === 'POST' && $action === 'toggle':
                $body = json_decode(file_get_contents('php://input'), true) ?? [];
                $item = toggle_item($body['id'] ?? '');
                echo json_encode($item ? ['ok' => true, 'item' => $item] : ['ok' => false, 'error' => 'not found']);
                break;

            case $method === 'POST' && $action === 'delete':
                $body = json_decode(file_get_contents('php://input'), true) ?? [];
                $ok = delete_item($body['id'] ?? '');
                echo json_encode(['ok' => $ok]);
                break;

            case $method === 'POST' && $action === 'clear_done':
                $n = clear_done();
                echo json_encode(['ok' => true, 'removed' => $n]);
                break;

            default:
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'acción no encontrada']);
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
