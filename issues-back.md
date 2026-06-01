# Zaku Issues Backlog

Last updated: 2026-05-20

## Status Singkat

Backend Zaku sudah punya fondasi API utama: auth, user profile, budget, dashboard, transaksi pemasukan/pengeluaran, chat parser, AI chat fallback, dokumentasi Scribe, dan README setup local. Test backend terakhir lulus: 8 tests, 144 assertions.

Langkah berikutnya yang saya sarankan adalah mengunci kontrak backend-frontend, memperbaiki edge case auth, dan memastikan product scope tetap fokus ke tracking uang masuk dan uang keluar. Zaku bukan aplikasi kirim uang, top up, withdraw, atau wallet transfer.

## Backend Issues

### BE-001 - Finalisasi API contract untuk frontend

Priority: High
Status: Todo
Owner: Backend + Frontend

Problem:
Frontend harus punya satu kontrak response yang pasti. Saat ini backend memakai format umum:

```json
{
  "success": true,
  "status": "success",
  "data": {},
  "message": "..."
}
```

Scope:
- Pastikan semua endpoint memakai response wrapper yang sama.
- Dokumentasikan field penting untuk auth: `data.token`, `data.user`.
- Dokumentasikan error validation: status code, message, dan lokasi errors.
- Pastikan frontend tidak membaca format lama seperti `access_token` jika backend mengirim `token`.

Acceptance criteria:
- [ ] Semua endpoint penting punya contoh response di Scribe atau README.
- [ ] Frontend dan backend sepakat membaca response dari `response.data.data`.
- [ ] Login, register, transaksi, dashboard, budget, dan profile sudah dites manual dari frontend.

### BE-002 - Tambahkan endpoint reset password final

Priority: High
Status: Todo
Owner: Backend

Problem:
Backend sudah punya `POST /api/auth/forgot-password`, tetapi belum ada endpoint public untuk menyelesaikan reset password memakai token yang dikirim ke email/log.

Proposed endpoint:

```http
POST /api/auth/reset-password
```

Request:

