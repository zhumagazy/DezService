<?php $c = cfg(); $years = (int)date('Y') - (int)$c['since']; ?>
<section class="hero">
  <div class="container hero__grid">
    <div class="hero__text">
      <p class="eyebrow">Санитарная служба Астаны · с <?= (int)$c['since'] ?> года</p>
      <h1>Уничтожим клопов, тараканов и грызунов с гарантией!</h1>
      <p class="hero__lead">Выезд в день обращения. Безопасные для детей и животных препараты. Гарантия в договоре: если вредители вернутся в гарантийный срок — повторная обработка бесплатно.</p>
      <div class="hero__actions">
        <a class="btn btn--primary" href="#zayavka">Рассчитать стоимость</a>
        <a class="btn btn--wa" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Написать в WhatsApp</a>
      </div>
      <ul class="hero__facts">
        <li><strong><?= $years ?> лет</strong><span>на рынке</span></li>
        <li><strong>от 9&nbsp;000&nbsp;₸</strong><span>обработка квартиры</span></li>
        <li><strong>Гарантия</strong><span>по договору</span></li>
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


<section class="section" id="ceny">
  <div class="container">
    <div class="section__head">
      <h2>Цены на обработку квартиры</h2>
      <a href="/ceny" class="link">Все цены →</a>
    </div>
    <div class="price-table card">
      <table class="prices prices--wide">
        <thead><tr><th scope="col">Услуга</th><th scope="col">1-комнатная</th><th scope="col">2-комнатная</th><th scope="col">3-комнатная</th></tr></thead>
        <tbody>
          <?php foreach (['unichtozhenie-klopov', 'unichtozhenie-tarakanov'] as $slug): $s = $services[$slug]; ?>
            <tr>
              <th scope="row"><a href="/uslugi/<?= $slug ?>"><?= e($s['menu']) ?></a></th>
              <?php foreach (array_slice($s['prices'], 0, 3) as $p): ?><td data-label="<?= e($p['name']) ?>"><?= e(price_label($p['price'])) ?></td><?php endforeach ?>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
      <p class="muted">Муравьи, блохи, грызуны, дезинфекция квартиры — от 9 000 ₸. Частные дома и предприятия считаем индивидуально. Гарантия входит в стоимость.</p>
      <a class="btn btn--primary" href="#zayavka">Рассчитать точную стоимость</a>
    </div>
  </div>
</section>

<section class="section section--muted">
  <div class="container">
    <h2>Как мы обрабатываем</h2>
    <ul class="methods">
      <?php foreach (data('methods') as $m): ?>
        <li class="card"><h3><?= e($m['title']) ?></h3><p><?= e($m['text']) ?></p></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>

<?= view('partials/steps') ?>
<?= view('partials/reviews', ['limit' => 3]) ?>

<section class="section">
  <div class="container biz">
    <div>
      <h2>Для бизнеса и организаций</h2>
      <p>Обслуживаем кафе, склады, гостиницы, торговые сети и учреждения по договору: график обработок, журнал учёта, акты и документы для проверок СЭС. Работаем по безналичному расчёту.</p>
      <a class="btn btn--primary" href="/uslugi/dlya-biznesa">Условия для бизнеса</a>
    </div>
    <ul class="checklist">
      <?php foreach ($services['dlya-biznesa']['formats'] as $f): ?><li><strong><?= e($f['title']) ?>.</strong> <?= e($f['text']) ?></li><?php endforeach ?>
    </ul>
  </div>
</section>

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
<?= view('partials/find-us') ?>

<?php if (cfg('products_page')): ?>
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
<?php endif ?>
