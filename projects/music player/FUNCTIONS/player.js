const AUDIO_EXT = ['mp3','wav','ogg','m4a','mp4','flac','aac','oga','webm'];
const VIDEO_COVER_EXT = ['m4a','mp4','aac']; // contenedores MP4 donde intentamos leer la portada
const IMAGE_TYPES = ['image/png', 'image/jpeg', 'image/gif'];

const gearBtn = document.getElementById('gearBtn');
const settingsPanel = document.getElementById('settingsPanel');
const dirInput = document.getElementById('dirInput');
const pickBtn = document.getElementById('pickBtn');
const queueList = document.getElementById('queueList');
const pickVideoBtn = document.getElementById('pickVideoBtn');
const clearVideoBtn = document.getElementById('clearVideoBtn');
const videoInput = document.getElementById('videoInput');
const videoStatus = document.getElementById('videoStatus');
const colorPageBg = document.getElementById('colorPageBg');
const colorPaper = document.getElementById('colorPaper');
const colorInk = document.getElementById('colorInk');
const colorTeal = document.getElementById('colorTeal');
const pickBgImageBtn = document.getElementById('pickBgImageBtn');
const clearBgImageBtn = document.getElementById('clearBgImageBtn');
const bgImageInput = document.getElementById('bgImageInput');
const bgImageStatus = document.getElementById('bgImageStatus');
const resetColorsBtn = document.getElementById('resetColorsBtn');

const audio = document.getElementById('audio');
const songName = document.getElementById('songName');
const songNameInner = document.getElementById('songNameInner');
const songArtist = document.getElementById('songArtist');
const playBtn = document.getElementById('playBtn');
const playIcon = document.getElementById('playIcon');
const replayBtn = document.getElementById('replayBtn');
const nextBtn = document.getElementById('nextBtn');
const progressWrap = document.getElementById('progressWrap');
const progressFill = document.getElementById('progressFill');
const curTime = document.getElementById('curTime');
const durTime = document.getElementById('durTime');
const emptyHint = document.getElementById('emptyHint');
const artFrame = document.getElementById('artFrame');

const DISC_SVG = `<svg viewBox="0 0 150 150">
  <circle cx="75" cy="75" r="70" fill="#171512"/>
  <circle cx="75" cy="75" r="70" fill="none" stroke="#3a362e" stroke-width="1"/>
  <circle cx="75" cy="75" r="55" fill="none" stroke="#3a362e" stroke-width="1"/>
  <circle cx="75" cy="75" r="40" fill="none" stroke="#3a362e" stroke-width="1"/>
  <circle cx="75" cy="75" r="24" fill="#2f8f83"/>
  <circle cx="75" cy="75" r="5" fill="#171512"/>
</svg>`;

let tracks = []; // {title, artist, url, file, ext}
let currentIndex = -1;
let isPlaying = false;
let customMediaUrl = null;
let customMediaType = null; // 'video' | 'image'
let customBgUrl = null;

/* =========================================================
   PERSISTENCIA (IndexedDB): Firefox/Zen no soportan la API
   de "handles" de carpeta (eso es solo Chromium), así que aquí
   guardamos directamente una copia de cada archivo (blob) en
   IndexedDB. Así no hace falta volver a elegir la carpeta.
   ========================================================= */
const DB_NAME = 'musicPlayerStore';
const DB_VERSION = 1;
let dbPromise = null;

function openDB(){
  if(dbPromise) return dbPromise;
  dbPromise = new Promise((resolve, reject) => {
    if(!('indexedDB' in window)){ reject(new Error('IndexedDB no disponible')); return; }
    const req = indexedDB.open(DB_NAME, DB_VERSION);
    req.onupgradeneeded = () => {
      const db = req.result;
      if(!db.objectStoreNames.contains('tracks')){
        db.createObjectStore('tracks', { keyPath: 'id', autoIncrement: true });
      }
      if(!db.objectStoreNames.contains('settings')){
        db.createObjectStore('settings', { keyPath: 'key' });
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
  return dbPromise;
}

async function idbSetSetting(key, value){
  try{
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('settings', 'readwrite');
      tx.objectStore('settings').put({ key, value });
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });
  } catch(err){ console.warn('No se pudo guardar la configuración:', err); }
}

async function idbGetSetting(key){
  try{
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('settings', 'readonly');
      const req = tx.objectStore('settings').get(key);
      req.onsuccess = () => resolve(req.result ? req.result.value : undefined);
      req.onerror = () => reject(req.error);
    });
  } catch(err){ console.warn('No se pudo leer la configuración:', err); return undefined; }
}

