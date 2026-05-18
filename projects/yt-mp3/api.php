<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action       = $_GET['action'] ?? '';
$downloadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR;
$binDir       = __DIR__ . DIRECTORY_SEPARATOR . 'bin'       . DIRECTORY_SEPARATOR;
$ytdlpPath    = $binDir . 'yt-dlp.exe';

if (!is_dir($downloadsDir)) mkdir($downloadsDir, 0777, true);
if (!is_dir($binDir))       mkdir($binDir,       0777, true);

// ── CHECK BIN ──────────────────────────────────────────────────
if ($action === 'check') {
    echo json_encode([
        'ytdlp'  => file_exists($ytdlpPath),
        'ffmpeg' => file_exists($binDir . 'ffmpeg.exe'),
    ]);
    exit;
}

// ── DOWNLOAD YT-DLP ────────────────────────────────────────────
if ($action === 'install') {
    $remoteUrl = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe';
    $data = false;

    if (function_exists('curl_init')) {
        $ch = curl_init($remoteUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 120,
        ]);
        $data = curl_exec($ch);
        $cerr = curl_error($ch);
        curl_close($ch);
        if (!$data) { echo json_encode(['ok'=>false,'error'=>'curl: '.$cerr]); exit; }
    } else {
        $ctx  = stream_context_create(['ssl'=>['verify_peer'=>false,'verify_peer_name'=>false]]);
        $data = @file_get_contents($remoteUrl, false, $ctx);
        if (!$data) { echo json_encode(['ok'=>false,'error'=>'No se pudo descargar. Descárgalo manualmente.']); exit; }
    }

    file_put_contents($ytdlpPath, $data);
    echo json_encode(['ok' => true]);
    exit;
}

// ── START DOWNLOAD ─────────────────────────────────────────────
if ($action === 'start') {
    $url = trim($_POST['url'] ?? '');
    if (!$url) { echo json_encode(['error' => 'URL requerida']); exit; }
    if (!file_exists($ytdlpPath)) { echo json_encode(['error' => 'yt-dlp.exe no encontrado en bin/']); exit; }

    $jobId   = bin2hex(random_bytes(8));
    $logFile = $downloadsDir . $jobId . '.log';
    $outTpl  = $downloadsDir . $jobId . '_%(title)s.%(ext)s';

    // Use long-form flags to avoid Windows misinterpreting short flags like -x
    // Pass each argument as a separate array element — no shell escaping needed
    $args = [
        $ytdlpPath,
        '--extract-audio',
        '--audio-format',    'mp3',
        '--audio-quality',   '192K',
        '--ffmpeg-location', rtrim($binDir, DIRECTORY_SEPARATOR),
        '--newline',
        '--no-playlist',
        '-o',                $outTpl,
        $url,
    ];

    // Build a safe quoted command string for cmd.exe
    $quoted = array_map(fn($a) => '"' . str_replace('"', '""', $a) . '"', $args);
    $cmd    = implode(' ', $quoted) . ' > "' . $logFile . '" 2>&1';

    // Write .bat with explicit cmd header and UTF-8 safe encoding
    $batFile = $downloadsDir . $jobId . '.bat';
    file_put_contents($batFile, "@echo off\r\nchcp 65001 > nul\r\n" . $cmd . "\r\n");

    // Launch via cmd.exe /C — do NOT use start /B as it drops stdout redirect
    $descriptors = [['pipe','r'], ['pipe','w'], ['pipe','w']];
    $proc = proc_open('cmd.exe /C "' . $batFile . '"', $descriptors, $pipes);
    if (is_resource($proc)) {
        array_map('fclose', $pipes);
        proc_close($proc);
    }

    echo json_encode(['job_id' => $jobId]);
    exit;
}

// ── POLL PROGRESS ──────────────────────────────────────────────
if ($action === 'progress') {
    $jobId    = preg_replace('/[^a-f0-9]/', '', $_GET['job'] ?? '');
    $logFile  = $downloadsDir . $jobId . '.log';
    $mp3Files = glob($downloadsDir . $jobId . '_*.mp3') ?: [];
    $mp3File  = $mp3Files ? basename($mp3Files[0]) : '';

    if (!file_exists($logFile) && !$mp3File) {
        echo json_encode(['status'=>'waiting','progress'=>0,'title'=>'','file'=>'','error'=>'']);
        exit;
    }

    $log      = file_exists($logFile) ? file_get_contents($logFile) : '';
    $lines    = preg_split('/\r?\n/', $log);
    $progress = 0;
    $title    = '';
    $done     = (bool)$mp3File;
    $error    = '';

    foreach ($lines as $raw) {
        $line = trim($raw);
        if (!$line) continue;

        // Destination → title
        if (preg_match('/Destination:.+[\\/\\\\][a-f0-9]+_(.+?)\.(webm|mp4|m4a|opus|ogg|mp3)$/i', $line, $m))
            $title = $m[1];

        // ExtractAudio → title
        if (preg_match('/\[ExtractAudio\].+[\\/\\\\][a-f0-9]+_(.+?)\.mp3/i', $line, $m))
            $title = $m[1];

        // Percentage
        if (preg_match('/\[download\]\s+(\d+(?:\.\d+)?)%/', $line, $m) && (float)$m[1] > $progress)
            $progress = (int)(float)$m[1];

        if (stripos($line, '[ExtractAudio]') !== false && $progress < 95)
            $progress = 95;

        if (stripos($line, 'Deleting original file') !== false || stripos($line, 'has already been downloaded') !== false)
            $done = true;

        if (preg_match('/^ERROR\s*:/i', $line))
            $error = $line;
    }

    if ($mp3File) { $done = true; $progress = 100; }

    echo json_encode([
        'status'   => $error ? 'error' : ($done ? 'done' : 'running'),
        'progress' => $progress,
        'title'    => $title,
        'file'     => $mp3File,
        'error'    => $error,
        'log_tail' => implode(' | ', array_filter(array_map('trim', array_slice($lines, -4)))),
    ]);
    exit;
}

// ── SERVE FILE ─────────────────────────────────────────────────
if ($action === 'get') {
    $jobId  = preg_replace('/[^a-f0-9]/', '', $_GET['job'] ?? '');
    $files  = glob($downloadsDir . $jobId . '_*.mp3') ?: [];
    if (!$files) { http_response_code(404); echo json_encode(['error'=>'Archivo no encontrado']); exit; }

    $file         = $files[0];
    $friendlyName = preg_replace('/^[a-f0-9]{16}_/', '', basename($file));

    header('Content-Type: audio/mpeg');
    header('Content-Disposition: attachment; filename="' . rawurlencode($friendlyName) . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// ── LOG RAW (debug) ────────────────────────────────────────────
if ($action === 'log') {
    $jobId = preg_replace('/[^a-f0-9]/', '', $_GET['job'] ?? '');
    $f     = $downloadsDir . $jobId . '.log';
    header('Content-Type: text/plain; charset=utf-8');
    echo file_exists($f) ? file_get_contents($f) : '(sin log todavía)';
    exit;
}

echo json_encode(['error' => 'Acción desconocida']);