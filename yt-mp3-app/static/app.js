const $ = id => document.getElementById(id);
const el = (tag, cls) => Object.assign(document.createElement(tag), cls ? { className: cls } : {});
const statusBox = $('status');
const trackList = $('trackList');
let currentUrl = '';
let rows = [];  // playlist rows: {chk, name, st, group}

function say(text) {
  statusBox.textContent = text;
  statusBox.hidden = false;
}

async function api(path, body) {
  const res = await fetch(path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = await res.json();
  if (data.error) throw new Error(data.error);
  return data;
}

async function poll(jobId, onUpdate) {
  const job = await (await fetch('/job/' + jobId)).json();
  onUpdate(job);
  if (job.status !== 'done' && job.status !== 'error') {
    setTimeout(() => poll(jobId, onUpdate), 1500);
  }
}

// --- lookup ------------------------------------------------------------------
$('lookupBtn').onclick = async () => {
  const url = $('url').value.trim();
  if (!url) return;
  $('lookupBtn').disabled = true;
  say('Looking up...');
  $('nameSection').hidden = $('playlistSection').hidden = true;
  trackList.replaceChildren();
  rows = [];
  try {
    const data = await api('/lookup', { url });
    currentUrl = url;
    statusBox.hidden = true;
    if (data.type === 'playlist') showPlaylist(data);
    else {
      $('filename').value = data.suggested_filename;
      $('nameSection').hidden = false;
    }
  } catch (e) {
    say('Error: ' + e.message);
  } finally {
    $('lookupBtn').disabled = false;
  }
};

function showPlaylist(data) {
  const fresh = data.tracks.filter(t => !t.downloaded).length;
  const done = data.tracks.length - fresh;
  $('playlistTitle').textContent =
    `${data.playlist_title || 'Playlist'} — ${data.tracks.length} tracks (${fresh} new, ${done} already downloaded)`;

  let lastGroup = null;
  data.tracks.forEach(t => {
    const group = t.downloaded ? 'done' : 'new';
    if (group !== lastGroup) {
      addHeader(group === 'new'
        ? `Not downloaded yet (${fresh})`
        : `Already downloaded (${done}) — unchecked, tick to download again`, group);
      lastGroup = group;
    }
    const row = el('div', 'track-row');
    const chk = el('input');
    chk.type = 'checkbox';
    chk.checked = !t.downloaded;
    chk.dataset.url = t.url;
    const name = el('input');
    name.type = 'text';
    name.value = t.suggested_filename;
    const st = el('span', 'track-status' + (t.downloaded ? ' ok' : ''));
    st.textContent = t.downloaded ? 'downloaded' : '';
    row.append(chk, name, st);
    trackList.append(row);
    rows.push({ chk, name, st, group });
  });
  $('playlistSection').hidden = false;
}

function addHeader(text, group) {
  const h = el('div', 'group-header');
  const span = el('span');
  span.textContent = text;
  const btn = el('button', 'link-btn');
  btn.type = 'button';
  btn.textContent = 'select all / none';
  btn.onclick = () => {
    const boxes = rows.filter(r => r.group === group).map(r => r.chk);
    const allOn = boxes.every(c => c.checked);
    boxes.forEach(c => (c.checked = !allOn));
  };
  h.append(span, btn);
  trackList.append(h);
}

// --- single video ------------------------------------------------------------
$('downloadBtn').onclick = async () => {
  const filename = $('filename').value.trim();
  if (!filename || !currentUrl) return;
  $('downloadBtn').disabled = true;
  say('Starting download...');
  try {
    const { job_id } = await api('/start', { url: currentUrl, filename });
    poll(job_id, job => {
      if (job.status === 'done') {
        const a = el('a', 'download-link');
        a.href = '/file/' + encodeURIComponent(job.filename);
        a.textContent = job.filename;
        statusBox.replaceChildren('Done: ', a);
        $('downloadBtn').disabled = false;
      } else if (job.status === 'error') {
        say('Error: ' + job.error);
        $('downloadBtn').disabled = false;
      } else {
        say(`Working... (${job.status})`);
      }
    });
  } catch (e) {
    say('Error: ' + e.message);
    $('downloadBtn').disabled = false;
  }
};

// --- playlist ----------------------------------------------------------------
$('downloadAllBtn').onclick = async () => {
  const tracks = rows
    .map((r, id) => ({ id, url: r.chk.dataset.url, filename: r.name.value.trim(), on: r.chk.checked }))
    .filter(t => t.on && t.filename);
  if (!tracks.length) return;
  $('downloadAllBtn').disabled = true;
  say(`Queuing ${tracks.length} track(s)...`);
  try {
    const { jobs } = await api('/start_playlist', { tracks });
    say(`Downloading ${jobs.length} track(s)... see progress below.`);
    jobs.forEach(j => poll(j.job_id, job => paint(rows[j.id].st, job)));
  } catch (e) {
    say('Error: ' + e.message);
  } finally {
    $('downloadAllBtn').disabled = false;
  }
};

function paint(st, job) {
  st.className = 'track-status' + (job.status === 'done' ? ' ok' : job.status === 'error' ? ' bad' : '');
  st.textContent = job.status;
  if (job.error) st.title = job.error;
}
