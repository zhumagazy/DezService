(function () {
  'use strict';
  var form = document.getElementById('editForm');
  if (!form) return;
  var csrf = document.getElementById('csrf').value;

  function upload(file) {
    var fd = new FormData();
    fd.append('file', file);
    fd.append('csrf', csrf);
    return fetch('/admin/upload', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.error) throw new Error(j.error); return j.url; });
  }
  function pickImage(cb) {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.onchange = function () { if (input.files[0]) cb(input.files[0]); };
    input.click();
  }

  var quill = new Quill('#editor', {
    theme: 'snow',
    placeholder: 'Начните писать статью…',
    modules: {
      toolbar: {
        container: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['blockquote', 'link', 'image'], ['clean']],
        handlers: {
          image: function () {
            pickImage(function (file) {
              upload(file).then(function (url) {
                var range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'image', url, 'user');
                quill.setSelection(range.index + 1);
              }).catch(function (e) { alert(e.message); });
            });
          }
        }
      }
    }
  });

  // Quill 2 renders bullet lists as <ol><li data-list="bullet">; convert to real <ul>/<ol> before saving
  function cleanHtml(html) {
    var box = document.createElement('div');
    box.innerHTML = html;
    box.querySelectorAll('ol').forEach(function (ol) {
      var items = ol.querySelectorAll(':scope > li');
      if (items.length && Array.prototype.every.call(items, function (li) { return li.dataset.list === 'bullet'; })) {
        var ul = document.createElement('ul');
        while (ol.firstChild) ul.appendChild(ol.firstChild);
        ol.replaceWith(ul);
      }
    });
    box.querySelectorAll('.ql-ui').forEach(function (n) { n.remove(); });
    return box.innerHTML;
  }

  form.addEventListener('submit', function () {
    document.getElementById('body').value = cleanHtml(quill.getSemanticHTML ? quill.getSemanticHTML() : quill.root.innerHTML);
  });

  // cover image
  var cover = document.getElementById('cover'), preview = document.getElementById('coverPreview'), remove = document.getElementById('coverRemove');
  document.getElementById('coverFile').addEventListener('change', function () {
    if (!this.files[0]) return;
    upload(this.files[0]).then(function (url) {
      cover.value = url; preview.src = url; preview.hidden = false; remove.hidden = false;
    }).catch(function (e) { alert(e.message); });
  });
  remove.addEventListener('click', function () { cover.value = ''; preview.hidden = true; remove.hidden = true; });

  // slug suggestion, counters and search snippet preview
  var map = { 'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'e','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'h','ц':'c','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':'','э':'e','ю':'yu','я':'ya','ә':'a','ғ':'g','қ':'k','ң':'n','ө':'o','ұ':'u','ү':'u','һ':'h','і':'i' };
  function slugify(s) {
    return s.toLowerCase().split('').map(function (ch) { return map[ch] !== undefined ? map[ch] : ch; }).join('')
      .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
  }
  var title = document.getElementById('title'), slug = document.getElementById('slug');
  var mt = document.getElementById('meta_title'), md = document.getElementById('meta_description');
  var slugTouched = slug.value !== '';
  slug.addEventListener('input', function () { slugTouched = true; });
  function refresh() {
    if (!slugTouched) slug.value = slugify(title.value);
    document.getElementById('serpSlug').textContent = slug.value || '…';
    document.getElementById('serpTitle').textContent = mt.value || title.value || 'Заголовок статьи';
    document.getElementById('serpDesc').textContent = md.value || 'Описание появится здесь. Если оставить пустым, поисковик возьмёт начало текста.';
    document.querySelectorAll('.a-count').forEach(function (c) {
      var el = document.getElementById(c.dataset.for), n = el.value.length, max = +c.dataset.max;
      c.textContent = n + ' / ' + max;
      c.classList.toggle('is-over', n > max);
    });
  }
  [title, slug, mt, md].forEach(function (el) { el.addEventListener('input', refresh); });
  refresh();

  // warn about unsaved changes
  var dirty = false;
  quill.on('text-change', function () { dirty = true; });
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
})();