async function idbReplaceTracks(trackList){
  try{
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('tracks', 'readwrite');
      const store = tx.objectStore('tracks');
      store.clear();
      trackList.forEach((t, i) => {
        store.add({ order: i, title: t.title, artist: t.artist, ext: t.ext, blob: t.file });
      });
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });
  } catch(err){ console.warn('No se pudo guardar la biblioteca:', err); }
}

async function idbGetAllTracks(){
  try{
    const db = await openDB();
    return new Promise((resolve, reject) => {
      const tx = db.transaction('tracks', 'readonly');
      const req = tx.objectStore('tracks').getAll();
      req.onsuccess = () => resolve(req.result || []);
      req.onerror = () => reject(req.error);
    });
  } catch(err){ console.warn('No se pudo leer la biblioteca:', err); return []; }
}

/* =========================================================
   CARPETA DE MÚSICA
   ========================================================= */
pickBtn.addEventListener('click', () => dirInput.click());

dirInput.addEventListener('change', (e) => {
  const files = Array.from(e.target.files).filter(f => {
    const ext = f.name.split('.').pop().toLowerCase();
    return AUDIO_EXT.includes(ext);
  });

  tracks.forEach(t => URL.revokeObjectURL(t.url));

  tracks = files
    .sort((a,b) => a.name.localeCompare(b.name, undefined, {numeric:true}))
    .map(f => {
      const ext = f.name.split('.').pop().toLowerCase();
      const raw = f.name.replace(/\.[^/.]+$/, '');
      let title = raw, artist = 'Artista desconocido';
      if(raw.includes(' - ')){
        const parts = raw.split(' - ');
        artist = parts[0].trim();
        title = parts.slice(1).join(' - ').trim();
      }
      return { title, artist, url: URL.createObjectURL(f), file: f, ext };
    });

  currentIndex = -1;
  renderQueue();
  idbReplaceTracks(tracks);
  idbSetSetting('lastIndex', null);
  if(tracks.length > 0){
    settingsPanel.classList.remove('open');
    playTrack(0);
  }
});

/* =========================================================
   IMAGEN/VÍDEO PERSONALIZADO EN LUGAR DEL DISCO
   ========================================================= */
pickVideoBtn.addEventListener('click', () => videoInput.click());

videoInput.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if(!file) return;

  const isVideo = file.type === 'video/mp4';
  const isImage = IMAGE_TYPES.includes(file.type);
  if(!isVideo && !isImage){
    videoStatus.textContent = 'Formato no compatible (usa png, jpg, gif o mp4)';
    return;
  }

  if(customMediaUrl) URL.revokeObjectURL(customMediaUrl);
  customMediaUrl = URL.createObjectURL(file);
  customMediaType = isVideo ? 'video' : 'image';
  videoStatus.textContent = `Usando: ${file.name}`;
  idbSetSetting('customMedia', { blob: file, type: customMediaType, name: file.name });
  if(currentIndex !== -1) loadArtwork(tracks[currentIndex]);
  else showCustomMedia();
});

clearVideoBtn.addEventListener('click', () => {
  if(customMediaUrl) URL.revokeObjectURL(customMediaUrl);
  customMediaUrl = null;
  customMediaType = null;
  videoInput.value = '';
  videoStatus.textContent = 'Se muestra el disco por defecto';
  idbSetSetting('customMedia', null);
  if(currentIndex !== -1) loadArtwork(tracks[currentIndex]);
  else showDisc();
});

function showCustomMedia(){
  if(customMediaType === 'video'){
    artFrame.innerHTML = `<video src="${customMediaUrl}" autoplay loop muted playsinline></video>`;
  } else {
    artFrame.innerHTML = `<img src="${customMediaUrl}" alt="Imagen personalizada">`;
  }
}

/* =========================================================
   MENÚ CONFIGURACIÓN
   ========================================================= */
gearBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  const opening = !settingsPanel.classList.contains('open');
  settingsPanel.classList.toggle('open');
  if(opening) syncColorInputs();
});
document.addEventListener('click', (e) => {
  if(!settingsPanel.contains(e.target) && e.target !== gearBtn){
    settingsPanel.classList.remove('open');
  }
});

/* =========================================================
   COLORES
   ========================================================= */
const THEME_VARS = {
  colorPageBg: '--page-bg',
  colorPaper: '--paper',
  colorInk: '--ink',
  colorTeal: '--teal'
};
const COLOR_INPUTS = [colorPageBg, colorPaper, colorInk, colorTeal];

