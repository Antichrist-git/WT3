# Лабораторная работа №3 — Gods of Olympus

Небольшой каталог богов Древней Греции на Laravel 12. Данные хранятся в SQLite, интерфейс сделан на Bootstrap 5, ресурсы собираются Laravel Mix.

## Возможности

- Просмотр списка богов и информации о каждом в модальном окне.
- Добавление, изменение и мягкое удаление записей (Soft Deletes).
- Проверка формы при вводе и на стороне Laravel, включая уникальность имён.
- Выпадающий список сфер покровительства и дата публикации.
- Загрузка фотографий в `storage/app/public/gods/` с изменением размера через GD.

## Первый запуск после клонирования

Требуются PHP 8.3+, Composer, Node.js и PHP-расширения `pdo_sqlite`, `sqlite3`, `gd`, `mbstring`, `fileinfo`.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item database/database.sqlite -ItemType File -Force | Out-Null
php artisan migrate
php artisan db:seed
php artisan storage:link
npm ci
npm run dev
php artisan serve
```

Откройте http://127.0.0.1:8000/gods

После установки зависимостей также можно запускать проект файлом `START_WT3.bat`. Для отслеживания изменений в Sass/JS: `npm run watch`.

## Структура

- `routes/web.php` — маршруты приложения;
- `app/Http/Controllers/GodController.php` — обработка запросов и проверка данных;
- `app/Models/God.php` — Eloquent-модель;
- `app/Support/` — обработка имён, категорий и фотографий;
- `database/migrations/` — структура таблиц;
- `database/seeders/DatabaseSeeder.php` — пять исходных богов;
- `resources/views/` — Blade-шаблоны;
- `resources/js/`, `resources/sass/` — исходные JS и Sass;
- `webpack.mix.js` — сборка JS и CSS в `public`.

Папка `vendor`, локальная `.env` и файл `database/database.sqlite` намеренно не добавляются в репозиторий. Данные для первоначального заполнения создаются сидером.
