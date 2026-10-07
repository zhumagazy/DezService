<?php
/** Lead block: quick quiz → WhatsApp, plus the Bitrix24 form. $title optional, $service optional preselect. */
$c = cfg();
$title = $title ?? 'Рассчитаем стоимость за 5 минут';
$service = $service ?? '';
$problems = ['Клопы', 'Тараканы', 'Муравьи, блохи, моль', 'Крысы или мыши', 'Дезинфекция', 'Плесень', 'Другое'];
?>
<section class="section lead" id="zayavka">
  <div class="container lead__grid">
    <div class="lead__intro">
      <h2><?= e($title) ?></h2>
      <p>Ответьте на 3 вопроса — пришлём точную цену в WhatsApp. Или оставьте телефон, и менеджер перезвонит в течение 15 минут.</p>
      <form class="quiz" data-wa="<?= e($c['whatsapp']) ?>">
        <fieldset>
          <legend>Что беспокоит?</legend>
          <div class="chips">
            <?php foreach ($problems as $p): ?>
              <label class="chip"><input type="radio" name="problem" value="<?= e($p) ?>"<?= $p === $service ? ' checked' : '' ?>><span><?= e($p) ?></span></label>
            <?php endforeach ?>
          </div>
        </fieldset>
        <fieldset>
          <legend>Какой объект?</legend>
          <div class="chips">
            <?php foreach (['Квартира', 'Частный дом', 'Офис или магазин', 'Кафе, производство', 'Другое'] as $o): ?>
              <label class="chip"><input type="radio" name="object" value="<?= e($o) ?>"><span><?= e($o) ?></span></label>
            <?php endforeach ?>
          </div>
        </fieldset>
        <label class="field">
          <span>Площадь или количество комнат</span>
          <input type="text" name="size" placeholder="Например, 2 комнаты или 60 м²" autocomplete="off">
        </label>
        <button class="btn btn--wa btn--block" type="submit">Получить расчёт в WhatsApp</button>
      </form>
    </div>
    <div class="lead__form card">
      <h3>Или закажите звонок</h3>
      <div class="b24">
        <script data-b24-form="<?= e($c['b24_form']) ?>" data-skip-moving="true">
          (function(w,d,u){var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);})(window,document,'<?= e($c['b24_loader']) ?>');
        </script>
      </div>
      <p class="consent">Отправляя заявку, вы соглашаетесь с <a href="/politika-konfidencialnosti">политикой конфиденциальности</a> и даёте согласие на обработку персональных данных.</p>
      <p class="lead__or">или позвоните: <a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone_human']) ?></a></p>
    </div>
  </div>
</section>
