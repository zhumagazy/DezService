(function () {
  'use strict';

  // Mobile menu
  var burger = document.querySelector('.burger');
  var nav = document.getElementById('nav');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      var open = burger.getAttribute('aria-expanded') !== 'true';
      // start the panel exactly under the header, whatever its current height
      nav.style.top = Math.round(document.querySelector('.header').getBoundingClientRect().bottom) + 'px';
      burger.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('is-open', open);
      // lock page scroll on <html>: on <body> it would turn body into a scroll box and unstick the header
      document.documentElement.style.overflow = open ? 'hidden' : '';
      document.documentElement.classList.toggle('menu-open', open);
    });
    nav.addEventListener('click', function (e) {
      if (e.target.closest('a') && burger.getAttribute('aria-expanded') === 'true') burger.click();
    });
  }

  // Quiz → WhatsApp with a ready message
  document.querySelectorAll('form.quiz').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var problem = (form.querySelector('[name=problem]:checked') || {}).value || 'не указано';
      var object = (form.querySelector('[name=object]:checked') || {}).value || 'не указано';
      var size = form.querySelector('[name=size]').value.trim() || 'не указано';
      var text = 'Здравствуйте! Хочу узнать стоимость.\nПроблема: ' + problem + '\nОбъект: ' + object + '\nПлощадь: ' + size;
      goal('whatsapp-click');
      window.open('https://wa.me/' + form.dataset.wa + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
    });
  });

  // Map with our office (Yandex Maps widget: works without an API key, unlike the 2GIS JS API).
  // Loads by itself once it is near the screen.
  function loadMap(box) {
    if (box.dataset.loaded) return;
    box.dataset.loaded = '1';
    var ll = box.dataset.lng + ',' + box.dataset.lat;
    var frame = document.createElement('iframe');
    frame.title = 'Карта: ' + box.dataset.address;
    frame.src = 'https://yandex.ru/map-widget/v1/?ll=' + encodeURIComponent(ll) + '&z=16&pt=' + encodeURIComponent(ll + ',pm2rdm');
    frame.setAttribute('allowfullscreen', '');
    box.appendChild(frame);
  }
  var maps = document.querySelectorAll('.map[data-lat]');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { loadMap(en.target); io.unobserve(en.target); } });
    }, { rootMargin: '600px 0px' });
    maps.forEach(function (m) { io.observe(m); });
  } else {
    maps.forEach(loadMap);
  }

  // Conversion goals — same events and counter as on the previous site
  function goal(name) {
    try { if (window.fbq) window.fbq('track', 'Lead'); } catch (err) {}
    try { if (window.ym) window.ym(window.SITE.goals, 'reachGoal', name); } catch (err) {}
    (window.dataLayer = window.dataLayer || []).push({ event: name });
  }
  document.addEventListener('click', function (e) {
    var a = e.target.closest('a[href]');
    if (!a) return;
    var href = a.getAttribute('href');
    if (href.indexOf('tel:') === 0) goal('phone-click');
    else if (/^https:\/\/(wa\.me|api\.whatsapp\.com)\//.test(href)) goal('whatsapp-click');
  });
  document.addEventListener('submit', function (e) {
    if (!e.target.classList.contains('quiz')) goal('form-send');
  }, true);
})();
