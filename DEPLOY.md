# Выкладка сайта на dezservis.kz (hoster.kz, Plesk)

Время: ~15 минут. Сайт на минуту-две будет недоступен (шаги 3–4).

## 0. Что нужно
- Вход в панель hoster.kz → Plesk для домена dezservis.kz.
- Архив `dezservis-site-release.zip` (файлы сайта лежат прямо в корне архива: `index.php`, `.htaccess`, папки `app`, `assets`, `storage`, `uploads`).

## 1. PHP
Plesk → Сайты и домены → dezservis.kz → **Настройки PHP** (или «PHP Settings»):
- версия **PHP 8.1 или новее** (подойдёт любая от 7.4), обработчик «FPM application served by Apache»;
- Сохранить.

## 2. Резервная копия старого сайта
Копию кладём **рядом** с `httpdocs`, а не внутрь: всё внутри `httpdocs` доступно из интернета
(сейчас, например, любой может скачать `dezservis.kz_27.08.2025.zip`).

Plesk → **Файлы** → «Домашняя папка»:
1. Создать папку `dezservis_v1_backup` (кнопка «+» → «Создать каталог»). Не внутри `httpdocs`!
2. Открыть `httpdocs`, отметить галочками **всё, кроме `cgi-bin`** (и `.well-known`, если есть):
   `css`, `files`, `images`, `js`, `thankyoupage`, `404.html`, `contact.php`, `dezservis.kz_27.08.2025.zip`,
   `favicon.ico`, `htaccess`, `index.html`, `index.html.bak` и всё, что ниже в списке.
3. «Переместить» → указать `/dezservis_v1_backup`.

Соседние папки домашней папки (`kazmeddez.kz`, `new_kazmeddez.kz`, `wordpress-backups` и др.) — это другие сайты, **их не трогать**.

Адрес `/thankyoupage` (страница «Спасибо») в новом сайте сохранён — цели рекламы и аналитики по нему продолжат работать.

## 3. Загрузка нового сайта
1. В `httpdocs` нажать «Загрузить» → выбрать `dezservis-site-release.zip`.
2. Нажать на загруженный архив (или «⋯» рядом) → **«Извлечь файлы»** → в текущую папку `httpdocs`.
3. Удалить архив `dezservis-site-release.zip` из `httpdocs`.
4. Проверить, что в `httpdocs` есть `index.php` и `.htaccess` (скрытые файлы видны в файловом менеджере Plesk).

## 4. Права на запись
Папки `storage` и `uploads` должны быть доступны сайту на запись (статьи, фото, прайс из админки).
В Plesk обычно уже так. Если в админке появится ошибка при сохранении: «Файлы» → у папки «⋯» → «Изменить права» → владелец: чтение и запись.

## 5. HTTPS
Plesk → dezservis.kz → **SSL/TLS-сертификаты**:
- если сертификата нет — «Установить» бесплатный **Let's Encrypt** (отметить dezservis.kz и www.dezservis.kz);
- включить **«Перенаправление с HTTP на HTTPS» (301, SEO-безопасное)** в «Хостинг и DNS» → «Настройки хостинга».

## 6. Проверка
Открыть в браузере (лучше в режиме инкогнито):
- https://dezservis.kz — главная, меню, карта, кнопки WhatsApp и звонка;
- https://dezservis.kz/dezsredstva — каталог с фото;
- https://dezservis.kz/kk — казахская версия;
- https://dezservis.kz/sitemap.xml и https://dezservis.kz/robots.txt;
- http://www.dezservis.kz — должен перебросить на https://dezservis.kz.

Метки: в Google Tag Assistant (tagassistant.google.com) ввести dezservis.kz — должен найтись GTM-TPNSV8RB.

## 7. Первый вход в админку
1. Открыть https://dezservis.kz/admin — появится форма «Первый вход».
2. В Plesk → «Файлы» → `httpdocs/storage/setup-key.php` → открыть: внизу строка `"key": "…"` — это ключ установки.
3. Ввести ключ, придумать логин и пароль (от 10 символов). Файл с ключом удалится сам.

## Если что-то пошло не так
Вернуть старый сайт: удалить новые файлы из `httpdocs` (кроме `cgi-bin`) и переместить содержимое `dezservis_v1_backup` обратно в `httpdocs`.
