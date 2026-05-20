# Zaku Backend API

Backend API untuk aplikasi Zaku, yaitu aplikasi pencatatan keuangan digital. Project ini dibuat dengan Laravel dan menyediakan API untuk login, register, wallet, transaksi, dashboard, budget bulanan, dan pencatatan transaksi lewat chat.

README ini dibuat untuk dua tipe pembaca:

- Orang awam/non-IT: supaya paham project ini untuk apa dan cara menjalankannya secara minimal.
- IT/developer: supaya bisa setup, menjalankan, testing, dan integrasi API di local.

## Gambaran Singkat

Project ini adalah backend, bukan aplikasi tampilan utama. Artinya project ini berjalan sebagai server API yang akan dipanggil oleh frontend/mobile app.

Contoh fungsi yang sudah tersedia:

- Register dan login user.
- Verifikasi email dengan kode.
- JWT Bearer token untuk akses endpoint yang butuh login.
- Wallet: cek saldo, top up, withdraw, kirim uang.
- Transaksi: daftar transaksi, statistik, kategori, tambah transaksi lewat chat.
- Dashboard ringkasan keuangan.
- Dokumentasi API otomatis lewat Scribe di `/docs`.

## Teknologi

- PHP 8.1 atau lebih baru.
- Laravel 10.
- Composer.
- MySQL untuk penggunaan local normal.
- SQLite untuk testing.
- JWT Auth dengan `tymon/jwt-auth`.
- Scribe untuk dokumentasi API.
- Node.js dan NPM hanya diperlukan jika ingin menjalankan asset Vite.

## Kebutuhan Sebelum Setup

Minimal yang perlu terpasang di komputer:

- PHP 8.1+.
- Composer.
- MySQL/MariaDB.
- Git.

Opsional:

- Node.js 18+ dan NPM, jika ingin menjalankan `npm run dev`.
- Postman/Insomnia, jika ingin mencoba API lebih nyaman.
- Mailpit atau SMTP lain, jika ingin menguji email sungguhan.

Untuk Windows, cara paling mudah biasanya memakai Laragon, XAMPP, atau instalasi PHP + Composer manual. Untuk macOS/Linux, bisa memakai PHP dari package manager, Homebrew, Docker, atau environment lain yang biasa dipakai tim.

## Setup Local Paling Minimal

Ikuti langkah ini dari folder project:

```bash
cd backend
```

Jika posisi terminal sudah di folder ini, lanjut ke langkah berikutnya.

### 1. Install dependency PHP

```bash
composer install
```

Jika folder `vendor` sudah ada, perintah ini tetap aman dijalankan untuk memastikan dependency lengkap.

### 2. Buat file `.env`

```bash
cp .env.example .env
```

Di Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 3. Buat application key

```bash
php artisan key:generate
```

### 4. Buat JWT secret

```bash
php artisan jwt:secret
```

Pilih `yes` jika diminta overwrite `JWT_SECRET`.

### 5. Siapkan database MySQL

Buat database kosong bernama:

```text
zaku_api
```

Contoh lewat MySQL CLI:

```sql
CREATE DATABASE zaku_api;
```

Lalu cek bagian database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zaku_api
DB_USERNAME=root
DB_PASSWORD=
```

Sesuaikan `DB_USERNAME` dan `DB_PASSWORD` dengan komputer masing-masing.

### 6. Jalankan migrasi dan seeder

```bash
php artisan migrate --seed
```

Perintah ini membuat tabel database dan mengisi data awal, termasuk kategori transaksi dan akun demo.

### 7. Jalankan server local

```bash
php artisan serve
```

Jika berhasil, API berjalan di:

```text
http://127.0.0.1:8000
```

Dokumentasi API bisa dibuka di:

```text
http://127.0.0.1:8000/docs
```

OpenAPI spec tersedia di:

```text
http://127.0.0.1:8000/docs.openapi
```

## Akun Demo

Setelah menjalankan `php artisan migrate --seed`, akun demo tersedia:

```text
Email: demo@zaku.test
Password: password
```

Gunakan akun ini untuk login dan mendapatkan JWT token.

## Cara Mencoba API

### Login

Request:

```http
POST http://127.0.0.1:8000/api/auth/login
Accept: application/json
Content-Type: application/json
```

Body:

```json
{
  "email": "demo@zaku.test",
  "password": "password"
}
```

Response login akan berisi `token`. Simpan token itu untuk endpoint yang butuh login.

### Mengakses Endpoint yang Butuh Login

Tambahkan header:

```http
Authorization: Bearer TOKEN_DARI_LOGIN
Accept: application/json
```

Contoh cek saldo:

```http
GET http://127.0.0.1:8000/api/wallet/balance
Authorization: Bearer TOKEN_DARI_LOGIN
Accept: application/json
```

## Endpoint Utama

Base URL local:

```text
http://127.0.0.1:8000/api
```

Endpoint public:

| Method | Endpoint | Fungsi |
| --- | --- | --- |
| POST | `/auth/register` | Register user baru |
| POST | `/auth/login` | Login dan ambil JWT token |
| POST | `/auth/verify-email` | Verifikasi email |
| POST | `/auth/resend-verification` | Kirim ulang kode verifikasi |
| POST | `/auth/forgot-password` | Minta token reset password |

Endpoint yang membutuhkan JWT token:

| Method | Endpoint | Fungsi |
| --- | --- | --- |
| GET | `/auth/me` | Ambil data user login |
| POST | `/auth/refresh` | Refresh token |
| POST | `/auth/logout` | Logout |
| POST | `/auth/change-password` | Ganti password |
| GET | `/user/profile` | Ambil profil dan statistik user |
| PUT | `/user/profile` | Update profil |
| PUT | `/user/budget` | Update budget bulanan |
| GET | `/dashboard` | Ambil dashboard keuangan |
| GET | `/transactions` | Ambil daftar transaksi |
| GET | `/transactions/stats` | Ambil statistik transaksi |
| GET | `/transactions/categories` | Ambil ringkasan kategori |
| GET | `/transactions/{id}` | Detail transaksi |
| DELETE | `/transactions/{id}` | Hapus transaksi |
| POST | `/transactions/chat` | Catat transaksi dari pesan chat parser local |
| POST | `/ai/chat` | Catat transaksi dari chat dengan AI/fallback local |
| GET | `/wallet/balance` | Cek saldo wallet |
| POST | `/wallet/topup` | Top up wallet |
| POST | `/wallet/withdraw` | Withdraw wallet |
| POST | `/wallet/send` | Kirim uang ke user lain |

Dokumentasi lengkap dengan contoh request/response ada di `/docs`.

## Konfigurasi `.env` Penting

Minimal untuk local:

```env
APP_NAME="Zaku Backend API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
SCRIBE_BASE_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zaku_api
DB_USERNAME=root
DB_PASSWORD=

