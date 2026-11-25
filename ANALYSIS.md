# Analisa Lengkap — Zaku Backend API

> Dokumen ini dibuat dari hasil pembacaan langsung source code, routes, services, models, config, dan tests.
> Tanggal analisa: 2026-11-25.

---

## 1. Ringkasan Proyek

| Aspek | Detail |
|-------|--------|
| Nama | `dhabyap/zaku-back-end` |
| Fungsi | REST API tracking pemasukan/pengeluaran (dompet) berbasis Laravel 10 |
| Bahasa | PHP 8.1 |
| Auth | JWT (`tymon/jwt-auth`) |
| Database | MySQL (prod), SQLite in-memory (test) |
| Dokumentasi API | Scribe (`/docs`) |
| Export | PDF via `barryvdh/laravel-dompdf` |
| AI | Chat parser via Groq / Gemini (`/ai/chat`) + parser lokal |
| Status Git | Bersih (tidak ada perubahan uncommitted) |
| Compose/Dev | `vendor/` belum terinstall, `composer` tidak ada di PATH |

**Fitur yang tersedia:** register, login, verify-email, resend, change-password, forgot/reset-password, profile, budget, dashboard, monthly recap, export PDF, transactions (CRUD + stats + categories + chat + AI chat), budgets, recurring transactions, notifications, categories (CRUD admin), insights, changelogs, activity logs, health endpoint.

---

## 2. Arsitektur & Struktur

```
zaku-back-end/
├── app/
│   ├── Http/Controllers/Api/   (17 controller)
│   ├── Http/Requests/          (15 form request)
│   ├── Http/Resources/         (7 resource)
│   ├── Http/Middleware/        (JwtAuthenticate, AdminOnly, dll.)
│   ├── Models/                 (10 model)
│   ├── Services/               (7 service — business logic)
│   └── Traits/ApiResponse.php  (format response konsisten)
├── database/migrations/        (22 migration)
├── routes/api.php              (prefix /api/v1)
├── tests/Feature/ (7) + tests/Unit/ (2)
├── config/ (app, cors, jwt, scribe, mail, dll.)
└── resources/views/ (welcome + scribe + export PDF)
```

**Alur request:**
```
Route (/api/v1/*) → middleware api group → throttle → JwtAuthenticate → Controller → FormRequest → Service → Model
                                                                    ↘ ApiResponse (format {status,message,data})
```

**Praktik yang baik:**
- Business logic dipisah ke `Services/` (TransactionService, DashboardService, BudgetService, dll.)
- Response format konsisten via `ApiResponse` trait
- Transaksi DB dibungkus `DB::transaction` (konsistensi data)
- Form Request validasi per endpoint
- JWT auth middleware menangani expired/invalid token dengan pesan jelas
- Rate limiting sudah terpasang (throttle) untuk endpoint auth
- Tests ada (19 test di `ZakuApiTest`), pakai `RefreshDatabase`
- Activity log untuk audit transaksi (created/updated/deleted)
- Soft deletes + indexes sudah dimigrasi

---

## 3. Analisis Kritis

### 3.1 BUG — Rate limiter `login` tidak didefinisikan (HIGH)

Di `routes/api.php` baris 39:
```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
```

Tetapi di `app/Providers/RouteServiceProvider.php` **hanya didefinisikan**: `api`, `registration`, `verification`, `password-reset`. Limiter **`login` tidak ada**.

Akibat: setiap request login akan melempar error (Laravel `Unable to configure rate limiting...`) → **login gagal / 500**.

Kenapa test tidak menangkap: di `ZakuApiTest::setUp()` dipanggil `$this->withoutMiddleware(ThrottleRequests::class)` sehingga seluruh throttle dimatikan saat test.

**Perbaikan:**
```php
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->input('email', $request->ip()));
});
```
…atau ganti middleware route jadi `throttle:api`.

---

### 3.2 BUG — `login()` memblokir user valid dengan `verification_code` (HIGH)

Di `AuthController::login` baris 125:
```php
if (! $user->isVerified() || $user->verification_code) {
    return $this->forbiddenResponse('Email address is not verified.');
}
```

