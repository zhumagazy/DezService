<?php /** Licence and awards */ ?>
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
