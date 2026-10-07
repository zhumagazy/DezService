<?php /** $slug, $s, $services */ ?>
<section class="page-head page-head--media">
  <div class="container page-head__grid">
    <div>
      <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Услуги' => '/uslugi', $s['menu'] => '/uslugi/' . $slug]]) ?>
      <h1><?= e($s['h1']) ?></h1>
      <p class="page-head__lead"><?= e($s['lead']) ?></p>
      <div class="hero__actions">
        <a class="btn btn--primary" href="#zayavka">Рассчитать стоимость</a>
        <a class="btn btn--wa" href="<?= e(wa_link('Здравствуйте! Нужна услуга: ' . $s['menu'] . '. Сколько будет стоить?')) ?>" target="_blank" rel="noopener">WhatsApp</a>
      </div>
    </div>
    <img class="page-head__img" src="<?= img($s['image']) ?>" alt="<?= e($s['h1']) ?>" width="600" height="400">
  </div>
</section>

<section class="section">
  <div class="container two-col">
    <div>
      <h2>Когда пора вызывать специалиста</h2>
      <ul class="checklist">
        <?php foreach ($s['signs'] as $sign): ?><li><?= e($sign) ?></li><?php endforeach ?>
      </ul>
    </div>
    <div class="card price-card">
      <h2>Стоимость</h2>
      <table class="prices">
        <tbody>
          <?php foreach ($s['prices'] as $p): ?>
            <tr><th scope="row"><?= e($p['name']) ?></th><td><?= e(price_label($p['price'])) ?></td></tr>
          <?php endforeach ?>
        </tbody>
      </table>
      <p class="muted">Точная цена зависит от площади и степени заражения. Расчёт бесплатный, гарантия 3 месяца входит в стоимость.</p>
      <a class="btn btn--primary btn--block" href="#zayavka">Узнать точную цену</a>
    </div>
  </div>
</section>

<?php if (!empty($s['formats'])): ?>
<section class="section section--muted">
  <div class="container">
    <h2>Форматы работы</h2>
    <ul class="methods">
      <?php foreach ($s['formats'] as $f): ?><li class="card"><h3><?= e($f['title']) ?></h3><p><?= e($f['text']) ?></p></li><?php endforeach ?>
    </ul>
  </div>
</section>
<?php endif ?>
<?= view('partials/advantages') ?>
<?= view('partials/steps') ?>
<?= view('partials/lead', ['title' => 'Рассчитаем стоимость: ' . mb_strtolower($s['menu'])]) ?>
<?= view('partials/reviews', ['limit' => 3, 'service' => $slug]) ?>
<?= view('partials/faq', ['faq' => $s['faq']]) ?>

<section class="section section--muted">
  <div class="container">
    <h2>Другие услуги</h2>
    <?= view('partials/service-cards', ['services' => $services, 'exclude' => $slug]) ?>
  </div>
</section>
