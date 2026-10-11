<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Hash Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Hash Generator</h1>
  <p class="page-sub">MD5، SHA-1، SHA-256 و SHA-512 برای متن و فایل — همه در مرورگر.</p>

  <div class="card">
    <div class="tabs">
      <button class="active" data-tab="text">متن</button>
      <button data-tab="file">فایل</button>
    </div>
    <div class="tabpane active" id="tab-text">
      <div class="field"><label>متن</label><textarea id="in" class="ltr" style="min-height:110px"></textarea></div>
      <div class="actions"><button class="btn btn-primary" id="goT">محاسبه</button></div>
    </div>
    <div class="tabpane" id="tab-file">
      <div class="drop" id="drop"><div class="big">فایل را اینجا رها کن</div><div>هش خودکار محاسبه می‌شود</div></div>
      <input type="file" id="file" hidden>
    </div>
    <div id="res" style="margin-top:14px"></div>
  </div>
</div>

<script>
initTabs();

/* MD5 خالص جاوااسکریپت (WebCrypto از MD5 پشتیبانی نمی‌کند) */
function md5(buf){
  const K = [...Array(64)].map((_, i) => Math.floor(Math.abs(Math.sin(i + 1)) * 2 ** 32));
  const S = [7,12,17,22,7,12,17,22,7,12,17,22,7,12,17,22,5,9,14,20,5,9,14,20,5,9,14,20,5,9,14,20,
             4,11,16,23,4,11,16,23,4,11,16,23,4,11,16,23,6,10,15,21,6,10,15,21,6,10,15,21,6,10,15,21];
  const bytes = new Uint8Array(buf);
  const bitLen = bytes.length * 8;
  const padded = new Uint8Array((((bytes.length + 8) >> 6) + 1) << 6);
  padded.set(bytes);
  padded[bytes.length] = 0x80;
  new DataView(padded.buffer).setUint32(padded.length - 8, bitLen >>> 0, true);
  new DataView(padded.buffer).setUint32(padded.length - 4, Math.floor(bitLen / 2 ** 32), true);
  let a0 = 0x67452301, b0 = 0xefcdab89, c0 = 0x98badcfe, d0 = 0x10325476;
  const dv = new DataView(padded.buffer);
  const rl = (x, c) => (x << c) | (x >>> (32 - c));
  for (let ch = 0; ch < padded.length; ch += 64){
    const M = [...Array(16)].map((_, i) => dv.getUint32(ch + i * 4, true));
    let [A, B, C, D] = [a0, b0, c0, d0];
    for (let i = 0; i < 64; i++){
      let F, g;
      if (i < 16){ F = (B & C) | (~B & D); g = i; }
      else if (i < 32){ F = (D & B) | (~D & C); g = (5 * i + 1) % 16; }
      else if (i < 48){ F = B ^ C ^ D; g = (3 * i + 5) % 16; }
      else { F = C ^ (B | ~D); g = (7 * i) % 16; }
      F = (F + A + K[i] + M[g]) | 0;
      A = D; D = C; C = B;
      B = (B + rl(F, S[i])) | 0;
    }
    a0 = (a0 + A) | 0; b0 = (b0 + B) | 0; c0 = (c0 + C) | 0; d0 = (d0 + D) | 0;
  }
  return [a0, b0, c0, d0].map(x => {
    const b = new Uint8Array(4);
    new DataView(b.buffer).setUint32(0, x, true);
    return [...b].map(v => v.toString(16).padStart(2, '0')).join('');
  }).join('');
}

async function hashAll(buf){
  const hex = b => [...new Uint8Array(b)].map(x => x.toString(16).padStart(2, '0')).join('');
  const out = { MD5: md5(buf) };
  for (const alg of ['SHA-1', 'SHA-256', 'SHA-512'])
    out[alg] = hex(await crypto.subtle.digest(alg, buf));
  return out;
}

function show(res){
  $('#res').innerHTML = '<table class="kv">' + Object.entries(res).map(([k, v]) =>
    `<tr><td>${k}</td><td><div class="copy-wrap"><button class="copybtn" data-v="${v}">کپی</button><div class="out" style="padding:8px 60px 8px 10px">${v}</div></div></td></tr>`
  ).join('') + '</table>';
  $$('#res .copybtn').forEach(b => b.onclick = () => copyText(b.dataset.v, b));
}

$('#goT').onclick = async () => show(await hashAll(new TextEncoder().encode($('#in').value)));

initDrop($('#drop'), $('#file'), async fs => {
  const f = fs[0];
  $('#drop .big').textContent = f.name + ' (' + fmtBytes(f.size) + ')';
  if (f.size > 200 * 1048576){ alert('حداکثر ۲۰۰ مگابایت'); return; }
  show(await hashAll(await f.arrayBuffer()));
});
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
