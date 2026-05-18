<?php
require_once __DIR__ . '/assets/functions.php';

if (isset($_GET['action'])) {
    handle_api();
}

$items = load_items();
$total = count($items);
$done  = count(array_filter($items, fn($i) => $i['done']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Wishlist</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="assets/style.css" />
</head>
<body>

<header>
  <div class="logo">Wish<span>list</span></div>
  <nav><?= $total ?> item<?= $total !== 1 ? 's' : '' ?></nav>
</header>

<main>

  <p class="eyebrow">Mi lista</p>
  <h1>Todo lo que <em>quiero</em>.</h1>
  <p class="description">Anota enlaces, notas y etiquetas. Marca lo que ya conseguiste.</p>

  <div class="input-block" id="form-block">
    <input type="url" id="inp-url" placeholder="https://… (opcional)" autocomplete="off" />
    <textarea id="inp-text" placeholder="Descripción o nota…" rows="3"></textarea>
    <div class="input-block-footer">
      <input class="tag-field" type="text" id="inp-tag" placeholder="Etiqueta: moda, tech, hogar…" autocomplete="off" />
      <button id="go-btn" onclick="addItem()">Añadir</button>
    </div>
  </div>

  <div class="wl-filters">
    <button class="wl-filter active" onclick="setFilter('all', this)">Todo</button>
    <button class="wl-filter"        onclick="setFilter('pending', this)">Pendiente</button>
    <button class="wl-filter"        onclick="setFilter('done', this)">Conseguido</button>
  </div>

  <div id="counter">
    <?= $total ?> item<?= $total !== 1 ? 's' : '' ?> · <?= $done ?> conseguido<?= $done !== 1 ? 's' : '' ?>
  </div>

  <div id="list">
    <?php if (empty($items)): ?>
      <div class="wl-empty">Lista vacía — añade tu primer deseo</div>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="wl-item<?= $it['done'] ? ' done' : '' ?>" data-id="<?= htmlspecialchars($it['id']) ?>">
          <div class="wl-check<?= $it['done'] ? ' checked' : '' ?>"
               onclick="toggleItem('<?= htmlspecialchars($it['id']) ?>')"
               title="<?= $it['done'] ? 'Marcar pendiente' : 'Marcar conseguido' ?>"></div>
          <div class="wl-item-body">
            <?php if ($it['url']): ?>
              <a class="wl-item-url"
                 href="<?= htmlspecialchars($it['url']) ?>"
                 target="_blank" rel="noopener">
                <?= htmlspecialchars(preg_replace('#^(https?://)?(www\.)?#', '', parse_url($it['url'], PHP_URL_HOST) ?: $it['url'])) ?>
              </a>
            <?php endif; ?>
            <?php if ($it['text']): ?>
              <div class="wl-item-text"><?= htmlspecialchars($it['text']) ?></div>
            <?php endif; ?>
            <?php if ($it['tag']): ?>
              <span class="wl-item-tag"><?= htmlspecialchars($it['tag']) ?></span>
            <?php endif; ?>
            <?php if (!empty($it['created_at'])): ?>
              <span class="wl-item-date">
                <?= date('d M Y', strtotime($it['created_at'])) ?>
              </span>
            <?php endif; ?>
          </div>
          <button class="wl-item-del"
                  onclick="deleteItem('<?= htmlspecialchars($it['id']) ?>')"
                  title="Eliminar">×</button>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <hr class="divider" />

  <div class="wl-foot">
    <span>Los datos se guardan en el servidor</span>
    <button onclick="clearDone()">Limpiar conseguidos</button>
  </div>

</main>

<footer>
  <span>Wishlist</span>
  <span><?= date('Y') ?></span>
</footer>

<script src="assets/functions.js"></script>
</body>
</html>