```json
{
  "email": "user@example.com",
  "token": "reset-token",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Acceptance criteria:
- [ ] Token reset password divalidasi dari tabel `password_reset_tokens`.
- [ ] Password baru di-hash dan tersimpan.
- [ ] Token reset dihapus setelah berhasil dipakai.
- [ ] Test feature untuk success, token invalid, token expired, dan password confirmation.

### BE-003 - Putuskan aturan login untuk email yang belum verified

Priority: High
Status: Todo
Owner: Backend + Product

Problem:
Dokumentasi controller menyebut login hanya untuk email verified, tetapi implementasi saat ini belum memblokir user yang `is_verified = false`.

Decision needed:
- Opsi A: user belum verified boleh login, tetapi fitur tertentu dibatasi.
- Opsi B: user belum verified tidak boleh login dan harus verify email dulu.

Acceptance criteria:
- [ ] Product memilih salah satu aturan.
- [ ] Implementasi backend sesuai aturan.
- [ ] Frontend menampilkan pesan yang jelas jika email belum verified.
- [ ] Test login user unverified ditambahkan.

### BE-004 - Tambahkan pagination dan limit untuk daftar transaksi pemasukan/pengeluaran

Priority: Medium
Status: Todo
Owner: Backend

Problem:
Frontend memanggil `/transactions?limit=5` untuk recent transactions, tetapi backend `TransactionController@index` belum membaca query `limit`.

Scope:
- Support `limit`, `page`, dan filter transaksi.
- Batasi `limit` maksimum, misalnya 100.
- Jaga response tetap mudah dipakai frontend.

Acceptance criteria:
- [ ] `/api/transactions?limit=5` hanya mengembalikan maksimal 5 transaksi.
- [ ] `/api/transactions?page=2&limit=10` berjalan jika pagination dipakai.
- [ ] Existing filter `SEMUA`, `PEMASUKAN`, `PENGELUARAN`, dan kategori tetap berjalan.
- [ ] Test ditambahkan.

### BE-005 - Harden CORS untuk production

Priority: Medium
Status: Todo
Owner: Backend/DevOps

Problem:
`config/cors.php` saat ini memakai `allowed_origins = ['*']`. Ini nyaman untuk local, tetapi terlalu longgar untuk production.

Scope:
- Tambahkan env `FRONTEND_URL` dan `FRONTEND_URL_DEV`.
- Gunakan daftar origin dari env.
- Tetap izinkan local development.

Acceptance criteria:
- [ ] Local frontend tetap bisa akses backend.
- [ ] Production hanya mengizinkan domain frontend resmi.
- [ ] README deployment diperbarui.

### BE-006 - Regenerate dokumentasi Scribe setelah contract final

Priority: Medium
Status: Todo
Owner: Backend

Problem:
Dokumentasi Scribe perlu dibuat ulang setelah perubahan kontrak API agar `/docs`, Postman collection, dan OpenAPI spec sinkron.

Acceptance criteria:
- [ ] Jalankan generate docs Scribe.
- [ ] `/docs` menampilkan nama Zaku dan response terbaru.
- [ ] `storage/app/scribe/collection.json` dan `openapi.yaml` update.

### BE-007 - Tambahkan CI test minimal

Priority: Medium
Status: Todo
Owner: Backend/DevOps

Problem:
Test sudah ada, tetapi belum terlihat pipeline otomatis untuk memastikan PR tidak merusak API.

Acceptance criteria:
- [ ] GitHub Actions menjalankan `composer install` dan `php artisan test`.
- [ ] Pipeline memakai SQLite in-memory.
- [ ] Badge/status CI bisa dilihat di GitHub.

### BE-008 - Hapus atau nonaktifkan fitur wallet transfer dari product scope

Priority: High
Status: Todo
Owner: Backend + Product

Problem:
Zaku hanya untuk tracking uang masuk dan keluar. Endpoint seperti top up, withdraw, dan send money membuat aplikasi terlihat seperti e-wallet/transfer uang, padahal bukan itu scope produk.

Endpoints yang perlu diputuskan:
- `GET /api/wallet/balance`
- `POST /api/wallet/topup`
- `POST /api/wallet/withdraw`
- `POST /api/wallet/send`

Recommended direction:
- Ganti konsep "wallet balance" menjadi ringkasan cashflow: total pemasukan, total pengeluaran, dan net balance berdasarkan catatan transaksi.
- Hapus/hide endpoint mutasi wallet yang menyerupai transaksi finansial sungguhan.
- Jika tetap butuh saldo estimasi, hitung dari transaksi income/expense, bukan dari top up/withdraw/send.

Acceptance criteria:
- [ ] Product memilih apakah endpoint wallet dihapus, deprecated, atau disembunyikan dari frontend.
- [ ] Frontend tidak lagi menampilkan Top Up, Withdraw, atau Send Money.
- [ ] Dashboard memakai istilah "saldo tercatat" atau "net balance" dari income minus expense.
- [ ] Test disesuaikan agar fokus ke income/expense tracking.

## Frontend Issues

### FE-001 - Ganti seluruh branding lama menjadi Zaku dan hapus istilah e-wallet

Priority: High
Status: Todo
Owner: Frontend

Problem:
Masih ada teks visible lama seperti `DOMPET` di beberapa Blade/CSS/PRD frontend.

Known files:
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/chat/index.blade.php`
- `resources/views/layouts/*.blade.php`
- `resources/css/app.css`
- `PRD-Frontend.md`
- `TASKS.md`

Acceptance criteria:
- [ ] Semua teks produk yang terlihat user memakai "Zaku".
- [ ] Tidak ada copy UI yang membuat Zaku terlihat seperti aplikasi kirim uang/top up/withdraw.
- [ ] Nama folder lama tidak wajib diubah dulu jika berisiko besar, tetapi UI dan docs harus bersih.
- [ ] Tidak ada hasil `rg "DOMPET|Dompet|dompet"` pada file user-facing, kecuali histori yang memang sengaja disimpan.

### FE-002 - Set base URL API local ke backend Zaku

Priority: High
Status: Todo
Owner: Frontend

Problem:
Frontend `.env.example` masih kosong untuk `VITE_API_BASE_URL`, sementara README frontend menyebut port `8001`. Backend README terbaru memakai `http://127.0.0.1:8000/api`.

Scope:
- Update `.env.example`.
- Update README frontend.
- Pastikan api-client memakai URL backend yang benar.

