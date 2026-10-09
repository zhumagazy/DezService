<?php
/** @var array $page */
$c = cfg();
$title = $page['title'];
$canonical = $page['canonical'] ?? null;
$image = abs_url($page['image'] ?? img('fog'));
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$kk = lang() === 'kk';
$ruPath = $kk ? (substr($path, 3) ?: '/') : $path;          // the page's Russian address
$bilingual = $canonical && has_kk($canonical);
$kkPath = '/kk' . ($ruPath === '/' ? '' : rtrim($ruPath, '/'));
if ($kk && $canonical) $canonical = '/kk' . ($canonical === '/' ? '' : $canonical);
$nav = [
    '/uslugi' => 'Услуги',
    '/ceny' => 'Цены',
    '/dlya-biznesa' => 'Для бизнеса',
    '/otzyvy' => 'Отзывы',
    '/blog' => 'Статьи',
    '/o-kompanii' => 'О компании',
    '/kontakty' => 'Контакты',
];
$active = function ($href) use ($path) {
    return $path === $href || ($href !== '/' && strpos($path, $href . '/') === 0) ? ' aria-current="page"' : '';
};
?><!DOCTYPE html>
<html lang="ru">
<head>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?= e($c['gtm']) ?>');</script>
<!-- End Google Tag Manager -->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<?php if (!empty($page['description'])): ?><meta name="description" content="<?= e($page['description']) ?>"><?php endif ?>
<?php if (!empty($page['noindex'])): ?><meta name="robots" content="noindex"><?php endif ?>
<?php if ($canonical): ?><link rel="canonical" href="<?= e(abs_url($canonical)) ?>"><?php endif ?>
<?php if ($bilingual): ?>
<link rel="alternate" hreflang="ru" href="<?= e(abs_url($ruPath)) ?>">
<link rel="alternate" hreflang="kk" href="<?= e(abs_url($kkPath)) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e(abs_url($ruPath)) ?>">
<?php endif ?>
<meta property="og:type" content="<?= e($page['og_type'] ?? 'website') ?>">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="<?= e($c['name']) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<?php if (!empty($page['description'])): ?><meta property="og:description" content="<?= e($page['description']) ?>"><?php endif ?>
<?php if ($canonical): ?><meta property="og:url" content="<?= e(abs_url($canonical)) ?>"><?php endif ?>
<meta property="og:image" content="<?= e($image) ?>">
<meta name="facebook-domain-verification" content="zlcpgs4upibq6rgwefe4gk1i3kii1r">
<meta name="theme-color" content="#ffffff">
<link rel="icon" href="/favicon.ico">
<link rel="preload" href="/assets/fonts/inter-cyrillic-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/inter-cyrillic-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/site.css') ?>">
<?php foreach ($page['schema'] ?? [] as $schema): ?>
<script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endforeach ?>
</head>
<body>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= e($c['gtm']) ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<a class="skip" href="#main">Перейти к содержанию</a>

<header class="header">
  <div class="container header__row">
    <a class="logo" href="/" aria-label="<?= e($c['name']) ?> — на главную">
      <img src="<?= img('logo') ?>" alt="<?= e($c['name']) ?>" width="120" height="74">
    </a>
    <nav class="nav" id="nav" aria-label="Основное меню">
      <?php if ($bilingual): ?>
        <div class="lang lang--menu" role="group" aria-label="Язык">
          <a href="<?= e($ruPath) ?>" hreflang="ru" lang="ru"<?= $kk ? '' : ' aria-current="true"' ?>>Рус</a>
          <a href="<?= e($kkPath) ?>" hreflang="kk" lang="kk"<?= $kk ? ' aria-current="true"' : '' ?>>Қаз</a>
        </div>
      <?php endif ?>
      <ul>
        <?php foreach ($nav as $href => $label):
            $target = $href === '/dlya-biznesa' ? '/uslugi/dlya-biznesa' : $href; ?>
          <li><a href="<?= $target ?>"<?= $active($target) ?>><?= e($label) ?></a></li>
        <?php endforeach ?>
      </ul>
      <div class="nav__contacts">
        <a class="nav__phone" href="tel:<?= e($c['phone']) ?>"><?= e($c['phone_human']) ?></a>
        <span class="nav__hours"><?= e($c['hours']) ?></span>
      </div>
    </nav>
    <div class="header__cta">
      <?php if ($bilingual): ?>
        <nav class="lang" aria-label="Язык">
          <a href="<?= e($ruPath) ?>" hreflang="ru" lang="ru"<?= $kk ? '' : ' aria-current="true"' ?>>Рус</a>
          <a href="<?= e($kkPath) ?>" hreflang="kk" lang="kk"<?= $kk ? ' aria-current="true"' : '' ?>>Қаз</a>
        </nav>
      <?php endif ?>
      <div class="header__phone">
        <a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone_human']) ?></a>
        <span><?= e($c['hours']) ?></span>
      </div>
      <a class="btn btn--wa btn--sm" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">WhatsApp</a>
      <button class="burger" type="button" aria-controls="nav" aria-expanded="false" aria-label="Меню"><span></span></button>
    </div>
  </div>
