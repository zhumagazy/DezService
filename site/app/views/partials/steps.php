<section class="section">
  <div class="container">
    <h2>Как мы работаем</h2>
    <ol class="steps">
      <?php foreach (data('steps') as $i => $st): ?>
        <li class="step">
          <span class="step__num"><?= $i + 1 ?></span>
          <h3><?= e($st['title']) ?></h3>
          <p><?= e($st['text']) ?></p>
        </li>
      <?php endforeach ?>
    </ol>
  </div>
</section>
