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

  // 2GIS map is loaded only on demand
  document.querySelectorAll('.map').forEach(function (box) {
    var btn = box.querySelector('.map__load');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var lat = box.dataset.lat, lng = box.dataset.lng, title = box.dataset.title || '';
      var html = '<!doctype html><meta charset="utf-8"><style>html,body,#m{margin:0;height:100%}</style><div id="m"></div>' +
        '<script src="https://maps.api.2gis.ru/2.0/loader.js?pkg=full"><\/script><script>DG.then(function(){var m=DG.map("m",{center:[' +
        lat + ',' + lng + '],zoom:16});DG.marker([' + lat + ',' + lng + ']).addTo(m).bindPopup(' + JSON.stringify(title) + ');});<\/script>';
      var frame = document.createElement('iframe');
      frame.title = 'Карта проезда';
      frame.setAttribute('loading', 'lazy');
      frame.srcdoc = html;
      box.innerHTML = '';
      box.appendChild(frame);
    });
  });

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
