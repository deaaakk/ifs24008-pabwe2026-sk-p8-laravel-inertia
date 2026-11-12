#!/usr/bin/env bash
# Jalankan dari root proyek Laravel baru SETELAH menyalin berkas overlay ini.
set -euo pipefail

echo "==> Dependensi PHP"
composer require inertiajs/inertia-laravel:^2.0 phpoffice/phpspreadsheet

echo "==> Patch config/app.php (force_https)"
if ! grep -q "force_https" config/app.php; then
  sed -i "s/'name' => env('APP_NAME', 'Laravel'),/&\n\n    'force_https' => (bool) env('FORCE_HTTPS', false),/" config/app.php
fi

echo "==> Bersihkan test bawaan"
rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php

echo "==> .env"
sed -i 's/^APP_NAME=.*/APP_NAME="BlogVerse Pro"/' .env
sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/; s/^# *DB_HOST=.*/DB_HOST=127.0.0.1/; s/^DB_HOST=.*/DB_HOST=127.0.0.1/' .env
for kv in DB_PORT=5432 DB_DATABASE=pabwe_p8_blog DB_USERNAME=postgres DB_PASSWORD=postgres; do
  key="${kv%%=*}"
  if grep -qE "^#? *${key}=" .env; then sed -i -E "s|^#? *${key}=.*|${kv}|" .env; else echo "$kv" >> .env; fi
done
grep -q '^OPENAI_API_KEY' .env || printf '\nOPENAI_API_KEY=\nOPENAI_MODEL=gpt-4o-mini\nOPENAI_MAX_POSTS=5\n' >> .env

echo "==> Database"
psql -U postgres -tc "SELECT 1 FROM pg_database WHERE datname='pabwe_p8_blog'" | grep -q 1 \
  || psql -U postgres -c "CREATE DATABASE pabwe_p8_blog;"

echo "==> Frontend"
npm install
npm run build

echo "==> Migrasi, seed, storage"
php artisan key:generate --force
php artisan migrate:fresh --seed --force
php artisan storage:link

echo "Selesai. Jalankan: php artisan serve   (login: admin/admin atau member/member)"
