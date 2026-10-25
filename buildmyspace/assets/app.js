const $ = (s) => document.querySelector(s);
const form = $('#est');
const money = (n) => window.CURRENCY + Number(n).toLocaleString('en-IN');

function readInputs() {
  const f = new FormData(form);
  return {
    plot: +f.get('plot'), coverage: +f.get('coverage'), floors: +f.get('floors'),
    quality: f.get('quality'), beds: +f.get('beds'), baths: +f.get('baths'),
    dining: form.dining.checked, parking: form.parking.checked,
    extras: [...form.querySelectorAll('[name=extras]:checked')].map((c) => c.value),
  };
}

// ---- Floor plan sketch -------------------------------------------------
let floorIdx = 0, lastInp = null;
const FLOOR_NAMES = ['Ground floor', 'First floor', 'Second floor', 'Third floor'];

function split(total, parts) {
  const out = Array(parts).fill(Math.floor(total / parts));
  for (let i = 0; i < total % parts; i++) out[i]++;
  return out;
}

// Decide which rooms sit on which floor. Ground floor gets the public rooms and one bedroom.
function floorPlan(inp) {
  const F = inp.floors;
  const beds = F === 1 ? [inp.beds] : [1, ...split(inp.beds - 1, F - 1)];
  const baths = F === 1 ? [inp.baths] : [1, ...split(inp.baths - 1, F - 1)];
  let b = 0, t = 0;
  return beds.map((nb, f) => {
    const priv = [];
    for (let i = 0; i < Math.max(nb, baths[f]); i++) {
      if (i < nb) priv.push({ name: 'Bedroom ' + ++b, w: 14, t: 'bed' });
      if (i < baths[f]) priv.push({ name: 'Bath ' + ++t, w: 5, t: 'wet' });
    }
    if (F > 1) priv.push({ name: 'Stairs', w: 5, t: 'stair' });
    if (f > 0 && nb === 0) priv.push({ name: 'Lounge', w: 14, t: 'pub' });
    const pub = f > 0 ? [] : [
      { name: 'Living', w: 18, t: 'pub' },
      ...(inp.dining ? [{ name: 'Dining', w: 8, t: 'pub' }] : []),
      { name: 'Kitchen', w: 10, t: 'pub' },
      ...(inp.parking ? [{ name: 'Parking', w: 12, t: 'car' }] : []),
    ];
    return { priv, pub };
  });
}

function twoRows(list) {
  const sum = list.reduce((a, r) => a + r.w, 0);
  let acc = 0, cut = list.length;
  for (let i = 0; i < list.length; i++) { acc += list[i].w; if (acc >= sum / 2) { cut = i + 1; break; } }
  return cut >= list.length ? [list] : [list.slice(0, cut), list.slice(cut)];
}

