<?php /** Client logos; $class sets the section background */ ?>
<section class="<?= e($class ?? 'section section--muted') ?>" id="klienty">
  <div class="container">
    <h2>Нам доверяют</h2>
    <ul class="logos">
      <?php foreach (data('clients') as $cl): ?>
        <li><img src="<?= img($cl['image']) ?>" alt="<?= e($cl['name']) ?>" loading="lazy"></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
