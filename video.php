<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/ffmpeg_helper.php';
abzar_require_page();
$serverFF = abzar_ffmpeg_bin() !== null;
$pageTitle = 'جعبه‌ابزار ویدئو — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">جعبه‌ابزار ویدئو</h1>
  <?php if ($serverFF): ?>
  <p class="page-sub">پردازش سریع روی همین سرور (ffmpeg واقعی) — اگر سرور در دسترس نباشد، خودکار در مرورگر انجام می‌شود.</p>
  <?php else: ?>
  <p class="page-sub">همه پردازش‌ها با ffmpeg داخل مرورگر انجام می‌شود — فایل جایی آپلود نمی‌شود.</p>
  <p class="note">بار اول موتور ffmpeg (~۲۵MB) از همین سرور بارگذاری می‌شود. پردازش در مرورگر از نسخه دسکتاپ کندتر است؛ برای فایل‌های زیر ~۲۰۰MB مناسب است.</p>
  <?php endif; ?>

  <div class="card">
    <div class="drop" id="drop"><div class="big">ویدئو را اینجا رها کن</div><div>MP4، MKV، AVI، MOV، WebM و…</div></div>
    <input type="file" id="file" accept="video/*,.mkv,.avi,.mov,.webm,.mp4" hidden>

    <div class="controls" style="margin-top:16px">
      <div class="field">
        <label>عملیات</label>
        <select id="op">
          <option value="compress">فشرده‌سازی</option>
          <option value="convert">تبدیل فرمت</option>
          <option value="trim">بریدن (Trim)</option>
          <option value="audio">استخراج صدا</option>
          <option value="gif">تبدیل به GIF</option>
          <option value="thumb">Thumbnail از یک لحظه</option>
          <option value="scale">تغییر رزولوشن</option>
          <option value="fps">تغییر FPS</option>
          <option value="wm">واترمارک تصویری</option>
        </select>
      </div>

      <div class="field op-compress"><label>شدت فشرده‌سازی: <b id="crfv">28</b> (بیشتر = حجم کمتر)</label><input type="range" id="crf" min="20" max="36" value="28" style="width:170px"></div>

      <div class="field op-convert hidden"><label>فرمت خروجی</label>
        <select id="vfmt"><option value="mp4">MP4 (H.264)</option><option value="mkv">MKV</option><option value="avi">AVI</option><option value="webm">WebM (کند)</option></select>
      </div>

      <div class="field op-trim op-gif hidden"><label>از ثانیه</label><input type="text" id="tStart" class="ltr" value="0" style="width:90px" placeholder="0 یا 00:01:30"></div>
      <div class="field op-trim op-gif hidden"><label>تا ثانیه</label><input type="text" id="tEnd" class="ltr" style="width:90px" placeholder="10"></div>
      <div class="field op-trim hidden"><label>روش</label><select id="trimMode"><option value="copy">سریع (بدون افت کیفیت)</option><option value="enc">دقیق (انکود مجدد)</option></select></div>

      <div class="field op-audio hidden"><label>فرمت صدا</label><select id="afmt"><option value="mp3">MP3</option><option value="aac">M4A (AAC)</option><option value="wav">WAV</option></select></div>

      <div class="field op-gif hidden"><label>عرض GIF</label><select id="gifW"><option value="480">480px</option><option value="360">360px</option><option value="640">640px</option></select></div>
      <div class="field op-gif hidden"><label>FPS</label><select id="gifFps"><option>10</option><option>15</option><option>8</option></select></div>

      <div class="field op-thumb hidden"><label>در ثانیه</label><input type="text" id="thumbAt" class="ltr" value="1" style="width:90px"></div>

      <div class="field op-scale hidden"><label>ارتفاع خروجی</label><select id="height"><option value="1080">1080p</option><option value="720" selected>720p</option><option value="480">480p</option><option value="360">360p</option></select></div>

      <div class="field op-fps hidden"><label>FPS</label><select id="newFps"><option>24</option><option>30</option><option>60</option><option>15</option></select></div>

      <div class="field op-wm hidden"><label>فایل لوگو (PNG)</label><input type="file" id="wmFile" accept="image/*"></div>
      <div class="field op-wm hidden"><label>موقعیت</label><select id="wmPos"><option value="10:10">بالا چپ</option><option value="W-w-10:10">بالا راست</option><option value="10:H-h-10">پایین چپ</option><option value="W-w-10:H-h-10" selected>پایین راست</option></select></div>
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
let vFile = null, ffmpeg = null;

