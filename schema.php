<?php
require __DIR__ . '/auth.php';
abzar_require_page();
$pageTitle = 'Schema Generator — ابزار';
require __DIR__ . '/partials/header.php';
?>
<div class="wrap">
  <h1 class="page-title">Schema Generator (JSON-LD)</h1>
  <p class="page-sub">داده ساخت‌یافته برای گوگل — خروجی را در <code>&lt;head&gt;</code> صفحه بگذار.</p>

  <div class="two-col">
    <div class="card">
      <div class="field" style="margin-bottom:12px">
        <label>نوع Schema</label>
        <select id="type">
          <option value="Article">مقاله (Article)</option>
          <option value="Product">محصول (Product)</option>
          <option value="FAQPage">سؤالات متداول (FAQ)</option>
          <option value="LocalBusiness">کسب‌وکار محلی</option>
          <option value="BreadcrumbList">مسیر صفحه (Breadcrumb)</option>
        </select>
      </div>
      <div id="fields"></div>
    </div>
    <div class="card">
      <h2>خروجی</h2>
      <div class="copy-wrap">
        <button class="copybtn" id="cp">کپی</button>
        <div class="out" id="out" style="min-height:280px"></div>
      </div>
    </div>
  </div>
</div>

<script>
const FIELDS = {
  Article: [
    ['headline', 'عنوان مقاله', 'text'],
    ['description', 'خلاصه', 'text'],
    ['image', 'آدرس تصویر', 'url'],
    ['authorName', 'نام نویسنده', 'text'],
    ['publisherName', 'نام سایت/ناشر', 'text'],
    ['datePublished', 'تاریخ انتشار', 'date'],
    ['dateModified', 'تاریخ به‌روزرسانی', 'date'],
  ],
  Product: [
    ['name', 'نام محصول', 'text'],
    ['description', 'توضیحات', 'text'],
    ['image', 'آدرس تصویر', 'url'],
    ['brand', 'برند', 'text'],
    ['sku', 'کد محصول (SKU)', 'text'],
    ['price', 'قیمت', 'text'],
    ['priceCurrency', 'واحد پول (IRR/USD)', 'text'],
    ['availability', 'موجودی (InStock/OutOfStock)', 'text'],
    ['ratingValue', 'امتیاز (مثلاً 4.6)', 'text'],
    ['reviewCount', 'تعداد نظرات', 'text'],
  ],
  FAQPage: [['faq', 'سؤال و جواب‌ها — هر سؤال یک خط، جوابش خط بعدی', 'textarea']],
  LocalBusiness: [
    ['name', 'نام کسب‌وکار', 'text'],
    ['image', 'آدرس تصویر/لوگو', 'url'],
    ['telephone', 'تلفن', 'text'],
    ['streetAddress', 'آدرس', 'text'],
    ['addressLocality', 'شهر', 'text'],
    ['addressCountry', 'کشور (IR)', 'text'],
    ['url', 'وب‌سایت', 'url'],
    ['openingHours', 'ساعات کار (مثلاً Sa-Th 09:00-18:00)', 'text'],
  ],
  BreadcrumbList: [['crumbs', 'مسیر — هر خط: عنوان | آدرس', 'textarea']],
};

function renderFields(){
  const t = $('#type').value;
  $('#fields').innerHTML = FIELDS[t].map(([id, label, kind]) =>
    kind === 'textarea'
      ? `<div class="field" style="margin-bottom:10px"><label>${label}</label><textarea id="f-${id}" style="min-height:140px"></textarea></div>`
      : `<div class="field" style="margin-bottom:10px"><label>${label}</label><input type="${kind}" id="f-${id}" ${kind !== 'text' ? 'class="ltr"' : ''}></div>`
  ).join('');
  $$('#fields input, #fields textarea').forEach(el => el.addEventListener('input', gen));
  gen();
}

const v = id => ($('#f-' + id) ? $('#f-' + id).value.trim() : '');

function gen(){
  const t = $('#type').value;
  let o = { '@context': 'https://schema.org', '@type': t };
  if (t === 'Article'){
    if (v('headline')) o.headline = v('headline');
    if (v('description')) o.description = v('description');
    if (v('image')) o.image = [v('image')];
    if (v('authorName')) o.author = { '@type': 'Person', name: v('authorName') };
    if (v('publisherName')) o.publisher = { '@type': 'Organization', name: v('publisherName') };
    if (v('datePublished')) o.datePublished = v('datePublished');
    if (v('dateModified')) o.dateModified = v('dateModified');
  } else if (t === 'Product'){
    if (v('name')) o.name = v('name');
    if (v('description')) o.description = v('description');
    if (v('image')) o.image = [v('image')];
    if (v('brand')) o.brand = { '@type': 'Brand', name: v('brand') };
    if (v('sku')) o.sku = v('sku');
    if (v('price')){
      o.offers = { '@type': 'Offer', price: enNum(v('price')), priceCurrency: v('priceCurrency') || 'IRR' };
      if (v('availability')) o.offers.availability = 'https://schema.org/' + v('availability');
    }
    if (v('ratingValue')) o.aggregateRating = { '@type': 'AggregateRating', ratingValue: enNum(v('ratingValue')), reviewCount: enNum(v('reviewCount') || '1') };
  } else if (t === 'FAQPage'){
    const lines = v('faq').split('\n').map(x => x.trim()).filter(Boolean);
    const main = [];
    for (let i = 0; i + 1 < lines.length; i += 2)
      main.push({ '@type': 'Question', name: lines[i], acceptedAnswer: { '@type': 'Answer', text: lines[i + 1] } });
    o.mainEntity = main;
  } else if (t === 'LocalBusiness'){
    if (v('name')) o.name = v('name');
    if (v('image')) o.image = v('image');
    if (v('telephone')) o.telephone = enNum(v('telephone'));
    o.address = { '@type': 'PostalAddress' };
    if (v('streetAddress')) o.address.streetAddress = v('streetAddress');
    if (v('addressLocality')) o.address.addressLocality = v('addressLocality');
    o.address.addressCountry = v('addressCountry') || 'IR';
    if (v('url')) o.url = v('url');
    if (v('openingHours')) o.openingHours = v('openingHours');
  } else if (t === 'BreadcrumbList'){
    o.itemListElement = v('crumbs').split('\n').map(x => x.trim()).filter(Boolean).map((l, i) => {
      const [name, url] = l.split('|').map(x => x.trim());
      return { '@type': 'ListItem', position: i + 1, name, ...(url ? { item: url } : {}) };
    });
  }
  $('#out').textContent = '<script type="application/ld+json">\n' + JSON.stringify(o, null, 2) + '\n<\/script>';
}

$('#type').onchange = renderFields;
renderFields();
$('#cp').onclick = () => copyText($('#out').textContent, $('#cp'));
</script>
<?php include __DIR__ . "/partials/footer.php"; ?>
</body>
</html>
