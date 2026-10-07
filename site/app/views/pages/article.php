<?php /** $a, $services */ ?>
<article>
  <section class="page-head">
    <div class="container container--narrow">
      <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Статьи' => '/blog', $a['title'] => '/blog/' . $a['slug']]]) ?>
      <h1><?= e($a['h1'] ?: $a['title']) ?></h1>
      <p class="muted">
        <time datetime="<?= e(substr($a['published_at'], 0, 10)) ?>"><?= e(ru_date($a['published_at'])) ?></time>
        · <?= reading_minutes($a['body']) ?> мин чтения
      </p>
    </div>
  </section>
  <div class="container container--narrow">
    <?php if ($a['cover']): ?><img class="article__cover" src="<?= e($a['cover']) ?>" alt="<?= e($a['title']) ?>"><?php endif ?>
    <div class="prose"><?= $a['body'] ?></div>
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