function drawPlan(inp) {
  lastInp = inp;
  const floors = floorPlan(inp);
  floorIdx = Math.min(floorIdx, floors.length - 1);
  const { priv, pub } = floors[floorIdx];
  const rows = pub.length ? [priv, pub] : twoRows(priv);
  const area = (inp.plot * inp.coverage) / 100;
  const wFt = Math.sqrt(area * 1.3), dFt = area / wFt;
  const PW = 560, k = PW / wFt, PH = dFt * k, M = 44;
  const total = rows.flat().reduce((a, r) => a + r.w, 0);
  const cls = { bed: 'r-bed', wet: 'r-wet', pub: 'r-pub', car: 'r-car', stair: 'r-stair' };
  let rooms = '', marks = '', labels = '', y = M;

  rows.forEach((row, ri) => {
    const rw = row.reduce((a, r) => a + r.w, 0), h = (PH * rw) / total;
    const first = ri === 0, last = ri === rows.length - 1;
    let x = M;
    row.forEach((r) => {
      const w = (PW * r.w) / rw, dw = Math.min(3 * k, w * 0.6), x0 = x + Math.min(w * 0.15, 8);
      const ww = Math.min(w * 0.5, 5 * k), wx = x + (w - ww) / 2;
      rooms += `<rect class="rm ${cls[r.t]}" x="${x}" y="${y}" width="${w}" height="${h}"/>`;
      if (r.t === 'stair') for (let ly = y + 6; ly < y + h - 3; ly += 6) rooms += `<line class="thin" x1="${x + 4}" x2="${x + w - 4}" y1="${ly}" y2="${ly}"/>`;

      // Doors between the two rows of rooms
      if (rows.length > 1 && r.t !== 'car' && r.t !== 'stair') {
        if (first) marks += `<line class="gap" x1="${x0}" x2="${x0 + dw}" y1="${y + h}" y2="${y + h}"/><path class="door" d="M${x0} ${y + h} V${y + h - dw} A${dw} ${dw} 0 0 1 ${x0 + dw} ${y + h}"/>`;
        else marks += `<line class="gap" x1="${x0}" x2="${x0 + dw}" y1="${y}" y2="${y}"/><path class="door" d="M${x0} ${y} V${y + dw} A${dw} ${dw} 0 0 0 ${x0 + dw} ${y}"/>`;
      }
      // Windows on outer walls, main entrance, garage opening
      if (first && r.t !== 'stair') marks += `<rect class="win" x="${wx}" y="${y - 3}" width="${ww}" height="6"/>`;
      if (last && pub.length && r.name === 'Living') {
        const ex = x + w * 0.3;
        marks += `<line class="gap" x1="${ex}" x2="${ex + dw}" y1="${y + h}" y2="${y + h}"/><path class="door" d="M${ex} ${y + h} V${y + h - dw} A${dw} ${dw} 0 0 1 ${ex + dw} ${y + h}"/>`;
      } else if (last && r.t === 'car') {
        marks += `<line class="gap wide" x1="${x + 6}" x2="${x + w - 6}" y1="${y + h}" y2="${y + h}"/><line class="thin dash" x1="${x + 6}" x2="${x + w - 6}" y1="${y + h}" y2="${y + h}"/>`;
      } else if (last && r.t !== 'stair') {
        marks += `<rect class="win" x="${wx}" y="${y + h - 3}" width="${ww}" height="6"/>`;
      }

      const fs = Math.max(9, Math.min(13, Math.min(w, h) / 5)), cx = x + w / 2, cy = y + h / 2;
      labels += `<text class="lbl" x="${cx}" y="${cy}" font-size="${fs}">${r.name}</text>`;
      if (r.t !== 'stair') labels += `<text class="lbl dim" x="${cx}" y="${cy + fs + 2}" font-size="${fs - 1}">${Math.round(w / k)}′ × ${Math.round(h / k)}′</text>`;
      x += w;
    });
    y += h;
  });

  const dims =
    `<line class="thin" x1="${M}" x2="${M + PW}" y1="${M - 16}" y2="${M - 16}"/>` +
    `<line class="thin" x1="${M}" x2="${M}" y1="${M - 20}" y2="${M - 12}"/><line class="thin" x1="${M + PW}" x2="${M + PW}" y1="${M - 20}" y2="${M - 12}"/>` +
    `<text class="lbl dim" x="${M + PW / 2}" y="${M - 22}" font-size="12">${Math.round(wFt)}′</text>` +
    `<line class="thin" x1="${M - 16}" x2="${M - 16}" y1="${M}" y2="${M + PH}"/>` +
    `<line class="thin" y1="${M}" y2="${M}" x1="${M - 20}" x2="${M - 12}"/><line class="thin" y1="${M + PH}" y2="${M + PH}" x1="${M - 20}" x2="${M - 12}"/>` +
    `<text class="lbl dim" transform="rotate(-90 ${M - 22} ${M + PH / 2})" x="${M - 22}" y="${M + PH / 2}" font-size="12">${Math.round(dFt)}′</text>`;
  const nx = M + PW + 34;
  const north = `<path class="door" d="M${nx} ${M + 30} V${M + 6} M${nx - 6} ${M + 14} L${nx} ${M + 6} L${nx + 6} ${M + 14}"/><text class="lbl" x="${nx}" y="${M + 44}" font-size="12">N</text>`;

  $('#plan').innerHTML =
    `<svg viewBox="0 0 ${PW + 2 * M + 24} ${PH + 2 * M}" role="img" aria-label="Floor plan sketch">` +
    rooms + `<rect class="outer" x="${M}" y="${M}" width="${PW}" height="${PH}"/>` + marks + dims + north + labels + '</svg>';
  $('#planCap').textContent = `${FLOOR_NAMES[floorIdx]} · about ${Math.round(wFt)}′ × ${Math.round(dFt)}′ (${Math.round(area).toLocaleString('en-IN')} sq ft)`;

  const tabs = $('#tabs');
  tabs.innerHTML = '';
  if (floors.length > 1) floors.forEach((_, i) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.textContent = FLOOR_NAMES[i];
    b.setAttribute('aria-pressed', i === floorIdx);
    b.onclick = () => { floorIdx = i; drawPlan(lastInp); };
    tabs.appendChild(b);
  });
}

function render(r) {
  $('#total').textContent = money(r.total);
  $('#meta').textContent = `${r.built_up.toLocaleString('en-IN')} sq ft built-up · ${r.quality} at ${money(r.rate)}/sq ft`;
  const rows = [...r.breakdown, ...r.extras.map((e) => ({ ...e, label: e.label + ' (add-on)' }))];
  $('#rows').innerHTML = rows.map((x) => {
    const pct = Math.round((x.amount / r.total) * 100);
    return `<div class="row"><span>${x.label}</span><span>${money(x.amount)}</span><i style="width:${pct}%"></i></div>`;
  }).join('');
  drawPlan(r.inputs, r.built_up);
}

let timer;
async function update() {
  clearTimeout(timer);
  timer = setTimeout(async () => {
    try {
      const res = await fetch('api.php?action=calc', { method: 'POST', body: JSON.stringify(readInputs()) });
      if (!res.ok) throw new Error();
      render(await res.json());
    } catch {
      $('#total').textContent = 'Unavailable';
      $('#meta').textContent = 'Could not reach the server. Check config.php and that database.sql is imported.';
    }
  }, 150);
}

form.addEventListener('input', () => { $('#covOut').textContent = form.coverage.value; update(); });
form.addEventListener('submit', (e) => e.preventDefault());

$('#saveForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const msg = $('#msg');
  msg.textContent = 'Saving…';
  const f = new FormData(e.target);
  try {
    const res = await fetch('api.php?action=save', {
      method: 'POST',
      body: JSON.stringify({ name: f.get('name'), email: f.get('email'), inputs: readInputs() }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error);
    location.href = 'view.php?id=' + data.id;
  } catch (err) {
    msg.textContent = err.message || 'Could not save. Try again.';
  }
});

update();
