<?php $reviews = data('reviews'); $limit = $limit ?? 0; if ($limit) $reviews = array_slice($reviews, 0, $limit); ?>
<section class="section section--muted" id="otzyvy">
  <div class="container">
    <h2>Отзывы клиентов</h2>
    <?php if ($reviews): ?>
      <div class="reviews">
        <?php foreach ($reviews as $r): ?>
          <figure class="review card">
            <div class="review__stars" aria-label="Оценка <?= (int)$r['rating'] ?> из 5"><?= str_repeat('★', (int)$r['rating']) ?></div>
            <blockquote><?= nl2br(e($r['text'])) ?></blockquote>
            <figcaption><strong><?= e($r['name']) ?></strong> · <?= e($r['source'] ?? '2ГИС') ?><?= !empty($r['date']) ? ', ' . e(date('d.m.Y', strtotime($r['date']))) : '' ?></figcaption>
          </figure>
        <?php endforeach ?>
      </div>
    <?php endif ?>
    <p class="reviews__more">
      <a class="btn btn--ghost" href="<?= e(cfg('gis_firm')) ?>" target="_blank" rel="noopener">Все отзывы в 2ГИС</a>
    </p>
  </div>
</section>
