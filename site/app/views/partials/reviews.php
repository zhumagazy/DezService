<?php
/** Reviews: $limit optional, $service optional (prefer reviews about this service). */
$all = data('reviews');
$service = $service ?? '';
// matching service first, then newest
usort($all, function ($a, $b) use ($service) {
    $m = $service ? (int)($b['service'] === $service) - (int)($a['service'] === $service) : 0;
    return $m ?: strcmp($b['date'], $a['date']);
});
$reviews = !empty($limit) ? array_slice($all, 0, $limit) : $all;
?>
<section class="section section--muted" id="otzyvy">
  <div class="container">
    <div class="section__head">
      <h2>Отзывы клиентов</h2>
      <a class="link" href="<?= e(cfg('gis_reviews')) ?>" target="_blank" rel="noopener">Все отзывы в 2ГИС →</a>
    </div>
    <?php if ($reviews): ?>
      <div class="reviews">
        <?php foreach ($reviews as $r): ?>
          <figure class="review card">
            <div class="review__top">
              <span class="review__avatar" aria-hidden="true"><?= e(mb_substr($r['name'], 0, 1)) ?></span>
              <span><strong><?= e($r['name']) ?></strong><br><span class="muted"><?= e(ru_date($r['date'])) ?> · <?= e($r['source']) ?></span></span>
            </div>
            <div class="review__stars" aria-label="Оценка <?= (int)$r['rating'] ?> из 5"><?= str_repeat('★', (int)$r['rating']) ?></div>
            <blockquote><?= nl2br(e($r['text'])) ?></blockquote>
          </figure>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>
</section>