JWT_SECRET=isi_dari_php_artisan_jwt_secret
JWT_ALGO=HS256
```

Untuk email local, default `.env.example` memakai Mailpit:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

Jika tidak memakai Mailpit, untuk local bisa diganti menjadi:

```env
MAIL_MAILER=log
```

Dengan `MAIL_MAILER=log`, isi email akan masuk ke file log Laravel, bukan dikirim ke inbox.

## Fitur Chat dan AI

Ada dua endpoint chat:

- `/api/transactions/chat`: parser local, tidak butuh API key AI.
- `/api/ai/chat`: mencoba AI provider jika API key tersedia, lalu fallback ke parser local.

Konfigurasi opsional:

```env
GROQ_API_KEY=
GROQ_MODEL=llama-3.1-8b-instant
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash
```

Untuk local minimal, bagian ini boleh dikosongkan.

## Menjalankan Test

```bash
php artisan test
```

Testing memakai SQLite in-memory dari `phpunit.xml`, jadi tidak mengubah database MySQL local.

## Perintah yang Sering Dipakai Developer

```bash
composer install
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan migrate --seed
php artisan migrate:fresh --seed
php artisan serve
php artisan route:list --path=api
php artisan test
```

Jika perlu asset frontend bawaan Laravel:

```bash
npm install
npm run dev
```

Untuk backend API saja, `npm install` tidak wajib.

## Struktur Folder Penting

```text
app/
  Http/Controllers/Api/    Controller API
  Http/Requests/           Validasi request
  Http/Resources/          Format response resource
  Models/                  Model database
  Services/                Logic bisnis dan parser transaksi
  Traits/ApiResponse.php   Format response API konsisten

database/
  migrations/              Struktur tabel database
  seeders/                 Data awal/demo

routes/
  api.php                  Semua route API utama

config/
  jwt.php                  Konfigurasi JWT
  scribe.php               Konfigurasi dokumentasi API
```

## Format Response Umum

Sebagian besar endpoint memakai format:

```json
{
  "status": "success",
  "message": "Pesan response",
  "data": {}
}
```

Jika error, response biasanya:

```json
{
  "status": "error",
  "message": "Pesan error",
  "errors": {}
}
```

## Troubleshooting

### `composer install` gagal

Pastikan PHP dan Composer sudah terpasang:

```bash
php -v
composer -V
```

Pastikan extension PHP yang umum untuk Laravel aktif, seperti `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, dan `fileinfo`.

### `php artisan migrate --seed` gagal koneksi database

Cek MySQL sudah menyala dan konfigurasi `.env` benar:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zaku_api
DB_USERNAME=root
DB_PASSWORD=
```

Jika mengganti `.env`, jalankan:

```bash
php artisan config:clear
```

### Error `JWT secret is not set`

Jalankan:

```bash
php artisan jwt:secret
php artisan config:clear
```

### Endpoint protected selalu `Unauthorized`

Pastikan header token benar:

```http
Authorization: Bearer TOKEN_DARI_LOGIN
Accept: application/json
```

Token harus berasal dari endpoint `/api/auth/login`.

### Dokumentasi `/docs` tidak sesuai URL local

Cek `.env`:

```env
SCRIBE_BASE_URL=http://127.0.0.1:8000
```

Lalu bersihkan config:

```bash
php artisan config:clear
```

## Catatan untuk Deployment

Untuk production, minimal ubah:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-api-anda.com
SCRIBE_BASE_URL=https://domain-api-anda.com
```

Gunakan database production, SMTP production, HTTPS, dan `JWT_SECRET` yang kuat. Jangan commit file `.env` ke repository.

## Dokumentasi Tambahan

Beberapa dokumen project lain tersedia di repository:

- `PRD-Backend.md`: kebutuhan dan rancangan produk backend.
- `TASK_LIST.md`: daftar task project.
- `CHANGELOG.md`: catatan perubahan.
- `GIT_WORKFLOW.md`: standar git workflow.
- `docs/`: dokumentasi internal issue, implementation, dan pull request.
