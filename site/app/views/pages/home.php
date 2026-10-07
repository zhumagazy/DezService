<?php $c = cfg(); $years = (int)date('Y') - (int)$c['since']; ?>
<section class="hero">
  <div class="container hero__grid">
    <div class="hero__text">
      <p class="eyebrow">Санитарная служба Астаны · с <?= (int)$c['since'] ?> года</p>
      <h1>Уничтожим клопов, тараканов и грызунов с гарантией по договору</h1>
      <p class="hero__lead">Выезд в день обращения. Безопасные для детей и животных препараты. Если вредители вернутся в гарантийный срок — повторная обработка бесплатно.</p>
      <div class="hero__actions">
        <a class="btn btn--primary" href="#zayavka">Рассчитать стоимость</a>
        <a class="btn btn--wa" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Написать в WhatsApp</a>
      </div>
      <ul class="hero__facts">
        <li><strong><?= $years ?> лет</strong><span>на рынке</span></li>
        <li><strong>24 часа</strong><span>от заявки до выезда</span></li>
        <li><strong>Лицензия</strong><span>и договор</span></li>
      </ul>
    </div>
    <div class="hero__media">
      <img src="<?= img('hero-master') ?>" alt="Специалист в защитном костюме с оборудованием для обработки" width="900" height="829" fetchpriority="high">
      <div class="hero__badge">
        <img src="<?= img('trophy') ?>" alt="" width="48" height="64">
        <span>Кубок акима: «Лучшее предприятие в сфере оказания услуг»</span>
      </div>
    </div>
  </div>
</section>

<?= view('partials/advantages') ?>

<section class="section section--muted" id="uslugi">
  <div class="container">
    <div class="section__head">
      <h2>Услуги</h2>
      <a href="/ceny" class="link">Все цены →</a>
    </div>
    <?= view('partials/service-cards', ['services' => $services]) ?>
  </div>
</section>

<?= view('partials/steps') ?>
<?= view('partials/reviews', ['limit' => 6]) ?>
<?= view('partials/lead') ?>
<?= view('partials/trust') ?>

<?php if ($articles): ?>
<section class="section section--muted">
  <div class="container">
    <div class="section__head">
      <h2>Полезные статьи</h2>
      <a href="/blog" class="link">Все статьи →</a>
    </div>
    <?= view('partials/article-cards', ['list' => $articles]) ?>
  </div>
</section>
<?php endif ?>

<?= view('partials/faq', ['faq' => data('faq')]) ?>

<section class="section section--muted">
  <div class="container product-teaser">
    <img src="<?= img('generator') ?>" alt="" loading="lazy" width="220" height="220">
    <div>
      <h2>Дезинфицирующие средства</h2>
      <p>Поставляем сертифицированные дезсредства для клиник, детских садов, салонов и общепита: Аламинол, Акваминол, Бианол, Макси-Дез и другие.</p>
      <a class="btn btn--ghost" href="/dezsredstva">Каталог дезсредств</a>
    </div>
  </div>
</section>
