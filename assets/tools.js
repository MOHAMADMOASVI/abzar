/* ابزارک‌های مشترک همه صفحات */
'use strict';

const $  = (s, c = document) => c.querySelector(s);
const $$ = (s, c = document) => [...c.querySelectorAll(s)];

function fmtBytes(b){
  if (!isFinite(b)) return '—';
  if (b < 1024) return b + ' B';
  if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
  return (b / 1048576).toFixed(2) + ' MB';
}

const FA_DIGITS = '۰۱۲۳۴۵۶۷۸۹';
const faNum = x => String(x).replace(/\d/g, d => FA_DIGITS[d]);
const enNum = x => String(x)
  .replace(/[۰-۹]/g, d => FA_DIGITS.indexOf(d))
  .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));

function download(blob, name){
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = name;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(a.href), 10000);
}

function baseName(n){ return n.replace(/\.[^.]+$/, ''); }

/* دراپ‌زون: کلیک + درگ‌اند‌دراپ. onFiles(files:File[]) */
function initDrop(drop, input, onFiles){
  drop.addEventListener('click', () => input.click());
  input.addEventListener('change', () => {
    if (input.files.length) onFiles([...input.files]);
    input.value = '';
  });
  ['dragover', 'dragenter'].forEach(ev =>
    drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('over'); }));
  ['dragleave', 'drop'].forEach(ev =>
    drop.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('over'); }));
  drop.addEventListener('drop', e => {
    const fs = [...e.dataTransfer.files];
    if (fs.length) onFiles(fs);
  });
}

function loadImage(fileOrBlob){
  return new Promise((res, rej) => {
    const url = URL.createObjectURL(fileOrBlob);
    const im = new Image();
    im.onload = () => res(im);
    im.onerror = () => { URL.revokeObjectURL(url); rej(new Error('فایل تصویر قابل خواندن نیست')); };
    im.src = url;
  });
}

function canvasBlob(cv, type = 'image/png', q){
  return new Promise((res, rej) =>
    cv.toBlob(b => b ? res(b) : rej(new Error('مرورگر از این فرمت خروجی پشتیبانی نمی‌کند')), type, q));
}

async function copyText(text, btn){
  try { await navigator.clipboard.writeText(text); }
  catch {
    const t = document.createElement('textarea');
    t.value = text; document.body.appendChild(t); t.select();
    document.execCommand('copy'); t.remove();
  }
  if (btn){
    const old = btn.textContent;
    btn.textContent = 'کپی شد ✓';
    setTimeout(() => { btn.textContent = old; }, 1400);
  }
}

function setBusy(btn, busy, busyLabel){
  if (!btn._label) btn._label = btn.innerHTML;
  btn.disabled = busy;
  btn.innerHTML = busy ? '<span class="spin"></span> ' + (busyLabel || 'در حال پردازش…') : btn._label;
}

function showNote(el, msg, kind = ''){
  el.className = 'note' + (kind ? ' ' + kind : '');
  el.innerHTML = msg;
  el.classList.remove('hidden');
}

/* اسکریپت‌های وندور را در صورت نیاز بارگذاری می‌کند (یک‌بار) */
const _loaded = {};
function loadScript(src){
  if (_loaded[src]) return _loaded[src];
  _loaded[src] = new Promise((res, rej) => {
    const s = document.createElement('script');
    s.src = src;
    s.onload = res;
    s.onerror = () => rej(new Error('بارگذاری ' + src + ' ناموفق بود'));
    document.head.appendChild(s);
  });
  return _loaded[src];
}

/* چند فایل → ZIP → دانلود (نیاز به jszip) */
async function zipDownload(items, zipName){
  await loadScript('assets/vendor/jszip.min.js');
  const z = new JSZip();
  for (const it of items) z.file(it.name, it.blob);
  const blob = await z.generateAsync({ type: 'blob', compression: 'DEFLATE' });
  download(blob, zipName);
}

/* آپلود FormData با نوار پیشرفت؛ پاسخ Blob یا reject با پیام خطای JSON سرور */
function uploadForm(url, fd, onProgress){
  return new Promise((resolve, reject) => {
    const x = new XMLHttpRequest();
    x.open('POST', url);
    x.responseType = 'blob';
    x.upload.onprogress = e => { if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total); };
    x.onload = async () => {
      const isJson = (x.response && x.response.type || '').includes('application/json');
      if (x.status === 200 && !isJson) return resolve(x.response);
      try {
        const j = JSON.parse(await x.response.text());
        reject(new Error(j.message || j.error || ('HTTP ' + x.status)));
      } catch { reject(new Error('HTTP ' + x.status)); }
    };
    x.onerror = () => reject(new Error('اتصال به سرور برقرار نشد'));
    x.send(fd);
  });
}

