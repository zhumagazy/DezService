<?php /** Clients and certificates strip */ ?>
<section class="section section--muted">
  <div class="container">
    <h2>Нам доверяют</h2>
    <ul class="logos">
      <?php foreach (data('clients') as $cl): ?>
        <li><img src="<?= img($cl['image']) ?>" alt="<?= e($cl['name']) ?>" loading="lazy"></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
<section class="section" id="licenziya">
  <div class="container">
    <h2>Лицензия и награды</h2>
    <ul class="certs">
      <?php foreach (data('certificates') as $ct): ?>
        <li>
          <a href="<?= img($ct['image']) ?>" target="_blank" rel="noopener" class="cert">
            <img src="<?= img($ct['image']) ?>" alt="<?= e($ct['name']) ?>" loading="lazy">
            <span><?= e($ct['name']) ?></span>
          </a>
        </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
