<?php
header('Content-Type: text/html; charset=utf-8');
$binDir    = __DIR__ . '/bin/';
$ytdlp     = $binDir . 'yt-dlp.exe';
$ffmpeg    = $binDir . 'ffmpeg.exe';
$ffprobe   = $binDir . 'ffprobe.exe';

function runCmd($cmd) {
    $out = []; $rc = -1;
    exec($cmd . ' 2>&1', $out, $rc);
    return ['output' => implode("\n", $out), 'code' => $rc];
}

$ytVer  = file_exists($ytdlp)  ? runCmd('"'.$ytdlp.'" --version') : null;
$ffVer  = file_exists($ffmpeg) ? runCmd('"'.$ffmpeg.'" -version') : null;
$ffpVer = file_exists($ffprobe)? runCmd('"'.$ffprobe.'" -version'): null;

// Test: can yt-dlp see ffmpeg?
$ffTest = file_exists($ytdlp) ? runCmd('"'.$ytdlp.'" --ffmpeg-location "'.rtrim($binDir,'/').'" --version') : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Diagnóstico</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  body { font-family: Inter, sans-serif; font-weight: 300; font-size: 14px; max-width: 700px; margin: 60px auto; padding: 0 2rem; color: #0d0d0d; }
  h1 { font-size: 1.4rem; font-weight: 500; letter-spacing: -.01em; margin-bottom: 2rem; }
  h2 { font-size: 11px; letter-spacing: .18em; text-transform: uppercase; color: #999; margin: 2rem 0 .6rem; }
  .row { display: flex; gap: 1rem; align-items: flex-start; padding: .8rem 0; border-bottom: 1px solid #eee; }
  .label { min-width: 160px; color: #999; font-size: 13px; }
  .val { font-size: 13px; }
  .ok  { color: #1a7a4a; font-weight: 500; }
  .err { color: #c0392b; font-weight: 500; }
  pre { background: #f4f4f2; padding: 1rem; font-size: 11px; line-height: 1.6; overflow-x: auto; white-space: pre-wrap; margin-top: .4rem; }
  .fix { background: #fffbf0; border: 1px solid #f0e0a0; padding: .8rem 1rem; font-size: 12px; line-height: 1.7; margin-top: .5rem; }
  .fix a { color: #0d0d0d; }
  .fix code { background: #eee; padding: 1px 4px; font-size: 11px; }
</style>
</head>
<body>
<h1>Diagnóstico — YT MP3</h1>

<h2>Archivos en bin/</h2>
<?php
$files = ['yt-dlp.exe' => $ytdlp, 'ffmpeg.exe' => $ffmpeg, 'ffprobe.exe' => $ffprobe];
foreach ($files as $name => $path): ?>
<div class="row">
  <span class="label"><?= $name ?></span>
  <?php if (file_exists($path)): ?>
    <span class="val ok">Encontrado &nbsp;(<?= round(filesize($path)/1024/1024, 1) ?> MB)</span>
  <?php else: ?>
    <span class="val err">No encontrado</span>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<h2>Versiones</h2>
<div class="row">
  <span class="label">yt-dlp</span>
  <div>
    <?php if ($ytVer): ?>
      <span class="val <?= $ytVer['code']===0 ? 'ok' : 'err' ?>"><?= htmlspecialchars(trim($ytVer['output'])) ?></span>
    <?php else: ?>
      <span class="val err">No disponible</span>
      <div class="fix">Descarga <a href="https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp.exe">yt-dlp.exe</a> y ponlo en <code>bin/</code></div>
    <?php endif; ?>
  </div>
</div>

<div class="row">
  <span class="label">ffmpeg</span>
  <div>
    <?php if ($ffVer): ?>
      <?php $firstLine = strtok($ffVer['output'], "\n"); ?>
      <span class="val <?= $ffVer['code']===0 ? 'ok' : 'err' ?>"><?= htmlspecialchars(trim($firstLine)) ?></span>
    <?php else: ?>
      <span class="val err">No encontrado</span>
      <div class="fix">
        Descarga <a href="https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip">ffmpeg-release-essentials.zip</a>,
        descomprime y copia <code>ffmpeg.exe</code> y <code>ffprobe.exe</code> (de la carpeta <code>bin/</code> del ZIP) a <code>yt-mp3/bin/</code>
      </div>
    <?php endif; ?>
  </div>
</div>

<h2>yt-dlp puede encontrar ffmpeg</h2>
<div class="row">
  <span class="label">Test integración</span>
  <div>
    <?php if ($ffTest): ?>
      <span class="val <?= $ffTest['code']===0 ? 'ok' : 'err' ?>"><?= $ffTest['code']===0 ? 'OK' : 'Error' ?></span>
      <?php if ($ffTest['code'] !== 0): ?>
        <pre><?= htmlspecialchars($ffTest['output']) ?></pre>
        <div class="fix">yt-dlp no puede ejecutar ffmpeg. Asegúrate de que <code>ffmpeg.exe</code> y <code>ffprobe.exe</code> están en <code>bin/</code> y que XAMPP tiene permisos para ejecutarlos.</div>
      <?php endif; ?>
    <?php else: ?>
      <span class="val err">No se pudo comprobar (yt-dlp no encontrado)</span>
    <?php endif; ?>
  </div>
</div>

<h2>PHP exec() habilitado</h2>
<div class="row">
  <span class="label">exec disponible</span>
  <?php $disabled = in_array('exec', array_map('trim', explode(',', ini_get('disable_functions')))); ?>
  <span class="val <?= $disabled ? 'err' : 'ok' ?>"><?= $disabled ? 'DESHABILITADO — edita php.ini y quita exec de disable_functions' : 'OK' ?></span>
</div>

<div class="row">
  <span class="label">proc_open disponible</span>
  <?php $po = in_array('proc_open', array_map('trim', explode(',', ini_get('disable_functions')))); ?>
  <span class="val <?= $po ? 'err' : 'ok' ?>"><?= $po ? 'DESHABILITADO — edita php.ini y quita proc_open de disable_functions' : 'OK' ?></span>
</div>

<div class="row">
  <span class="label">popen / pclose disponible</span>
  <?php $disabledList = array_map('trim', explode(',', ini_get('disable_functions'))); ?>
  <?php $pop = in_array('popen', $disabledList) || in_array('pclose', $disabledList); ?>
  <span class="val <?= $pop ? 'err' : 'ok' ?>"><?= $pop ? 'DESHABILITADO — api.php usa popen()/pclose() para lanzar yt-dlp en segundo plano, edita php.ini y quítalas de disable_functions' : 'OK' ?></span>
</div>

<h2>Últimos archivos descargados</h2>
<?php
$dl = __DIR__ . '/downloads/';
$mp3s = glob($dl . '*.mp3') ?: [];
usort($mp3s, fn($a,$b) => filemtime($b) - filemtime($a));
$recent = array_slice($mp3s, 0, 5);
if ($recent):
  foreach ($recent as $f):
    $size = round(filesize($f)/1024/1024, 2);
    $name = preg_replace('/^[a-f0-9]{16}_/', '', basename($f));
    $date = date('d/m H:i', filemtime($f));
?>
<div class="row">
  <span class="label" style="font-size:12px;color:#999"><?= $date ?></span>
  <span class="val" style="font-size:12px"><?= htmlspecialchars($name) ?> &nbsp;<span style="color:#999">(<?= $size ?> MB)</span></span>
</div>
<?php endforeach; else: ?>
<div class="row"><span class="val" style="color:#999">Sin descargas todavía</span></div>
<?php endif; ?>

<p style="margin-top:3rem;font-size:11px;color:#bbb;letter-spacing:.1em;text-transform:uppercase">
  Borra este archivo (diagnostico.php) cuando termines de depurar.
</p>
</body>
</html>