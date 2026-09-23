# Хранение Android APK

APK хранятся вне public: `storage/app/android-builds/aviatechnik-vX.Y.Z.apk`.
API `/api/android/public/app-config` выбирает наибольшую версию из имен файлов и возвращает ссылку `/api/android/public/download/aviatechnik-vX.Y.Z.apk`.
Скачивание остается доступным без входа, как раньше. Старые ссылки `/app/aviatechnik-vX.Y.Z.apk` обслуживает тот же обработчик. Файл физически находится только в новой папке.

Локально перенесен `aviatechnik-v0.8.0.apk`; SHA-256 до и после переноса совпадает. Файлы APK исключены из Git правилами `storage/app/.gitignore`.

## Перенос на production пользователем

1. Создать `/home/mcxq8917/aviatechnik.ca/storage/app/android-builds` и перенести туда APK из `public/app`, сохранив имена. Права должны позволять PHP читать файлы.
2. Перенести код с сохранением путей:
   - `config/filesystems.php`
   - `app/Http/Controllers/Api/Android/AndroidApiController.php`
   - `routes/api.php`
   - `routes/web.php`
3. Если на production включен кеш конфигурации или маршрутов, после переноса выполнить из каталога приложения `php artisan config:clear` и `php artisan route:clear`, чтобы загрузились новый диск и маршруты.
4. Проверить `/api/android/public/app-config`: версия и новая ссылка должны присутствовать. Проверить скачивание по новой и старой ссылке.

Миграция БД и пересборка APK не нужны. Новые версии размещать только в `storage/app/android-builds`.
Production в рамках этой задачи не изменялся.
