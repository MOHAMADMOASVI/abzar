<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ffmpeg_helper.php';
abzar_require_page();
$serverFF = abzar_ffmpeg_bin() !== null;
$pageTitle = 'جعبه‌ابزار صدا — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">جعبه‌ابزار صدا</h1>
  <?php if ($serverFF): ?>
  <p class="page-sub">تبدیل فرمت، کاهش حجم، حذف نویز، تقویت صدا و برش — پردازش سریع روی همین سرور.</p>
  <?php else: ?>
  <p class="page-sub">تبدیل فرمت، کاهش حجم، حذف نویز، تقویت صدا و برش — با ffmpeg در مرورگر.</p>
  <p class="note">بار اول موتور ffmpeg (~۲۵MB) از همین سرور بارگذاری می‌شود.</p>
  <?php endif; ?>

  <div class="card">
    <div class="drop" id="drop"><div class="big">فایل صوتی را اینجا رها کن</div><div>MP3، WAV، AAC، OGG، M4A و…</div></div>
    <input type="file" id="file" accept="audio/*,.m4a,.aac,.ogg,.wav,.mp3,.flac" hidden>

    <div class="controls" style="margin-top:16px">
      <div class="field">
        <label>عملیات</label>
        <select id="op">
          <option value="convert">تبدیل فرمت</option>
          <option value="compress">کاهش حجم (بیت‌ریت)</option>
          <option value="denoise">حذف نویز</option>
          <option value="volume">تغییر بلندی صدا</option>
          <option value="trim">برش</option>
        </select>
      </div>
      <div class="field op-convert"><label>فرمت خروجی</label><select id="fmt"><option value="mp3">MP3</option><option value="wav">WAV</option><option value="aac">M4A (AAC)</option><option value="ogg">OGG</option></select></div>
      <div class="field op-compress hidden"><label>بیت‌ریت</label><select id="br"><option value="64">64k (کمترین حجم)</option><option value="96" selected>96k</option><option value="128">128k</option></select></div>
      <div class="field op-denoise hidden"><label>شدت حذف نویز</label><select id="nr"><option value="12">ملایم</option><option value="20" selected>متوسط</option><option value="30">قوی</option></select></div>
      <div class="field op-volume hidden"><label>تغییر (dB): <b id="volv">+6</b></label><input type="range" id="vol" min="-15" max="15" value="6" style="width:170px"></div>
      <label class="chk op-volume hidden"><input type="checkbox" id="norm"> نرمال‌سازی خودکار (loudnorm)</label>
      <div class="field op-trim hidden"><label>از ثانیه</label><input type="text" id="tStart" class="ltr" value="0" style="width:90px"></div>
      <div class="field op-trim hidden"><label>تا ثانیه</label><input type="text" id="tEnd" class="ltr" style="width:90px" placeholder="30"></div>
    </div>

    <div class="progress" id="prog"><div class="bar"></div></div>
    <div class="actions">
      <button class="btn btn-primary hidden" id="go">شروع پردازش</button>
      <span id="stat" style="color:var(--text-dim);font-size:.87rem"></span>
    </div>
    <p class="note hidden" id="msg"></p>
    <div id="outRow"></div>
  </div>
</div>

<script>
const SERVER_FF = <?= json_encode($serverFF) ?>;
let aFile = null, ffmpeg = null;

function addResult(outName, blob){
  const d = document.createElement('div');
  d.className = 'frow';
  d.innerHTML = `<span class="nm">${outName}</span><span class="sz">${fmtBytes(aFile.size)} ← ${fmtBytes(blob.size)}</span>`;
  const audio = document.createElement('audio');
  audio.controls = true; audio.src = URL.createObjectURL(blob); audio.style.height = '34px';
  const a = document.createElement('a');
  a.className = 'dl'; a.textContent = 'دانلود';
  a.href = audio.src; a.download = outName;
  d.appendChild(audio);
  d.appendChild(a);
  $('#outRow').appendChild(d);
}

/* پردازش روی سرور (ffmpeg واقعی) */
async function runServer(op){
  const fd = new FormData();
  fd.append('file', aFile);
  const base = baseName(aFile.name);
  const fmt = op === 'convert' ? $('#fmt').value : 'mp3';
  const EXTS = { mp3: 'mp3', wav: 'wav', aac: 'm4a', ogg: 'ogg' };
  let outName;
  if (op === 'convert'){ fd.append('op', 'a_convert'); fd.append('fmt', fmt); outName = base + '.' + EXTS[fmt]; }
  else if (op === 'compress'){ fd.append('op', 'a_compress'); fd.append('br', $('#br').value); outName = base + '-small.mp3'; }
  else if (op === 'denoise'){ fd.append('op', 'a_denoise'); fd.append('nr', $('#nr').value); outName = base + '-denoised.mp3'; }
  else if (op === 'volume'){
    fd.append('op', 'a_volume'); fd.append('db', $('#vol').value);
    if ($('#norm').checked) fd.append('norm', '1');
    outName = base + '-vol.mp3';
  }
  else if (op === 'trim'){
    const e = enNum($('#tEnd').value.trim());
    if (!e) throw new Error('زمان پایان را وارد کن');
    fd.append('op', 'a_trim'); fd.append('start', enNum($('#tStart').value.trim()) || '0'); fd.append('end', e);
    outName = base + '-cut.mp3';
  }
  $('#stat').textContent = 'در حال پردازش روی سرور…';
  const blob = await uploadForm('api_media.php', fd, r => {
    $('#prog').style.display = 'block';
    $('#prog .bar').style.width = Math.round(r * 100) + '%';
  });
  $('#stat').textContent = '';
  $('#prog').style.display = 'none';
  addResult(outName, blob);
}

