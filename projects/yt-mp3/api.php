<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action       = $_GET['action'] ?? '';
$downloadsDir = __DIR__ . DIRECTORY_SEPARATOR . 'downloads' . DIRECTORY_SEPARATOR;
$binDir       = __DIR__ . DIRECTORY_SEPARATOR . 'bin'       . DIRECTORY_SEPARATOR;
$ytdlpPath    = $binDir . 'yt-dlp.exe';

if (!is_dir($downloadsDir)) mkdir($downloadsDir, 0777, true);
if (!is_dir($binDir))       mkdir($binDir,       0777, true);

function sanitizeFilename($s) {
    $s = preg_replace('/[\\\\\/:*?"<>|]/', '', $s);
    $s = trim($s);
    return $s === '' ? 'sin_nombre' : mb_substr($s, 0, 80);
}

// ── CHECK BIN ──────────────────────────────────────────────────
if ($action === 'check') {
    echo json_encode([
        'ytdlp'  => file_exists($ytdlpPath),
        'ffmpeg' => file_exists($binDir . 'ffmpeg.exe'),
    ]);
    exit;
}

// ── PREVIEW / INFO ──────────────────────────────────────────────
if ($action === 'info') {
    $url = trim($_GET['url'] ?? $_POST['url'] ?? '');
    if (!$url) { echo json_encode(['error' => 'URL requerida']); exit; }
    if (!file_exists($ytdlpPath)) { echo json_encode(['error' => 'yt-dlp.exe no encontrado en bin/']); exit; }

    $descriptors = [['pipe','r'], ['pipe','w'], ['pipe','w']];
    $proc = proc_open([$ytdlpPath, '--dump-single-json', '--no-playlist', '--no-warnings', $url], $descriptors, $pipes);
    if (!is_resource($proc)) { echo json_encode(['error' => 'No se pudo iniciar yt-dlp']); exit; }

    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($proc);

    $data = json_decode($out, true);
    if (!$data) { echo json_encode(['error' => 'No se pudo leer información del vídeo. ' . trim($err)]); exit; }

    $rawTitle = $data['title'] ?? '';
    $uploader = $data['uploader'] ?? $data['channel'] ?? '';

    // Intenta separar "Artista - Título" si el título ya viene así
    $artist = $uploader;
    $title  = $rawTitle;
    if (preg_match('/^\s*(.+?)\s*[-–—]\s*(.+?)\s*$/u', $rawTitle, $m)) {
        $artist = $m[1];
        $title  = $m[2];
    }

    echo json_encode([
        'ok'        => true,
        'raw_title' => $rawTitle,
        'uploader'  => $uploader,
        'artist'    => $artist,
        'title'     => $title,
        'thumbnail' => $data['thumbnail'] ?? '',
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
    if (!file_exists($ytdlpPath))            { echo json_encode(['error' => 'yt-dlp.exe no encontrado en bin/']); exit; }
    if (!file_exists($binDir.'ffmpeg.exe'))  { echo json_encode(['error' => 'ffmpeg.exe no encontrado en bin/']); exit; }

    $artist = trim($_POST['artist'] ?? '');
    $title  = trim($_POST['title']  ?? '');
    if ($artist === '') $artist = 'Desconocido';
    if ($title  === '') $title  = 'Video';

    $jobId    = bin2hex(random_bytes(8));
    $logFile  = $downloadsDir . $jobId . '.log';
    $tempFile = $downloadsDir . 'tmp_' . $jobId . '.mp3';
    $tempTpl  = $downloadsDir . 'tmp_' . $jobId . '.%(ext)s';

    $safeArtist = sanitizeFilename($artist);
    $safeTitle  = sanitizeFilename($title);
    $finalFile  = $downloadsDir . $jobId . '_' . $safeArtist . ' - ' . $safeTitle . '.mp3';

    // Paso 1: yt-dlp descarga y extrae el audio a un nombre temporal fijo
    $ytArgs = [
        $ytdlpPath,
        '--extract-audio',
        '--audio-format',    'mp3',
        '--audio-quality',   '192K',
        '--ffmpeg-location', rtrim($binDir, DIRECTORY_SEPARATOR),
        '--newline',
        '--no-playlist',
        '-o',                $tempTpl,
        $url,
    ];

    // Paso 2: ffmpeg copia el audio (sin recodificar) y escribe los tags exactos
    $ffArgs = [
        $binDir . 'ffmpeg.exe',
        '-y',
        '-i',        $tempFile,
        '-c',        'copy',
        '-metadata', 'title='  . $title,
        '-metadata', 'artist=' . $artist,
        '-metadata', 'album='  . $artist,
        $finalFile,
    ];

    $quote = fn($a) => '"' . str_replace('"', '""', $a) . '"';
    $ytCmd = implode(' ', array_map($quote, $ytArgs));
    $ffCmd = implode(' ', array_map($quote, $ffArgs));

    // Encadenado: descarga → etiqueta y renombra → borra temporal
    $full = $ytCmd . ' >> "' . $logFile . '" 2>&1'
        . ' && ' . $ffCmd . ' >> "' . $logFile . '" 2>&1'
        . ' && del "' . $tempFile . '"';

    $fullEscaped = str_replace('%', '%%', $full);

    $batFile = $downloadsDir . $jobId . '.bat';
    file_put_contents($batFile, "@echo off\r\nchcp 65001 > nul\r\n" . $fullEscaped . "\r\n");

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

        // Destination → título (nombre temporal, solo usamos esto para mostrar progreso, no como fuente del título final)
        if (preg_match('/Destination:.+[\\/\\\\]tmp_[a-f0-9]+\.(webm|mp4|m4a|opus|ogg|mp3)$/i', $line, $m))
            $title = $title ?: 'Procesando…';

        // Percentage
        if (preg_match('/\[download\]\s+(\d+(?:\.\d+)?)%/', $line, $m) && (float)$m[1] > $progress)
            $progress = (int)(float)$m[1];

        if (stripos($line, '[ExtractAudio]') !== false && $progress < 90)
            $progress = 90;

        // yt-dlp ya terminó de extraer el audio, pero ffmpeg aún tiene que
        // etiquetar y renombrar al archivo final. NO marcamos $done aquí:
        // solo avanzamos el progreso visual. $done depende exclusivamente
        // de que el archivo final con metadatos ya exista (ver $mp3File abajo).
        if (stripos($line, 'Deleting original file') !== false || stripos($line, 'has already been downloaded') !== false)
            $progress = max($progress, 97);

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