<section class="page-head">
  <div class="container">
    <?= view('partials/crumbs', ['items' => ['Главная' => '/', 'Отзывы' => '/otzyvy']]) ?>
    <h1>Отзывы клиентов</h1>
    <p class="page-head__lead">Мы публикуем отзывы клиентов из 2ГИС. Оставить свой отзыв можно в нашей карточке компании.</p>
  </div>
</section>
<?= view('partials/reviews') ?>
<?= view('partials/lead') ?>