/* ---- فراخوانی AI با پشتیبانی کپچا (حالت عمومی) ---- */
async function aiFetch(payload){
  for (;;){
    const r = await (await fetch('api_ai.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    })).json();
    if (r.error === 'captcha_required'){ await solveCaptcha(); continue; }
    return r;
  }
}

/* مودال کپچا: resolve بعد از حل موفق، reject اگر بسته شود */
function solveCaptcha(){
  return new Promise((resolve, reject) => {
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:200;display:grid;place-items:center;padding:16px';
    ov.innerHTML = `
      <div class="card" style="max-width:330px;width:100%;margin:0">
        <h2 style="margin-bottom:12px">کد امنیتی</h2>
        <p style="font-size:.85rem;color:var(--text-dim);margin-bottom:12px">برای استفاده از هوش مصنوعی، کد داخل تصویر را وارد کن (یک‌بار در هر نشست).</p>
        <img src="captcha.php?img=1&r=${Math.random()}" alt="کد امنیتی" title="برای تصویر جدید کلیک کن"
             style="width:100%;border-radius:8px;border:1px solid var(--line);cursor:pointer;margin-bottom:10px">
        <input type="text" maxlength="5" placeholder="کد ۵ حرفی" autocomplete="off"
               style="width:100%;direction:ltr;text-align:center;letter-spacing:4px;font-weight:700">
        <p class="note err hidden" style="margin:10px 0 0"></p>
        <div class="actions">
          <button class="btn btn-primary" style="flex:1">تأیید</button>
          <button class="btn btn-ghost">انصراف</button>
        </div>
      </div>`;
    document.body.appendChild(ov);
    const img = ov.querySelector('img'), inp = ov.querySelector('input'),
          err = ov.querySelector('.note'), [okB, noB] = ov.querySelectorAll('.actions .btn');
    img.onclick = () => { img.src = 'captcha.php?img=1&r=' + Math.random(); inp.value = ''; inp.focus(); };
    noB.onclick = () => { ov.remove(); reject(new Error('کد امنیتی تأیید نشد')); };
    async function submit(){
      const v = inp.value.trim();
      if (!v) return;
      okB.disabled = true;
      const r = await (await fetch('captcha.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ captcha: v }),
      })).json();
      okB.disabled = false;
      if (r.ok){ ov.remove(); resolve(); }
      else { err.textContent = r.message || 'اشتباه بود؛ دوباره امتحان کن.'; err.classList.remove('hidden'); img.onclick(); }
    }
    okB.onclick = submit;
    inp.addEventListener('keydown', e => { if (e.key === 'Enter') submit(); });
    inp.focus();
  });
}

/* تب‌های ساده: دکمه‌های .tabs[data-tab] و پنل‌های .tabpane#tab-X */
function initTabs(root = document){
  $$('.tabs button', root).forEach(b => b.addEventListener('click', () => {
    $$('.tabs button', root).forEach(x => x.classList.toggle('active', x === b));
    $$('.tabpane', root).forEach(p => p.classList.toggle('active', p.id === 'tab-' + b.dataset.tab));
  }));
}

/* ---- تاریخ شمسی (الگوریتم jalaali) ---- */
function div(a, b){ return ~~(a / b); }
function mod(a, b){ return a - ~~(a / b) * b; }
function jalCal(jy){
  const breaks = [-61,9,38,199,426,686,756,818,1111,1181,1210,1635,2060,2097,2192,2262,2324,2394,2456,3178];
  let bl = breaks.length, gy = jy + 621, leapJ = -14, jp = breaks[0], jump = 0;
  for (let i = 1; i < bl; i++){
    const jm = breaks[i]; jump = jm - jp;
    if (jy < jm) break;
    leapJ += div(jump, 33) * 8 + div(mod(jump, 33), 4);
    jp = jm;
  }
  let n = jy - jp;
  leapJ += div(n, 33) * 8 + div(mod(n, 33) + 3, 4);
  if (mod(jump, 33) === 4 && jump - n === 4) leapJ++;
  const leapG = div(gy, 4) - div((div(gy, 100) + 1) * 3, 4) - 150;
  const march = 20 + leapJ - leapG;
  if (jump - n < 6) n = n - jump + div(jump + 4, 33) * 33;
  let leap = mod(mod(n + 1, 33) - 1, 4);
  if (leap === -1) leap = 4;
  return { leap, gy, march };
}
function g2d(gy, gm, gd){
  let d = div((gy + div(gm - 8, 6) + 100100) * 1461, 4) + div(153 * mod(gm + 9, 12) + 2, 5) + gd - 34840408;
  return d - div(div(gy + 100100 + div(gm - 8, 6), 100) * 3, 4) + 752;
}
function d2g(jdn){
  let j = 4 * jdn + 139361631 + div(div(4 * jdn + 183187720, 146097) * 3, 4) * 4 - 3908;
  const i = div(mod(j, 1461), 4) * 5 + 308;
  const gd = div(mod(i, 153), 5) + 1, gm = mod(div(i, 153), 12) + 1;
  const gy = div(j, 1461) - 100100 + div(8 - gm, 6);
  return [gy, gm, gd];
}
function j2d(jy, jm, jd){
  const r = jalCal(jy);
  return g2d(r.gy, 3, r.march) + (jm - 1) * 31 - div(jm, 7) * (jm - 7) + jd - 1;
}
function d2j(jdn){
  const gy = d2g(jdn)[0];
  let jy = gy - 621;
  const r = jalCal(jy), jdn1f = g2d(gy, 3, r.march);
  let k = jdn - jdn1f;
  if (k >= 0){
    if (k <= 185) return [jy, 1 + div(k, 31), mod(k, 31) + 1];
    k -= 186;
  } else {
    jy--; k += 179;
    if (r.leap === 1) k++;
  }
  return [jy, 7 + div(k, 30), mod(k, 30) + 1];
}
function toJalali(gy, gm, gd){ return d2j(g2d(gy, gm, gd)); }
function toGregorian(jy, jm, jd){ return d2g(j2d(jy, jm, jd)); }
function jalaliMonthLen(jy, jm){
  if (jm <= 6) return 31;
  if (jm <= 11) return 30;
  return jalCal(jy).leap === 1 ? 30 : 29;
}
function todayJalali(){
  const n = new Date();
  return toJalali(n.getFullYear(), n.getMonth() + 1, n.getDate());
}
const JMONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];

/* سایه هدر هنگام اسکرول */
document.addEventListener('DOMContentLoaded', () => {
  const tb = document.getElementById('topbar');
  if (!tb) return;
  const onScroll = () => tb.classList.toggle('scrolled', window.scrollY > 4);
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
});