Acceptance criteria:
- [ ] `.env.example` berisi `VITE_API_BASE_URL=http://127.0.0.1:8000/api`.
- [ ] Login dari frontend local berhasil ke backend local.
- [ ] Tidak ada request yang jatuh ke `/api` milik frontend Laravel kecuali memang internal.

### FE-003 - Sesuaikan refresh token flow dengan backend JWT

Priority: High
Status: Todo
Owner: Frontend + Backend

Problem:
Frontend `api-client.js` mencoba refresh token dengan body `{ refresh_token }`, sedangkan backend `POST /api/auth/refresh` memakai Bearer token aktif lewat middleware `jwt.auth`.

Possible solution:
- Simpan hanya `access_token` jika backend tidak memakai refresh token terpisah.
- Saat 401, clear session dan redirect ke login.
- Jika ingin refresh, sepakati kontrak baru backend.

Acceptance criteria:
- [ ] Tidak ada retry refresh yang salah format.
- [ ] Token expired membuat user diarahkan ke `/login?session=expired`.
- [ ] Logout membersihkan `access_token`, `refresh_token`, dan user session.

### FE-004 - Perbaiki request update budget

Priority: High
Status: Todo
Owner: Frontend

Problem:
Backend endpoint `PUT /api/user/budget` mengharapkan field `monthly_budget`, tetapi frontend profile mengirim `{ amount: ... }`.

File:
- `resources/views/dashboard/profile.blade.php`

Acceptance criteria:
- [ ] Request menjadi `{ "monthly_budget": 4000000 }`.
- [ ] Setelah sukses, UI budget memakai response terbaru dari backend.
- [ ] Error validation ditampilkan dengan toast yang jelas.

### FE-005 - Lengkapi forgot/reset password UI

Priority: Medium
Status: Todo
Owner: Frontend

Problem:
Frontend sudah punya forgot password page, tetapi perlu flow final setelah backend punya `POST /api/auth/reset-password`.

Acceptance criteria:
- [ ] User bisa input email untuk minta token reset.
- [ ] User bisa input token, password baru, dan confirmation.
- [ ] Success redirect ke login.
- [ ] Error token invalid/expired tampil jelas.

### FE-006 - E2E smoke test manual untuk flow utama tracking keuangan

Priority: Medium
Status: Todo
Owner: Frontend + QA

Flow yang harus dites:
- [ ] Register.
- [ ] Verify email.
- [ ] Login.
- [ ] Dashboard tampil.
- [ ] Tambah transaksi pengeluaran manual/chat.
- [ ] Tambah transaksi pemasukan manual/chat.
- [ ] Lihat detail transaksi.
- [ ] Hapus transaksi.
- [ ] Update profile.
- [ ] Update budget.
- [ ] Logout.

### FE-007 - Hapus menu Top Up, Withdraw, dan Send Money dari frontend

Priority: High
Status: Todo
Owner: Frontend

Problem:
Menu Top Up, Withdraw, dan Send Money tidak sesuai dengan arah produk. Zaku hanya mencatat uang masuk dan uang keluar, bukan memindahkan uang.

Scope:
- Hapus/hide entry navigation ke halaman wallet transfer.
- Jangan tampilkan tombol atau CTA yang membuat user mengira bisa melakukan transaksi uang sungguhan.
- Jika ada halaman lama masih tersisa, arahkan ulang atau beri status deprecated sampai dihapus.

Acceptance criteria:
- [ ] Tidak ada menu "Top Up".
- [ ] Tidak ada menu "Withdraw/Tarik Saldo".
- [ ] Tidak ada menu "Send Money/Kirim Uang".
- [ ] User diarahkan ke flow catat pemasukan/pengeluaran.

## Urutan Kerja yang Saya Sarankan

1. FE-001, FE-007, dan FE-002 supaya frontend sudah bernama Zaku, tidak terlihat seperti e-wallet, dan tersambung ke backend local.
2. FE-004 karena ini mismatch request yang langsung terlihat user.
3. BE-008 untuk merapikan scope backend dari wallet transfer ke tracking income/expense.
4. BE-003 dan FE-003 untuk merapikan aturan auth.
5. BE-002 dan FE-005 untuk melengkapi reset password.
6. BE-004 supaya dashboard/recent transaction lebih efisien.
7. BE-005, BE-006, BE-007 untuk siap production dan kolaborasi tim.