function addResult(outName, blob){
  const d = document.createElement('div');
  d.className = 'frow';
  d.innerHTML = `<span class="nm">${outName}</span><span class="sz">${fmtBytes(vFile.size)} ← ${fmtBytes(blob.size)}</span>`;
  const a = document.createElement('a');
  a.className = 'dl'; a.textContent = 'دانلود';
  a.href = URL.createObjectURL(blob); a.download = outName;
  d.appendChild(a);
  $('#outRow').appendChild(d);
}

/* پردازش روی سرور (ffmpeg واقعی) */
async function runServer(op){
  const fd = new FormData();
  fd.append('op', op);
  fd.append('file', vFile);
  const base = baseName(vFile.name);
  let outName = base + '.mp4';
  if (op === 'compress'){ fd.append('crf', $('#crf').value); outName = base + '-compressed.mp4'; }
  else if (op === 'convert'){ const f = $('#vfmt').value; fd.append('fmt', f); outName = base + '.' + f; }
  else if (op === 'trim'){
    const e = parseTime($('#tEnd').value);
    if (!e) throw new Error('زمان پایان را وارد کن');
    fd.append('start', parseTime($('#tStart').value) || '0'); fd.append('end', e); fd.append('mode', $('#trimMode').value);
    outName = base + '-cut.mp4';
  }
  else if (op === 'audio'){ const f = $('#afmt').value; fd.append('afmt', f); outName = base + '.' + (f === 'aac' ? 'm4a' : f); }
  else if (op === 'gif'){
    fd.append('start', parseTime($('#tStart').value) || '0');
    const e = parseTime($('#tEnd').value); if (e) fd.append('end', e);
    fd.append('fps', $('#gifFps').value); fd.append('w', $('#gifW').value);
    outName = base + '.gif';
  }
  else if (op === 'thumb'){ fd.append('at', parseTime($('#thumbAt').value) || '1'); outName = base + '-thumb.jpg'; }
  else if (op === 'scale'){ fd.append('h', $('#height').value); outName = base + '-' + $('#height').value + 'p.mp4'; }
  else if (op === 'fps'){ fd.append('fps', $('#newFps').value); outName = base + '-' + $('#newFps').value + 'fps.mp4'; }
  else if (op === 'wm'){
    const wf = $('#wmFile').files[0];
    if (!wf) throw new Error('فایل لوگو را انتخاب کن');
    fd.append('wm', wf); fd.append('pos', $('#wmPos').value);
    outName = base + '-wm.mp4';
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
  vFile = fs[0];
  $('#drop .big').textContent = vFile.name + ' (' + fmtBytes(vFile.size) + ')';
  $('#go').classList.remove('hidden');
});

