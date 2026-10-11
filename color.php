<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'ابزار رنگ — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">ابزار رنگ</h1>
  <p class="page-sub">انتخاب‌گر، تبدیل بین HEX / RGB / HSL و استخراج پالت از عکس.</p>

  <div class="two-col">
    <div class="card">
      <h2>انتخاب و تبدیل</h2>
      <div class="controls">
        <div class="field"><label>انتخاب‌گر</label><input type="color" id="pick" value="#2563EB" style="width:80px;height:52px"></div>
        <div class="field"><label>HEX</label><input type="text" id="hex" class="ltr" value="#2563EB" style="width:120px"></div>
      </div>
      <table class="kv" style="margin-top:14px" id="vals"></table>
      <div style="margin-top:14px; height:64px; border-radius:6px; border:1px solid var(--line)" id="swatch"></div>
      <h2 style="margin-top:18px">سایه‌ها</h2>
      <div id="shades" style="display:flex; height:44px; border-radius:6px; overflow:hidden; cursor:pointer"></div>
    </div>
    <div class="card">
      <h2>پالت از عکس</h2>
      <div class="drop" id="drop" style="padding:24px"><div class="big">عکس را رها کن</div><div>۶ رنگ غالب استخراج می‌شود</div></div>
      <input type="file" id="file" accept="image/*" hidden>
      <div id="palette" style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:14px"></div>
    </div>
  </div>
</div>

<script>
function hexToRgb(h){
  h = h.replace('#', '');
  if (h.length === 3) h = [...h].map(c => c + c).join('');
  const n = parseInt(h, 16);
  return [n >> 16 & 255, n >> 8 & 255, n & 255];
}
const rgbToHex = (r, g, b) => '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('').toUpperCase();
function rgbToHsl(r, g, b){
  r /= 255; g /= 255; b /= 255;
  const mx = Math.max(r, g, b), mn = Math.min(r, g, b), d = mx - mn;
  let h = 0, s = 0, l = (mx + mn) / 2;
  if (d){
    s = l > .5 ? d / (2 - mx - mn) : d / (mx + mn);
    h = mx === r ? ((g - b) / d + (g < b ? 6 : 0)) : mx === g ? (b - r) / d + 2 : (r - g) / d + 4;
    h *= 60;
  }
  return [Math.round(h), Math.round(s * 100), Math.round(l * 100)];
}

function setColor(hex){
  if (!/^#?[0-9a-f]{3}([0-9a-f]{3})?$/i.test(hex)) return;
  if (!hex.startsWith('#')) hex = '#' + hex;
  const [r, g, b] = hexToRgb(hex);
  const [h, s, l] = rgbToHsl(r, g, b);
  $('#pick').value = rgbToHex(r, g, b);
  $('#hex').value = rgbToHex(r, g, b);
  $('#swatch').style.background = hex;
  const rows = [
    ['HEX', rgbToHex(r, g, b)],
    ['RGB', `rgb(${r}, ${g}, ${b})`],
    ['HSL', `hsl(${h}, ${s}%, ${l}%)`],
  ];
  $('#vals').innerHTML = rows.map(([k, v]) =>
    `<tr><td>${k}</td><td><div class="copy-wrap"><button class="copybtn" data-v="${v}">کپی</button><div class="out" style="padding:8px 60px 8px 10px">${v}</div></div></td></tr>`).join('');
  $$('#vals .copybtn').forEach(bt => bt.onclick = () => copyText(bt.dataset.v, bt));
  // سایه‌ها
  const sh = $('#shades'); sh.innerHTML = '';
  for (let i = 1; i <= 9; i++){
    const ll = Math.round(i * 10);
    const d = document.createElement('div');
    d.style.cssText = 'flex:1;background:hsl(' + h + ',' + s + '%,' + ll + '%)';
    d.title = 'hsl(' + h + ',' + s + '%,' + ll + '%)';
    d.onclick = () => {
      const c = getComputedStyle(d).backgroundColor.match(/\d+/g).map(Number);
      setColor(rgbToHex(c[0], c[1], c[2]));
    };
    sh.appendChild(d);
  }
}
$('#pick').oninput = () => setColor($('#pick').value);
$('#hex').oninput = () => setColor($('#hex').value.trim());
setColor('#2563EB');

/* پالت از عکس: خوشه‌بندی ساده روی نمونه‌ها */
initDrop($('#drop'), $('#file'), async fs => {
  const im = await loadImage(fs[0]);
  const cv = document.createElement('canvas');
  const sc = Math.min(1, 200 / im.naturalWidth);
  cv.width = Math.max(1, Math.round(im.naturalWidth * sc));
  cv.height = Math.max(1, Math.round(im.naturalHeight * sc));
  const cx = cv.getContext('2d');
  cx.drawImage(im, 0, 0, cv.width, cv.height);
  const data = cx.getImageData(0, 0, cv.width, cv.height).data;
  const buckets = new Map();
  for (let i = 0; i < data.length; i += 16){
    if (data[i + 3] < 128) continue;
    const r = data[i] >> 4, g = data[i + 1] >> 4, b = data[i + 2] >> 4;
    const k = (r << 8) | (g << 4) | b;
    const e = buckets.get(k) || [0, 0, 0, 0];
    e[0] += data[i]; e[1] += data[i + 1]; e[2] += data[i + 2]; e[3]++;
    buckets.set(k, e);
  }
  const top = [...buckets.values()].sort((a, b) => b[3] - a[3]).slice(0, 6);
  $('#palette').innerHTML = '';
  for (const [r, g, b, n] of top){
    const hex = rgbToHex(Math.round(r / n), Math.round(g / n), Math.round(b / n));
    const d = document.createElement('div');
    d.style.cssText = 'border:1px solid var(--line);border-radius:6px;overflow:hidden;cursor:pointer';
    d.innerHTML = `<div style="height:52px;background:${hex}"></div><div style="padding:5px;text-align:center;font-size:.8rem" dir="ltr">${hex}</div>`;
    d.onclick = () => { setColor(hex); copyText(hex); };
    $('#palette').appendChild(d);
  }
});
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
