<?php /** Map with our office; site.js loads it as soon as it comes near the screen */ $c = cfg(); ?>
<div class="map" data-lat="<?= e($c['geo']['lat']) ?>" data-lng="<?= e($c['geo']['lng']) ?>"
     data-title="<?= e($c['name']) ?>" data-address="<?= e($c['address']) ?>" role="region" aria-label="Карта: <?= e($c['address']) ?>">
  <noscript><a href="<?= e($c['gis_firm']) ?>" target="_blank" rel="noopener">Открыть карту в 2ГИС</a></noscript>
</div>