initDrop($('#drop'), $('#file'), fs => {
  aFile = fs[0];
  $('#drop .big').textContent = aFile.name + ' (' + fmtBytes(aFile.size) + ')';
  $('#go').classList.remove('hidden');
});

$('#vol').oninput = e => $('#volv').textContent = (e.target.value > 0 ? '+' : '') + faNum(e.target.value);
$('#op').onchange = () => {
  const op = $('#op').value;
  $$('[class*=op-]').forEach(el => el.classList.toggle('hidden', ![...el.classList].includes('op-' + op)));
};

async function getFFmpeg(){
  if (ffmpeg && ffmpeg.isLoaded()) return ffmpeg;
  await loadScript('assets/vendor/ffmpeg.min.js');
  const base = location.href.replace(/[^/]*$/, '');
  ffmpeg = FFmpeg.createFFmpeg({
    corePath: base + 'assets/vendor/ffmpeg-core.js',
    mainName: 'main',
    log: false,
    progress: ({ ratio }) => {
      if (ratio >= 0 && ratio <= 1){
        $('#prog').style.display = 'block';
        $('#prog .bar').style.width = Math.round(ratio * 100) + '%';
      }
    },
  });
  $('#stat').textContent = 'بارگذاری موتور ffmpeg…';
  await ffmpeg.load();
  $('#stat').textContent = '';
  return ffmpeg;
}

const ENC = {
  mp3: ['-c:a', 'libmp3lame', '-b:a', '192k'],
  wav: ['-c:a', 'pcm_s16le'],
  aac: ['-c:a', 'aac', '-b:a', '160k'],
  ogg: ['-c:a', 'libvorbis', '-q:a', '5'],
};
const EXT = { mp3: 'mp3', wav: 'wav', aac: 'm4a', ogg: 'ogg' };
const MIME = { mp3: 'audio/mpeg', wav: 'audio/wav', aac: 'audio/mp4', ogg: 'audio/ogg' };

$('#go').onclick = async () => {
  if (!aFile) return;
  const op = $('#op').value;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  $('#outRow').innerHTML = '';
  if (SERVER_FF){
    try { await runServer(op); setBusy($('#go'), false); return; }
    catch (e){ showNote($('#msg'), 'پردازش سروری ناموفق (' + e.message + ') — تلاش در مرورگر…', 'warn'); }
  }
  try {
    const ff = await getFFmpeg();
    const inName = 'in.' + (aFile.name.split('.').pop().toLowerCase() || 'mp3');
    ff.FS('writeFile', inName, await FFmpeg.fetchFile(aFile));
    const base = baseName(aFile.name);

    // فرمت خروجی: در تبدیل از انتخاب کاربر، بقیه mp3 (یا wav→wav)
    let fmt = op === 'convert' ? $('#fmt').value : 'mp3';
    let args = ['-i', inName], suffix = '';

    if (op === 'compress'){ args.push('-c:a', 'libmp3lame', '-b:a', $('#br').value + 'k'); suffix = '-small'; }
    else if (op === 'denoise'){ args.push('-af', 'afftdn=nr=' + $('#nr').value + ':nf=-25,highpass=f=80', ...ENC[fmt]); suffix = '-denoised'; }
    else if (op === 'volume'){
      const f = $('#norm').checked ? 'loudnorm' : 'volume=' + $('#vol').value + 'dB';
      args.push('-af', f, ...ENC[fmt]); suffix = '-vol';
    }
    else if (op === 'trim'){
      const s = enNum($('#tStart').value.trim()) || '0', e = enNum($('#tEnd').value.trim());
      if (!e) throw new Error('زمان پایان را وارد کن');
      args = ['-ss', s, '-to', e, '-i', inName, ...ENC[fmt]]; suffix = '-cut';
    }
    else args.push(...ENC[fmt]);

    const outFs = 'out.' + EXT[fmt];
    args.push(outFs);
    await ff.run(...args);
    const data = ff.FS('readFile', outFs);
    const blob = new Blob([data.buffer], { type: MIME[fmt] });
    ff.FS('unlink', inName);
    try { ff.FS('unlink', outFs); } catch {}

    const outName = base + suffix + '.' + EXT[fmt];
    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="nm">${outName}</span><span class="sz">${fmtBytes(aFile.size)} ← ${fmtBytes(blob.size)}</span>`;
    const audio = document.createElement('audio');
    audio.controls = true; audio.src = URL.createObjectURL(blob); audio.style.height = '34px';
    const a = document.createElement('a');
    a.className = 'dl'; a.textContent = 'دانلود';
    a.href = audio.src; a.download = outName;
    d.appendChild(audio);
    d.appendChild(a);
    $('#outRow').appendChild(d);
    $('#prog').style.display = 'none';
  } catch (e){
    showNote($('#msg'), 'خطا: ' + (e.message || e) + ' — اگر تکرار شد صفحه را رفرش کن.', 'err');
    ffmpeg = null;
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