Kondisi `|| $user->verification_code` berarti: jika kolom `verification_code` pernah terisi (sisa dari flow verifikasi lama), user **tidak bisa login meski `is_verified = true`**. Dengan flag `FEATURE_EMAIL_VERIFICATION=false` (auto-verify), register tidak membuat code — tapi data lama / flow kirim ulang bisa meninggalkan code di kolom ini dan mengunci user.

**Perbaikan:** hapus cek `verification_code` di login, cukup `! $user->isVerified()`. Pastikan `resendVerification` menghapus code setelah dipakai.

---

### 3.3 BUG — `forgotPassword()` kirim email sinkron (MEDIUM)

Di `AuthController::forgotPassword` baris 441:
```php
Mail::raw("Use this password reset token: {$token}", function ($message) use ($user) {
    $message->to($user->email)->subject('Reset your password');
});
```

`Mail::raw` bersifat **sinkron** → request menunggu SMTP selesai (timeout risk di production). Sebaiknya `Mail::queue` / notification queue. Juga tidak ada cek apakah `is_verified` etc. (minor).

---

### 3.4 BUG — `changePassword()` tidak invalidate token lama (MEDIUM)

Setelah password diganti, token JWT lama masih valid sampai TTL-nya habis. Idealnya setelah ganti password, blacklist semua token user (atau pakai `JWTAuth::invalidate()`), supaya sesi lama ikut mati.

---

### 3.5 BUG — Duplikat docblock di `refresh()` (LOW)

Baris 166–200 ada dua docblock bertumpuk (dua blok `/** ... */` berurutan). Tidak fatal, tapi menimbulkan duplikasi dokumentasi Scribe.

---

### 3.6 BUG — `me()` tidak return wallet / budget (LOW)

`AuthController::me` hanya return `id, name, email`. Endpoint `/user/profile` (UserController) lebih lengkap. Tidak masalah, tapi bisa disamakan agar frontend cukup satu call.

---

## 4. Keamanan

| # | Temuan | Level | Catatan |
|---|--------|-------|---------|
| 1 | `config/cors.php`: `allowed_origins => ['*']` | MEDIUM | `supports_credentials=false` jadi JWT (header) tidak bocor, tapi `*` tetap longgar. Batasi ke domain frontend (`zaku.com`, `localhost:3000`). |
| 2 | Rate limiter `login` hilang (lihat 3.1) | HIGH | Efek: login tak terlindungi throttle. |
| 3 | `forgotPassword` tanpa rate limit per email | MEDIUM | Route sudah pakai `throttle:password-reset`, tapi limiter key `input('email', ip)` — OK. Pastikan terpakai. |
| 4 | `changePassword` tidak invalidate JWT | MEDIUM | Sesi lama tetap hidup setelah ganti password. |
| 5 | Password hashing | OK | Model `User` cast `password => 'hashed'` (Laravel 10) → otomatis hash. `changePassword` via forceFill juga kena cast → aman. |
| 6 | Error detail di production | Cek | Pastikan `APP_DEBUG=false` di production; ada `SENTRY_LARAVEL_DSN` opsional untuk error tracking. |
| 7 | `verify-email` / `resend-verification` tanpa throttle route | LOW | Sudah ada `throttle:verification` di resend; verify-email belum. Tambahkan jika perlu. |

---

## 5. Performa & Skalabilitas

| # | Temuan | Level | Catatan |
|---|--------|-------|---------|
| 1 | `Wallet::addBalance/deductBalance` non-atomik | MEDIUM | `balance_cents += x` lalu `save()` — race condition bila request bersamaan. Sebaiknya `DB::raw` / lockForUpdate / `increment()`. |
| 2 | Dashboard banyak query terpisah | MEDIUM | `getDashboard()` menjalankan beberapa `sum()`/`get()` terpisah per bulan. Bisa dioptimasi jadi satu query aggregate. |
| 3 | `TransactionController::index` — `count` + 2 `sum` terpisah | LOW | 3 query per request; bisa digabung. Sudah pakai index `transaction_date`. |
| 4 | `DashboardService::getMonthlyRecap` — load semua transaksi bulan lalu filter | LOW | `->get()` penuh lalu `->where()` di collection; untuk volume besar lebih baik filter di SQL. |
| 5 | `ExportController::monthlyPdf` sinkron (DOMPDF) | LOW | PDF dibuat di request; untuk data besar bisa lambat. Opsional: queue. |
| 6 | Email sinkron (3.3) | MEDIUM | Blocking SMTP; gunakan queue. |
| 7 | Queue/cache driver default `file`/`sync` | INFO | Production disarankan Redis (config sudah ada placeholder). |