$('#crf').oninput = e => $('#crfv').textContent = faNum(e.target.value);
$('#op').onchange = () => {
  const op = $('#op').value;
  $$('[class*=op-]').forEach(el => {
    el.classList.toggle('hidden', ![...el.classList].includes('op-' + op));
  });
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

const parseTime = s => {
  s = enNum(s.trim());
  if (!s) return null;
  if (/^[\d.]+$/.test(s)) return s;
  return s; // hh:mm:ss هم قبول است
};

$('#go').onclick = async () => {
  if (!vFile) return;
  const op = $('#op').value;
  setBusy($('#go'), true);
  $('#msg').classList.add('hidden');
  $('#outRow').innerHTML = '';
  if (SERVER_FF){
    try { await runServer(op); setBusy($('#go'), false); return; }
    catch (e){ showNote($('#msg'), 'پردازش سروری ناموفق (' + e.message + ') — تلاش در مرورگر…', 'warn'); }
  }
  if (vFile.size > 500 * 1048576){ showNote($('#msg'), 'فایل بزرگ‌تر از ۵۰۰MB برای پردازش مرورگری مناسب نیست.', 'err'); setBusy($('#go'), false); return; }
  try {
    const ff = await getFFmpeg();
    const ext = vFile.name.split('.').pop().toLowerCase() || 'mp4';
    const inName = 'in.' + ext;
    ff.FS('writeFile', inName, await FFmpeg.fetchFile(vFile));

    let args = ['-i', inName], outName, outMime = 'video/mp4';
    const base = baseName(vFile.name);

    if (op === 'compress'){
      outName = base + '-compressed.mp4';
      args.push('-c:v', 'libx264', '-crf', $('#crf').value, '-preset', 'veryfast', '-c:a', 'aac', '-b:a', '96k', 'out.mp4');
    } else if (op === 'convert'){
      const f = $('#vfmt').value;
      outName = base + '.' + f;
      if (f === 'mp4') args.push('-c:v', 'libx264', '-crf', '23', '-preset', 'veryfast', '-c:a', 'aac', 'out.mp4');
      else if (f === 'mkv') args.push('-c:v', 'libx264', '-crf', '23', '-preset', 'veryfast', '-c:a', 'aac', 'out.mkv');
      else if (f === 'avi') args.push('-c:v', 'mpeg4', '-q:v', '5', '-c:a', 'libmp3lame', 'out.avi');
      else args.push('-c:v', 'libvpx', '-b:v', '1M', '-c:a', 'libvorbis', 'out.webm');
      outMime = 'video/' + (f === 'mkv' ? 'x-matroska' : f);
    } else if (op === 'trim'){
      const s = parseTime($('#tStart').value) || '0', e = parseTime($('#tEnd').value);
      if (!e) throw new Error('زمان پایان را وارد کن');
      outName = base + '-cut.mp4';
      args = ['-ss', s, '-to', e, '-i', inName];
      if ($('#trimMode').value === 'copy') args.push('-c', 'copy', 'out.mp4');
      else args.push('-c:v', 'libx264', '-crf', '22', '-preset', 'veryfast', '-c:a', 'aac', 'out.mp4');
    } else if (op === 'audio'){
      const f = $('#afmt').value;
      outName = base + '.' + (f === 'aac' ? 'm4a' : f);
      if (f === 'mp3') args.push('-vn', '-c:a', 'libmp3lame', '-b:a', '192k', 'out.mp3');
      else if (f === 'aac') args.push('-vn', '-c:a', 'aac', '-b:a', '160k', 'out.m4a');
      else args.push('-vn', '-c:a', 'pcm_s16le', 'out.wav');
      outMime = 'audio/' + (f === 'aac' ? 'mp4' : f === 'wav' ? 'wav' : 'mpeg');
    } else if (op === 'gif'){
      const s = parseTime($('#tStart').value) || '0', e = parseTime($('#tEnd').value);
      outName = base + '.gif';
      args = ['-i', inName];
      if (s !== '0') args.splice(0, 0, '-ss', s);
      if (e) args.push('-to', e);
      args.push('-vf', 'fps=' + $('#gifFps').value + ',scale=' + $('#gifW').value + ':-1:flags=lanczos', 'out.gif');
      outMime = 'image/gif';
    } else if (op === 'thumb'){
      outName = base + '-thumb.jpg';
      args = ['-ss', parseTime($('#thumbAt').value) || '1', '-i', inName, '-frames:v', '1', '-q:v', '2', 'out.jpg'];
      outMime = 'image/jpeg';
    } else if (op === 'scale'){
      outName = base + '-' + $('#height').value + 'p.mp4';
      args.push('-vf', 'scale=-2:' + $('#height').value, '-c:v', 'libx264', '-crf', '23', '-preset', 'veryfast', '-c:a', 'copy', 'out.mp4');
    } else if (op === 'fps'){
      outName = base + '-' + $('#newFps').value + 'fps.mp4';
      args.push('-r', $('#newFps').value, '-c:v', 'libx264', '-crf', '23', '-preset', 'veryfast', '-c:a', 'copy', 'out.mp4');
    } else if (op === 'wm'){
      const wf = $('#wmFile').files[0];
      if (!wf) throw new Error('فایل لوگو را انتخاب کن');
      ff.FS('writeFile', 'wm.png', await FFmpeg.fetchFile(wf));
      outName = base + '-wm.mp4';
      args = ['-i', inName, '-i', 'wm.png', '-filter_complex', 'overlay=' + $('#wmPos').value, '-c:v', 'libx264', '-crf', '23', '-preset', 'veryfast', '-c:a', 'copy', 'out.mp4'];
    }

    const outFile = args[args.length - 1];
    await ff.run(...args);
    const data = ff.FS('readFile', outFile);
    const blob = new Blob([data.buffer], { type: outMime });
    ff.FS('unlink', inName);
    try { ff.FS('unlink', outFile); } catch {}

    const d = document.createElement('div');
    d.className = 'frow';
    d.innerHTML = `<span class="nm">${outName}</span><span class="sz">${fmtBytes(vFile.size)} ← ${fmtBytes(blob.size)}</span>`;
    const a = document.createElement('a');
    a.className = 'dl'; a.textContent = 'دانلود';
    a.href = URL.createObjectURL(blob); a.download = outName;
    d.appendChild(a);
    $('#outRow').appendChild(d);
    $('#prog').style.display = 'none';
  } catch (e){
    showNote($('#msg'), 'خطا در پردازش: ' + (e.message || e) + ' — اگر تکرار شد، صفحه را رفرش کن.', 'err');
    ffmpeg = null; // موتور ST بعد از خطا باید از نو بارگذاری شود
  }
  setBusy($('#go'), false);
};
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
