<?php $c = cfg(); ?>
<section class="page-head">
  <div class="container container--narrow">
    <h1>Спасибо! Заявка принята</h1>
    <p class="page-head__lead">Менеджер свяжется с вами в течение 15 минут в рабочее время (<?= e($c['hours'] ?? 'ежедневно 9:00–21:00') ?>). Если вопрос срочный — позвоните или напишите в WhatsApp.</p>
    <p class="thanks__actions">
      <a class="btn btn--primary" href="tel:<?= e($c['phone']) ?>">Позвонить: <?= e($c['phone_human']) ?></a>
      <a class="btn btn--wa" href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Написать в WhatsApp</a>
    </p>
    <p><a href="/">← Вернуться на главную</a></p>
  </div>
</section>