</header>

<main id="main">
<?= $page['body'] ?>
</main>

<footer class="footer">
  <div class="container footer__grid">
    <div>
      <img src="<?= img('logo') ?>" alt="" width="120" height="74" loading="lazy">
      <p class="footer__about">Дезинфекция, дезинсекция и дератизация в Астане с <?= (int)$c['since'] ?> года.</p>
      <?php if ($c['legal_name'] || $c['bin']): ?>
        <p class="footer__muted"><?= e($c['legal_name']) ?><?= $c['bin'] ? ', БИН ' . e($c['bin']) : '' ?></p>
      <?php endif ?>
    </div>
    <div>
      <p class="footer__title">Услуги</p>
      <ul>
        <?php foreach (data('services') as $slug => $s): ?>
          <li><a href="/uslugi/<?= $slug ?>"><?= e($s['menu']) ?></a></li>
        <?php endforeach ?>
        <?php if (cfg('products_page')): ?><li><a href="/dezsredstva">Дезсредства</a></li><?php endif ?>
      </ul>
    </div>
    <div>
      <p class="footer__title">Компания</p>
      <ul>
        <li><a href="/ceny">Цены</a></li>
        <li><a href="/otzyvy">Отзывы</a></li>
        <li><a href="/blog">Статьи</a></li>
        <li><a href="/o-kompanii">О компании</a></li>
        <li><a href="/kontakty">Контакты</a></li>
      </ul>
    </div>
    <div>
      <p class="footer__title">Контакты</p>
      <p><a class="footer__phone" href="tel:<?= e($c['phone']) ?>"><?= e($c['phone_human']) ?></a></p>
      <p><?= e($c['hours']) ?>, без выходных</p>
      <p><?= e($c['city']) ?>, <?= e($c['address']) ?></p>
      <?php if ($c['email']): ?><p><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></p><?php endif ?>
      <p class="footer__social">
        <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">WhatsApp</a>
        <?php if ($c['telegram']): ?><a href="<?= e($c['telegram']) ?>" target="_blank" rel="noopener">Telegram</a><?php endif ?>
        <?php if ($c['instagram']): ?><a href="<?= e($c['instagram']) ?>" target="_blank" rel="noopener">Instagram</a><?php endif ?>
        <a href="<?= e($c['gis_firm']) ?>" target="_blank" rel="noopener">2ГИС</a>
      </p>
    </div>
  </div>
  <div class="container footer__bottom">
    <span>© <?= date('Y') ?> <?= e($c['name']) ?></span>
    <a href="/politika-konfidencialnosti">Политика конфиденциальности</a>
  </div>
</footer>

<nav class="dock" aria-label="Быстрая связь">
  <a class="dock__btn" href="tel:<?= e($c['phone']) ?>">Позвонить</a>
  <a class="dock__btn dock__btn--wa" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">WhatsApp</a>
</nav>

<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js', 'ym');
    ym(<?= (int)$c['metrika'] ?>, 'init', {webvisor:true, clickmap:true, accurateTrackBounce:true, trackLinks:true}); </script>
<noscript><div><img src="https://mc.yandex.ru/watch/<?= (int)$c['metrika'] ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
<script>window.SITE = {goals: <?= (int)$c['metrika_goals'] ?>};</script>
<script src="<?= asset('js/site.js') ?>" defer></script>
</body>
</html>