function toHex(color){
  if(color.startsWith('#')) return color;
  const m = color.match(/\d+/g);
  if(!m) return '#000000';
  return '#' + m.slice(0,3).map(n => parseInt(n).toString(16).padStart(2,'0')).join('');
}

function syncColorInputs(){
  const computed = getComputedStyle(document.body);
  colorPageBg.value = toHex(computed.getPropertyValue('--page-bg').trim());
  colorPaper.value = toHex(computed.getPropertyValue('--paper').trim());
  colorInk.value = toHex(computed.getPropertyValue('--ink').trim());
  colorTeal.value = toHex(computed.getPropertyValue('--teal').trim());
}

function persistTheme(){
  const data = {};
  COLOR_INPUTS.forEach(inp => data[inp.id] = inp.value);
  idbSetSetting('theme', data);
}

COLOR_INPUTS.forEach(input => {
  input.addEventListener('input', () => {
    document.body.style.setProperty(THEME_VARS[input.id], input.value);
    persistTheme();
  });
});

resetColorsBtn.addEventListener('click', () => {
  Object.values(THEME_VARS).forEach(varName => document.body.style.removeProperty(varName));
  idbSetSetting('theme', null);
  clearBgImageBtn.click();
  syncColorInputs();
});

/* =========================================================
   IMAGEN DE FONDO DE PÁGINA
   ========================================================= */
pickBgImageBtn.addEventListener('click', () => bgImageInput.click());

bgImageInput.addEventListener('change', (e) => {
  const file = e.target.files[0];
  if(!file) return;
  if(customBgUrl) URL.revokeObjectURL(customBgUrl);
  customBgUrl = URL.createObjectURL(file);
  document.body.style.backgroundImage = `url(${customBgUrl})`;
  bgImageStatus.textContent = `Usando: ${file.name}`;
  idbSetSetting('bgImage', { blob: file, name: file.name });
});

clearBgImageBtn.addEventListener('click', () => {
  if(customBgUrl) URL.revokeObjectURL(customBgUrl);
  customBgUrl = null;
  bgImageInput.value = '';
  document.body.style.backgroundImage = '';
  bgImageStatus.textContent = 'Sin imagen de fondo';
  idbSetSetting('bgImage', null);
});

/* =========================================================
   COLA / LISTA DE PISTAS
   ========================================================= */
function renderQueue(){
  if(tracks.length === 0){
    queueList.innerHTML = '<div class="queue-empty">Todavía no hay pistas cargadas</div>';
    return;
  }
  queueList.innerHTML = tracks.map((t, i) => `
    <div class="queue-item ${i === currentIndex ? 'active' : ''}" data-index="${i}">
      <span class="queue-idx">${String(i+1).padStart(2,'0')}</span>
      <span class="queue-text"><span class="queue-item-inner">${escapeHtml(t.title)}</span></span>
    </div>
  `).join('');
  queueList.querySelectorAll('.queue-item').forEach(el => {
    el.addEventListener('click', () => playTrack(parseInt(el.dataset.index)));
    const textWrap = el.querySelector('.queue-text');
    const inner = el.querySelector('.queue-item-inner');
    const overflowPx = inner.scrollWidth - textWrap.clientWidth;
    if(overflowPx > 0){
      el.classList.add('overflow');
      inner.style.setProperty('--scroll-dist', overflowPx + 'px');
    }
  });
  const activeEl = queueList.querySelector('.queue-item.active');
  if(activeEl) activeEl.scrollIntoView({ block: 'nearest' });
}

function escapeHtml(s){
  const div = document.createElement('div');
  div.textContent = s;
  return div.innerHTML;
}

function playTrack(index, autoplay = true){
  if(index < 0 || index >= tracks.length) return;
  currentIndex = index;
  const t = tracks[index];
  audio.src = t.url;
  if(autoplay){
    audio.play();
    isPlaying = true;
  } else {
    isPlaying = false;
  }
  updatePlayIcon();
  songNameInner.textContent = t.title;
  songArtist.textContent = t.artist;
  songName.classList.remove('overflow');
  requestAnimationFrame(() => {
    const overflowPx = songNameInner.scrollWidth - songName.clientWidth;
    if(overflowPx > 0){
      songName.classList.add('overflow');
      songNameInner.style.setProperty('--scroll-dist', overflowPx + 'px');
    }
  });
  emptyHint.style.display = 'none';
  renderQueue();
  loadArtwork(t);
  idbSetSetting('lastIndex', index);
}

