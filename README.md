# BlogVerse Pro — pabwe2026-sk-p8 (Laravel 13 · Inertia.js · React · PostgreSQL)

Platform publikasi artikel **multi-tier** dengan arsitektur **Clean Architecture & Feature-First**.

| Tingkat | URL | Hak akses |
|---|---|---|
| 1. Publik | `/`, `/articles`, `/articles/{slug}`, `/login`, `/register` | Guest — baca, cari, filter kategori. Sticky top navbar, tanpa sidebar |
| 2. Anggota (Penulis) | `/dashboard/posts`, `/dashboard/posts/{post}`, `/profile` | CRUD artikel sendiri, publish/draf, cover, ekspor/impor Excel, generator AI |
| 3. Admin | `/admin/users`, `/admin/posts`, `/admin/users/{user}/posts` | Monitoring read-only + ekspor Excel |

## Instalasi

Prasyarat: PHP ≥ 8.4 (ext: gd, zip, mbstring, pdo_pgsql, pdo_sqlite), Composer, Node.js, PostgreSQL ≥ 17.

```bash
laravel new ifs24008-pabwe2026-sk-p8-laravel-inertia --database=pgsql --phpunit --no-node --no-boost --no-interaction
cd ifs24008-pabwe2026-sk-p8-laravel-inertia

# salin seluruh isi zip ini ke root proyek (timpa berkas yang ada), lalu:
bash scripts/bootstrap.sh
php artisan serve
```

`bootstrap.sh` melakukan: `composer require`, patch `config/app.php` (`force_https`), buat DB `pabwe_p8_blog`,
atur `.env`, `npm install && npm run build`, `migrate:fresh --seed`, `storage:link`.

Akun seeder: **admin / admin** · **member / member**. Isi `OPENAI_API_KEY` di `.env` untuk fitur AI.

## Pengujian

```bash
php artisan test                              # 12 kelas test, SQLite in-memory
php artisan test --coverage --min=100         # butuh ekstensi pcov atau xdebug (coverage mode)
composer audit
```

## Keputusan desain

* **Feature-First**: kode dikelompokkan per fitur (`Admin`, `Ai`, `Auth`, `Post`, `Profile`), bukan per jenis berkas.
* **Repository + Interface** untuk akses data `Post`; `AiClientInterface` memisahkan controller dari OpenAI (mudah di-mock).
* **Kepemilikan artikel** ditegakkan middleware `EnsurePostOwnership` → `PostPolicy@manage`. Admin sengaja *tidak* bisa mengubah artikel orang lain.
* **Pencarian** memakai `LOWER(col) LIKE ?` agar portabel (PostgreSQL & SQLite untuk test).
* **Excel**: sel ditulis eksplisit sebagai string (mencegah formula injection `=...`); ekspor penulis = format impor (round-trip).
* **AI**: respons OpenAI dipaksa JSON; hasil disimpan sebagai **draf** setelah penulis memilih.
* Penambahan kecil di luar daftar rute spec: `PUT /profile` (agar form profil bisa disimpan) dan filter `?status=` (kartu statistik interaktif).
* `PublicLayout.jsx` & `Components/` ditambahkan di luar pohon folder spec (layout publik + komponen bersama).

## Deploy
Set `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `APP_URL=https://…`, lalu `php artisan migrate --force`, `php artisan storage:link`, `npm run build`.