---

## 6. Kualitas Kode & DX

- **Tidak ada CI/CD** terlihat (tidak ada GitHub Actions). Bisa ditambahkan workflow jalankan `composer install` + `php artisan test` + Pint.
- **`vendor/` belum terinstall** dan `composer` tidak ada di PATH → test belum bisa dijalankan di mesin ini. Setup: install Composer, `composer install`, `php artisan test`.
- **`tests/Feature/ZakuApiTest`** mematikan throttle (`withoutMiddleware`) → bug rate limiter (3.1) tak terdeteksi. Test terpisah sebaiknya tidak mematikan throttle untuk endpoint auth.
- **`.env.example`** sudah aman (`MAIL_MAILER=log`, `FEATURE_EMAIL_VERIFICATION=false`), tapi `JWT_SECRET` kosong (wajar, harus `jwt:secret`).
- **Issue #1** (mailpit di production) sudah ditangani via default `log` + auto-verify; sisa: verifikasi ulang di production dan aktifkan kembali email verification saat siap.
- **Docblock ganda** di `refresh()` perlu dirapikan.
- **Komentar sisa** `// Changed to LAINNYA` berulang di beberapa tempat — pertimbangkan hapus / rapikan.

---

## 7. Prioritas Enhancement (Rekomendasi)

### Prioritas 1 — Segera (Bug blocking / keamanan)
1. **Fix rate limiter `login`** — tambahkan `RateLimiter::for('login', ...)` di `RouteServiceProvider` (atau pakai `throttle:api`). *(Login bisa gagal 500 saat ini.)*
2. **Fix `login()` blokir `verification_code`** — hapus cek `|| $user->verification_code`.
3. **Batasi CORS origin** ke domain frontend.
4. **Invalidate JWT setelah change-password.**

### Prioritas 2 — Kualitas / performa
5. **Queue email** (`Mail::queue`) untuk forgot-password & verification.
6. **Atomic wallet balance** (`increment`/`lockForUpdate`) untuk hindari race.
7. **Rapikan duplikasi docblock** `refresh()`.
8. **Install composer + jalankan test** untuk baseline hijau; tambah test yang tidak mematikan throttle.

### Prioritas 3 — Fitur / produksi
9. **CI/CD** GitHub Actions (test + Pint).
10. **Redis cache/queue** di production.
11. **Re-enable email verification** saat siap (flag sudah ada).
12. **Sentry** error tracking (DSN sudah disiapkan di `.env.example`).
13. **Optimasi dashboard query** jadi agregat tunggal.

---

## 8. Checklist Cepat

- [ ] Tambah `RateLimiter::for('login')`
- [ ] Hapus cek `verification_code` di `AuthController::login`
- [ ] Batasi `cors.php` allowed_origins
- [ ] Invalidate token saat `changePassword`
- [ ] `Mail::queue` untuk email
- [ ] Atomic balance di `Wallet`
- [ ] Hapus docblock duplikat `refresh()`
- [ ] `composer install` + `php artisan test`
- [ ] Tambah GitHub Actions CI
- [ ] Konfigurasi Redis + Sentry di production

---

## 9. Limitasi Analisa Ini

- **Tidak bisa menjalankan test/build** — `composer` tidak ada di PATH, `vendor/` belum terinstall (PHP 8.1 tersedia via Laragon).
- **Tidak membaca production `.env`** — hanya `.env.example` dan source. Kondisi mail/JWT/cors di server production tidak terverifikasi.
- **Tidak memeriksa frontend** — frontend di luar folder ini (disebut terpisah, `zaku.com` / `localhost:3000`).
- **Tidak menguji endpoint langsung** — hanya pembacaan kode statis.