function showDisc(){
  artFrame.innerHTML = DISC_SVG;
}

function loadArtwork(track){
  if(customMediaUrl){
    showCustomMedia();
    return;
  }
  if(!VIDEO_COVER_EXT.includes(track.ext) || typeof jsmediatags === 'undefined'){
    showDisc();
    return;
  }
  jsmediatags.read(track.file, {
    onSuccess: (tag) => {
      const pic = tag.tags && tag.tags.picture;
      if(pic && pic.data){
        const bytes = new Uint8Array(pic.data);
        let binary = '';
        for(let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
        const b64 = btoa(binary);
        artFrame.innerHTML = `<img src="data:${pic.format};base64,${b64}" alt="Portada">`;
      } else {
        showDisc();
      }
    },
    onError: () => showDisc()
  });
}

function updatePlayIcon(){
  playIcon.innerHTML = isPlaying
    ? '<rect x="6" y="5" width="4" height="14"/><rect x="14" y="5" width="4" height="14"/>'
    : '<path d="M8 5v14l11-7z"/>';
}

playBtn.addEventListener('click', () => {
  if(currentIndex === -1){
    if(tracks.length > 0) playTrack(0);
    return;
  }
  if(isPlaying){ audio.pause(); isPlaying = false; }
  else { audio.play(); isPlaying = true; }
  updatePlayIcon();
});

replayBtn.addEventListener('click', () => {
  if(currentIndex === -1) return;
  audio.currentTime = 0;
  audio.play();
  isPlaying = true;
  updatePlayIcon();
});

nextBtn.addEventListener('click', () => {
  if(tracks.length === 0) return;
  const i = currentIndex >= tracks.length - 1 ? 0 : currentIndex + 1;
  playTrack(i);
});

audio.addEventListener('ended', () => nextBtn.click());

audio.addEventListener('timeupdate', () => {
  if(!isNaN(audio.duration) && audio.duration > 0){
    progressFill.style.width = (audio.currentTime / audio.duration * 100) + '%';
    curTime.textContent = formatTime(audio.currentTime);
    durTime.textContent = formatTime(audio.duration);
  }
});

progressWrap.addEventListener('click', (e) => {
  if(isNaN(audio.duration) || currentIndex === -1) return;
  const rect = progressWrap.getBoundingClientRect();
  const ratio = (e.clientX - rect.left) / rect.width;
  audio.currentTime = ratio * audio.duration;
});

function formatTime(sec){
  if(isNaN(sec)) return '0:00';
  const m = Math.floor(sec / 60);
  const s = Math.floor(sec % 60).toString().padStart(2, '0');
  return `${m}:${s}`;
}

/* =========================================================
   RESTAURAR SESIÓN AL CARGAR LA PÁGINA
   ========================================================= */
async function init(){
  const theme = await idbGetSetting('theme');
  if(theme){
    COLOR_INPUTS.forEach(inp => {
      if(theme[inp.id]) document.body.style.setProperty(THEME_VARS[inp.id], theme[inp.id]);
    });
  }
  syncColorInputs();

  const bgImage = await idbGetSetting('bgImage');
  if(bgImage && bgImage.blob){
    customBgUrl = URL.createObjectURL(bgImage.blob);
    document.body.style.backgroundImage = `url(${customBgUrl})`;
    bgImageStatus.textContent = `Usando: ${bgImage.name}`;
  }

  const customMedia = await idbGetSetting('customMedia');
  if(customMedia && customMedia.blob){
    customMediaUrl = URL.createObjectURL(customMedia.blob);
    customMediaType = customMedia.type;
    videoStatus.textContent = `Usando: ${customMedia.name}`;
  }

  const storedTracks = await idbGetAllTracks();
  if(storedTracks.length > 0){
    tracks = storedTracks
      .sort((a, b) => a.order - b.order)
      .map(r => ({
        title: r.title,
        artist: r.artist,
        ext: r.ext,
        file: r.blob,
        url: URL.createObjectURL(r.blob)
      }));
    renderQueue();

    const lastIndex = await idbGetSetting('lastIndex');
    if(typeof lastIndex === 'number' && lastIndex >= 0 && lastIndex < tracks.length){
      playTrack(lastIndex, false);
    } else if(customMediaUrl){
      showCustomMedia();
    }
  } else if(customMediaUrl){
    showCustomMedia();
  }
}

init();