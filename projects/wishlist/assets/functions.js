let items  = [];
let filter = 'all';

async function api(action, body = null) {
  const opts = { method: body ? 'POST' : 'GET', headers: { 'Content-Type': 'application/json' } };
  if (body) opts.body = JSON.stringify(body);
  const res  = await fetch(`?action=${action}`, opts);
  return res.json();
}

async function loadItems() {
  const data = await api('list');
  if (data.ok) { items = data.items; render(); }
}

async function addItem() {
  const url  = document.getElementById('inp-url').value.trim();
  const text = document.getElementById('inp-text').value.trim();
  const tag  = document.getElementById('inp-tag').value.trim();
  if (!url && !text) { shake(document.querySelector('.input-block')); return; }

  setLoading(true);
  const data = await api('add', { url, text, tag });
  setLoading(false);

  if (data.ok) {
    items.unshift(data.item);
    document.getElementById('inp-url').value  = '';
    document.getElementById('inp-text').value = '';
    document.getElementById('inp-tag').value  = '';
    render();
  }
}

async function toggleItem(id) {
  const data = await api('toggle', { id });
  if (data.ok) {
    const idx = items.findIndex(i => i.id === id);
    if (idx > -1) items[idx] = data.item;
    render();
  }
}

async function deleteItem(id) {
  const data = await api('delete', { id });
  if (data.ok) { items = items.filter(i => i.id !== id); render(); }
}

async function clearDone() {
  const data = await api('clear_done');
  if (data.ok) { items = items.filter(i => !i.done); render(); }
}

function setFilter(f, btn) {
  filter = f;
  document.querySelectorAll('.wl-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  render();
}

function render() {
  const visible = items.filter(i => {
    if (filter === 'pending') return !i.done;
    if (filter === 'done')    return  i.done;
    return true;
  });

  const total = items.length;
  const done  = items.filter(i => i.done).length;
  document.getElementById('counter').textContent =
    `${total} item${total !== 1 ? 's' : ''} · ${done} conseguido${done !== 1 ? 's' : ''}`;

  const list = document.getElementById('list');
  list.innerHTML = '';

  if (visible.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'wl-empty';
    empty.textContent = 'Lista vacía';
    list.appendChild(empty);
    return;
  }

  visible.forEach(it => {
    const item = document.createElement('div');
    item.className = 'wl-item' + (it.done ? ' done' : '');
    item.dataset.id = it.id;

    const check = document.createElement('div');
    check.className = 'wl-check' + (it.done ? ' checked' : '');
    check.title = it.done ? 'Marcar pendiente' : 'Marcar conseguido';
    check.onclick = () => toggleItem(it.id);
    item.appendChild(check);

    const body = document.createElement('div');
    body.className = 'wl-item-body';

    if (it.url) {
      const a = document.createElement('a');
      a.className   = 'wl-item-url';
      a.href        = it.url;
      a.target      = '_blank';
      a.rel         = 'noopener';
      a.textContent = fmtDomain(it.url);
      body.appendChild(a);
    }

    if (it.text) {
      const p = document.createElement('div');
      p.className   = 'wl-item-text';
      p.textContent = it.text;   
      body.appendChild(p);
    }

    if (it.tag) {
      const tag = document.createElement('span');
      tag.className   = 'wl-item-tag';
      tag.textContent = it.tag;
      body.appendChild(tag);
    }

    const date = document.createElement('span');
    date.className   = 'wl-item-date';
    date.textContent = fmtDate(it.created_at);
    body.appendChild(date);

    item.appendChild(body);

    const del = document.createElement('button');
    del.className   = 'wl-item-del';
    del.title       = 'Eliminar';
    del.textContent = '×';
    del.onclick     = () => deleteItem(it.id);
    item.appendChild(del);

    list.appendChild(item);
  });
}

function fmtDomain(url) {
  try { return new URL(url).hostname.replace(/^www\./, ''); } catch { return url; }
}

function fmtDate(str) {
  if (!str) return '';
  const d = new Date(str);
  return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
}

function shake(el) {
  el.classList.add('shake');
  el.addEventListener('animationend', () => el.classList.remove('shake'), { once: true });
}

function setLoading(on) {
  document.getElementById('go-btn').disabled    = on;
  document.getElementById('go-btn').textContent = on ? '…' : 'Añadir';
}

document.addEventListener('DOMContentLoaded', () => {
  loadItems();

  document.getElementById('inp-url').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('inp-text').focus(); }
  });

  document.getElementById('inp-text').addEventListener('keydown', e => {
    if (e.key === 'Enter' && (e.metaKey || e.ctrlKey)) addItem();
  });
});
