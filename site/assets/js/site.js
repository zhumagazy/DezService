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

  // Disinfectants catalog: search and an order list sent as one WhatsApp message
  var catalog = document.querySelector('.catalog[data-wa]');
  if (catalog) {
    var rows = catalog.querySelectorAll('.sku__variants li');
    var search = document.querySelector('[data-catalog-search]');
    search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      var any = false;
      rows.forEach(function (li) { li.hidden = q && li.dataset.search.indexOf(q) < 0; });
      catalog.querySelectorAll('.sku').forEach(function (card) {
        var shown = card.querySelector('.sku__variants li:not([hidden])');
        card.hidden = !shown;
        any = any || !!shown;
      });
      catalog.querySelectorAll('.catalog__group').forEach(function (g) {
        g.hidden = !g.querySelector('.sku:not([hidden])');
      });
      catalog.querySelector('.catalog__empty').hidden = any;
    });

    var cartEl = document.querySelector('.cart');
    var panel = document.getElementById('cart-panel');
    var KEY = 'dez-cart';
    var cart = {};
    try { cart = JSON.parse(localStorage.getItem(KEY)) || {}; } catch (err) {}
    var bySku = {};
    rows.forEach(function (li) { bySku[li.dataset.sku] = li; });
    Object.keys(cart).forEach(function (s) { if (!bySku[s]) delete cart[s]; });
    var fmt = function (n) { return n.toLocaleString('ru-RU').replace(/\s/g, ' ') + ' ₸'; };

    function render() {
      var skus = Object.keys(cart), count = 0, total = 0, html = '';
      skus.forEach(function (s) {
        var li = bySku[s], q = cart[s], sum = q * +li.dataset.price;
        count += q; total += sum;
        html += '<li><span>' + li.dataset.name.replace(/</g, '&lt;') + '</span>' +
          '<span class="cart__qty"><button type="button" data-dec="' + s + '" aria-label="Меньше">−</button><span>' + q +
          '</span><button type="button" data-inc="' + s + '" aria-label="Больше">+</button></span>' +
          '<span class="cart__line"><span>арт. ' + s + '</span><span>' + fmt(sum) + '</span></span></li>';
      });
      rows.forEach(function (li) {
        var a = li.querySelector('.sku__add'), q = cart[li.dataset.sku];
        a.classList.toggle('is-in', !!q);
        a.textContent = q ? '×' + q : '+';
      });
      cartEl.querySelector('.cart__list').innerHTML = html;
      cartEl.querySelector('[data-cart-count]').textContent = 'В заказе: ' + count + ' шт.';
      cartEl.querySelector('[data-cart-total]').textContent = fmt(total);
      cartEl.hidden = !skus.length;
      document.documentElement.classList.toggle('has-cart', !!skus.length);
      if (!skus.length) toggle(false);
      try { localStorage.setItem(KEY, JSON.stringify(cart)); } catch (err) {}
    }
    function toggle(open) {
      panel.hidden = !open;
      cartEl.querySelector('.cart__sum').setAttribute('aria-expanded', String(open));
    }
    catalog.addEventListener('click', function (e) {
      var a = e.target.closest('.sku__add');
      if (!a) return;
      e.preventDefault();  // without JS the link opens WhatsApp for this one item
      var s = a.closest('li').dataset.sku;
      cart[s] = (cart[s] || 0) + 1;
      render();
    });
    cartEl.addEventListener('click', function (e) {
      var t = e.target;
      if (t.closest('[data-cart-toggle]')) toggle(panel.hidden);
      if (t.dataset.inc) { cart[t.dataset.inc]++; render(); }
      if (t.dataset.dec) { if (--cart[t.dataset.dec] < 1) delete cart[t.dataset.dec]; render(); }
      if (t.closest('[data-cart-send]')) {
        var lines = Object.keys(cart).map(function (s) {
          return '• ' + bySku[s].dataset.name + ' (арт. ' + s + ') — ' + cart[s] + ' шт.';
        });
        var total = Object.keys(cart).reduce(function (n, s) { return n + cart[s] * +bySku[s].dataset.price; }, 0);
        var text = 'Здравствуйте! Хочу заказать дезсредства:\n' + lines.join('\n') + '\nИтого по прайсу: ' + fmt(total).replace(/ /g, ' ');
        goal('whatsapp-click');
        window.open('https://wa.me/' + catalog.dataset.wa + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
      }
    });
    render();
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
