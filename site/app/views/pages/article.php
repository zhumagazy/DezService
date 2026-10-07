<?php /** $a, $services */ ?>
<article>
  <section class="page-head">
    <div class="container container--narrow">
      <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Статьи' => '/blog', $a['title'] => '/blog/' . $a['slug']]]) ?>
      <h1><?= e($a['h1'] ?: $a['title']) ?></h1>
      <p class="muted">
        <time datetime="<?= e(substr($a['published_at'], 0, 10)) ?>"><?= e(ru_date($a['published_at'])) ?></time>
        <?php if (substr($a['updated_at'], 0, 10) > substr($a['published_at'], 0, 10)): ?>
          · обновлено <time datetime="<?= e(substr($a['updated_at'], 0, 10)) ?>"><?= e(ru_date($a['updated_at'])) ?></time>
        <?php endif ?>
        · <?= reading_minutes($a['body']) ?> мин чтения
      </p>
    </div>
  </section>
  <div class="container container--narrow">
    <?php if ($a['cover']): ?><img class="article__cover" src="<?= e($a['cover']) ?>" alt="<?= e($a['title']) ?>"><?php endif ?>
    <?php if (!empty($a['summary'])): ?>
      <div class="summary"><p class="summary__label">Коротко</p><p><?= nl2br(e($a['summary'])) ?></p></div>
    <?php endif ?>
    <div class="prose"><?= $a['body'] ?></div>
    <?php if (!empty($a['faq'])): ?>
      <section class="article__faq">
        <h2>Частые вопросы</h2>
        <div class="faq">
          <?php foreach ($a['faq'] as $f): ?><details><summary><?= e($f['q']) ?></summary><p><?= e($f['a']) ?></p></details><?php endforeach ?>
        </div>
      </section>
    <?php endif ?>
    <?php $x = cfg('expert'); ?>
    <aside class="expert">
      <img src="<?= img('logo') ?>" alt="" width="64" height="40" loading="lazy">
      <div>
        <?php if (!empty($x['name'])): ?>
          <p class="expert__name">Автор: <?= e($x['name']) ?><?= $x['role'] ? ', ' . e($x['role']) : '' ?></p>
          <p class="muted"><?= $x['experience'] ? 'Стаж — ' . e($x['experience']) . '. ' : '' ?><?= e(cfg('legal_name')) ?>, работаем с <?= (int)cfg('since') ?> года.</p>
        <?php else: ?>
          <p class="expert__name">Материал подготовлен специалистами <?= e(cfg('legal_name')) ?></p>
          <p class="muted">Санитарная служба Астаны с <?= (int)cfg('since') ?> года, работаем по государственной лицензии.</p>
        <?php endif ?>
        <p><a href="/o-kompanii#licenziya">Лицензия и сертификаты</a></p>
      </div>
    </aside>
    <aside class="card article__cta">
      <h2>Нужна профессиональная обработка?</h2>
      <p>Выезд в день обращения, гарантия по договору.</p>
      <div class="hero__actions">
        <a class="btn btn--primary" href="#zayavka">Рассчитать стоимость</a>
        <a class="btn btn--wa" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">WhatsApp</a>
      </div>
    </aside>
  </div>
</article>
<?= view('partials/lead') ?>
<?php $more = array_slice(array_values(array_filter(articles_all(), function ($x) use ($a) { return $x['id'] !== $a['id']; })), 0, 3); ?>
<?php if ($more): ?>
<section class="section section--muted">
  <div class="container">
    <h2>Читайте также</h2>
    <?= view('partials/article-cards', ['list' => $more]) ?>
  </div>
</section>
<?php endif ?>
