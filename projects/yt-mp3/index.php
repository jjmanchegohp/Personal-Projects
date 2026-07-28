<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>YT — MP3</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="logo">YT — MP3 <span>/ Descargador</span></div>
  <nav>Local · Sin cuenta · Gratis</nav>
</header>

<main>
  <p class="eyebrow">Herramienta</p>
  <h1>Descarga videos de YT como <em>MP3.</em></h1>
  <h3>IM NOT PAYING YT PREMIUM BITCH</h3>
  <br>
  <p class="description">Pega la URL de YouTube, pulsa descargar y guarda el audio en tu dispositivo. Sin límites, sin registro, todo en local.</p>

  <div id="setup-banner">
    <strong>Configuración inicial requerida</strong><br>
    Necesitas <a href="https://www.gyan.dev/ffmpeg/builds/ffmpeg-release-essentials.zip" target="_blank">ffmpeg.exe + ffprobe.exe</a> en la carpeta <code>bin/</code> del proyecto.<br>
    yt-dlp puedes descargarlo aquí mismo:
    <br>
    <button id="install-btn" onclick="installYtdlp()">Descargar yt-dlp</button>
  </div>

  <div class="input-group">
    <input type="text" id="url" placeholder="https://youtube.com/watch?v=..." autocomplete="off"/>
    <button id="go-btn" onclick="startDownload()">Descargar</button>
  </div>

  <div id="preview-card" style="display:none">
    <img id="preview-thumb" src="" alt="">
    <div class="preview-fields">
      <label>Artista / Banda <input type="text" id="artist-input"></label>
      <label>Título de la canción <input type="text" id="title-input"></label>
    </div>
  </div>
  <div id="prog-area">
    <div class="prog-row">
      <span id="phase">Iniciando</span>
      <span id="pct">0%</span>
    </div>
    <div class="track"><div class="fill" id="bar"></div></div>
    <div id="song">Obteniendo información del vídeo…</div>
  </div>

  <button id="dl-btn" onclick="getFile()">Guardar MP3</button>
  <div id="err"></div>

  <hr class="divider">

  <div class="steps">
    <div>
      <p class="step-num">01</p>
      <p class="step-title">Pega la URL</p>
      <p class="step-desc">Copia el enlace del vídeo de YouTube y pégalo en el campo de arriba.</p>
    </div>
    <div>
      <p class="step-num">02</p>
      <p class="step-title">Espera la conversión</p>
      <p class="step-desc">El audio se extrae y convierte a MP3 a 192 kbps automáticamente.</p>
    </div>
    <div>
      <p class="step-num">03</p>
      <p class="step-title">Descarga el archivo</p>
      <p class="step-desc">Pulsa el botón negro para guardar el MP3 en tu dispositivo y ponle nombre que quieras.</p>
    </div>
  </div>

</main>

<footer>
  <span>By JJ aka M1tt0.278 aka Jhon Jairo</span>
  <span>Solo uso personal o al que se lo pase.</span>
</footer>

<script>
let jobId = null, timer = null, previewReady = false;
const $ = id => document.getElementById(id);

(async () => {
  try {
    const r = await fetch('api.php?action=check').then(r => r.json());
    if (!r.ytdlp) $('setup-banner').style.display = 'block';
  } catch(_) {}
})();

async function installYtdlp() {
  const btn = $('install-btn');
  btn.disabled = true;
  btn.textContent = 'Descargando…';
  try {
    const r = await fetch('api.php?action=install').then(r => r.json());
    btn.textContent = r.ok ? 'Instalado correctamente' : 'Error: ' + (r.error || 'inténtalo manual');
    if (!r.ok) btn.disabled = false;
  } catch(e) {
    btn.textContent = 'Error de red';
    btn.disabled = false;
  }
}

$('url').addEventListener('blur', loadPreview);
$('url').addEventListener('keydown', e => { if (e.key === 'Enter') loadPreview(); });

// Si el usuario cambia la URL después de haber cargado un preview,
// invalidamos ese preview para no descargar un vídeo etiquetado con
// datos de la canción anterior.
$('url').addEventListener('input', () => {
  previewReady = false;
  $('preview-card').style.display = 'none';
});

async function loadPreview() {
  const url = $('url').value.trim();
  if (!url) return;
  $('go-btn').disabled = true;
  $('go-btn').textContent = 'Buscando…';
  try {
    const r = await fetch('api.php?action=info&url=' + encodeURIComponent(url)).then(r => r.json());
    if (r.error) throw new Error(r.error);

    $('artist-input').value = r.artist || r.uploader || '';
    $('title-input').value  = r.title  || r.raw_title || '';
    if (r.thumbnail) {
      $('preview-thumb').src = r.thumbnail;
      $('preview-thumb').style.display = 'block';
    } else {
      $('preview-thumb').style.display = 'none';
    }
    $('preview-card').style.display = 'flex';
    previewReady = true;
  } catch(e) {
    showErr(e.message);
  } finally {
    $('go-btn').disabled = false;
    $('go-btn').textContent = 'Descargar';
  }
}

function reset() {
  clearInterval(timer);
  $('bar').style.width = '0%';
  $('dl-btn').style.display = 'none';
  $('err').style.display = 'none';
  $('song').textContent = 'Obteniendo información del vídeo…';
  $('pct').textContent = '0%';
}

async function startDownload() {
  const url = $('url').value.trim();
  if (!url) return;

  // Si aún no se cargó el preview (usuario le dio directo a "Descargar"), cárgalo primero
  if (!previewReady) { await loadPreview(); return; }

  reset();
  $('go-btn').disabled = true;
  $('prog-area').style.display = 'block';
  $('phase').textContent = 'Conectando';
  $('song').innerHTML = '<strong>' + $('artist-input').value + ' - ' + $('title-input').value + '</strong>';

  const fd = new FormData();
  fd.append('url', url);
  fd.append('artist', $('artist-input').value.trim());
  fd.append('title', $('title-input').value.trim());

  try {
    const r = await fetch('api.php?action=start', { method: 'POST', body: fd }).then(r => r.json());
    if (r.error) throw new Error(r.error);
    jobId = r.job_id;
    timer = setInterval(poll, 1000);
  } catch(e) {
    showErr(e.message);
    $('go-btn').disabled = false;
  }
}

async function poll() {
  if (!jobId) return;
  try {
    const d = await fetch(`api.php?action=progress&job=${jobId}`).then(r => r.json());
    $('bar').style.width = (d.progress || 0) + '%';
    $('pct').textContent = (d.progress || 0) + '%';
    if (d.status === 'running') $('phase').textContent = d.progress >= 95 ? 'Convirtiendo a MP3' : 'Descargando audio';
    if (d.status === 'done') {
      clearInterval(timer);
      $('bar').style.width = '100%';
      $('phase').textContent = 'Completado';
      $('pct').textContent = '100%';
      $('dl-btn').style.display = 'block';
      $('go-btn').disabled = false;
    }
    if (d.status === 'error') {
      clearInterval(timer);
      showErr(d.error || 'Error desconocido');
      $('go-btn').disabled = false;
    }
  } catch(_) {}
}

function getFile() {
  if (jobId) window.location.href = `api.php?action=get&job=${jobId}`;
}

function showErr(msg) {
  $('prog-area').style.display = 'none';
  $('err').style.display = 'block';
  $('err').textContent = msg;
}
</script>
</body>
</html>