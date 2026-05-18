<?php
header('Content-Type: application/json');

// ── CONFIG ─────────────────────────────────────────────────────────────────
// Path to python executable — adjust if needed
$python = 'C:\\Users\\JJ\\AppData\\Local\\Programs\\Python\\Launcher\\py.exe';

// Where uploaded files are stored temporarily
$uploadDir = __DIR__ . '/uploads/';

// Where demucs outputs its results
$outputDir = __DIR__ . '/output/';

// Public URL base (relative to htdocs)
$publicBase = '/stems/';
// ───────────────────────────────────────────────────────────────────────────

function jsonError($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// Create dirs if they don't exist
foreach ([$uploadDir, $outputDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Validate upload
if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    jsonError('No file uploaded or upload error.');
}

$allowed = ['mp3','wav','flac','ogg','m4a'];
$ext = strtolower(pathinfo($_FILES['audio']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    jsonError('Invalid file type: ' . $ext);
}

// Max 100MB
if ($_FILES['audio']['size'] > 100 * 1024 * 1024) {
    jsonError('File too large (max 100MB).');
}

// Save uploaded file with unique name
$uid      = uniqid('stem_', true);
$inputFile = $uploadDir . $uid . '.' . $ext;
if (!move_uploaded_file($_FILES['audio']['tmp_name'], $inputFile)) {
    jsonError('Could not save uploaded file.');
}

// Run Demucs
// --two-stems=vocals  → produces vocals.wav and no_vocals.wav
// --out               → output directory
// --mp3               → output as mp3 (lighter); remove this line if you want WAV
$inputEscaped  = escapeshellarg($inputFile);
$outputEscaped = escapeshellarg($outputDir);

$cmd = "$python -m demucs --two-stems=vocals --out $outputEscaped $inputEscaped 2>&1";
exec($cmd, $cmdOutput, $returnCode);

if ($returnCode !== 0) {
    // Clean up input
    @unlink($inputFile);
    $errorDetail = implode("\n", $cmdOutput);
    jsonError('Demucs failed: ' . $errorDetail);
}

// Demucs saves to: output/htdemucs/<filename_without_ext>/vocals.wav
// Find the generated folder
$baseName   = pathinfo($inputFile, PATHINFO_FILENAME); // e.g. stem_abc123
$modelName  = 'htdemucs'; // default model
$stemFolder = $outputDir . $modelName . '/' . $baseName . '/';

$vocalsFile       = $stemFolder . 'vocals.wav';
$instrumentalFile = $stemFolder . 'no_vocals.wav';

if (!file_exists($vocalsFile) || !file_exists($instrumentalFile)) {
    @unlink($inputFile);
    jsonError('Stems not found after processing. Output: ' . implode(' | ', $cmdOutput));
}

// Build public download URLs
$vocalsUrl       = $publicBase . 'output/' . $modelName . '/' . $baseName . '/vocals.wav';
$instrumentalUrl = $publicBase . 'output/' . $modelName . '/' . $baseName . '/no_vocals.wav';

// Clean up the uploaded source file
@unlink($inputFile);

echo json_encode([
    'success'      => true,
    'vocals'       => $vocalsUrl,
    'instrumental' => $instrumentalUrl,
]);
?>