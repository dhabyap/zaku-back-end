# Issues - DOMPET Backend API

Dokumen ini adalah panduan kerja untuk programmer junior dalam membangun backend aplikasi pencatat keuangan **DOMPET**. Fokus utama: RESTful API, data aggregation untuk dashboard, pencatatan transaksi dari chat/input teks dengan AI NLP atau fallback parser, dan response JSON yang siap dikonsumsi frontend brutalist dynamic UI.

> Catatan: nominal uang wajib dikembalikan sebagai integer murni, contoh `65000`, bukan `"Rp 65.000"` dan bukan `65000.00`.

---

## Global API Standard

Semua endpoint wajib menggunakan format response berikut:

```json
{
  "success": true,
  "data": {},
  "message": "OK"
}
```

Untuk error validasi:

```json
{
  "success": false,
  "data": {
    "errors": {
      "email": ["Email wajib diisi."]
    }
  },
  "message": "Validation failed"
}
```

Aturan umum:
- Gunakan auth JWT yang sudah ada di project, atau Sanctum jika project diputuskan pindah.
- Endpoint private wajib memakai middleware auth.
- Semua amount/budget/balance dikembalikan sebagai integer.
- Tanggal transaksi disimpan sebagai datetime, tetapi response frontend boleh berisi `date_formatted`.
- Gunakan timezone aplikasi yang konsisten, disarankan `Asia/Jakarta`.
- Jangan expose field sensitif seperti password, token internal, atau verification code.

---

## Target Architecture

Struktur yang disarankan:

```text
app/
  Http/
    Controllers/Api/
      AuthController.php
      UserController.php
      DashboardController.php
      TransactionController.php
    Requests/
      RegisterRequest.php
      LoginRequest.php
      UpdateProfileRequest.php
      UpdateBudgetRequest.php
      ChatTransactionRequest.php
    Resources/
      UserProfileResource.php
      TransactionResource.php
      DashboardResource.php
  Models/
    User.php
    Wallet.php
    Category.php
    Transaction.php
  Services/
    DashboardService.php
    TransactionParserService.php
    TransactionService.php
  Traits/
    ApiResponse.php
database/
  migrations/
  seeders/
    DatabaseSeeder.php
    CategorySeeder.php
    DompetDemoSeeder.php
routes/
  api.php
```

Tanggung jawab:
- **Controller** hanya handle request, auth user, validasi, panggil service, return response.
- **Service** berisi business logic seperti kalkulasi dashboard, parsing chat, dan create transaction.
- **Resource** menjaga bentuk JSON agar konsisten dengan kebutuhan frontend.
- **Seeder** membuat data demo minimal 2 bulan terakhir agar dashboard dan grafik langsung terisi.

---

## Database Schema

### Issue DB-001: Sesuaikan tabel `users`

Tambahkan field budget pada user.

Kolom wajib:
- `id`
- `full_name` atau `name` sesuai convention project saat ini
- `email`
- `password`
- `monthly_budget` decimal/integer default `0`
- `is_verified`
- `last_login_at`
- `created_at`
- `updated_at`

Acceptance criteria:
- [ ] User bisa menyimpan budget bulanan.
- [ ] Register tetap berjalan.
- [ ] Login tetap mengembalikan token dan profil user.
- [ ] Nama di response frontend menggunakan key `name`, walaupun database memakai `full_name`.

### Issue DB-002: Buat tabel `categories`

Kategori digunakan untuk grouping transaksi dan icon frontend.

Kolom:
- `id`
- `name` string, unique, uppercase, contoh `MAKANAN`
- `icon` string, contoh `☕`
- `type` enum: `income`, `expense`, `both`
- `keywords` json nullable, contoh `["kopi", "starbucks", "makan", "nasi"]`
- `created_at`
- `updated_at`

Data awal wajib:
- `MAKANAN` icon `☕`, type `expense`
- `TRANSPORT` icon `🚗`, type `expense`
- `BELANJA` icon `🛒`, type `expense`
- `TAGIHAN` icon `🧾`, type `expense`
- `HIBURAN` icon `🎮`, type `expense`
- `GAJI` icon `💰`, type `income`
- `LAINNYA` icon `📌`, type `both`

Acceptance criteria:
- [ ] Category model dibuat.
- [ ] Seeder category dibuat.
- [ ] Transaction punya relasi ke category.

### Issue DB-003: Sesuaikan tabel `transactions`

Project saat ini sudah punya tabel transactions. Sesuaikan agar cocok untuk DOMPET.

Kolom wajib:
- `id`
- `wallet_id` foreign key ke wallets
- `category_id` nullable/foreign key ke categories
- `type` enum: `income`, `expense`
- `amount` decimal/integer, disimpan positif
- `description` text
- `source` enum: `manual`, `chat`, default `manual`
- `raw_message` text nullable, isi pesan asli untuk transaksi dari chat
- `status` enum: `completed`, `pending`, `failed`, default `completed`
- `transaction_date` datetime indexed
- `reference_id` nullable
- `created_at`
- `updated_at`

Acceptance criteria:
- [ ] Type transaksi memakai `income` dan `expense`, bukan `debit`/`credit` pada API baru.
- [ ] Amount disimpan positif, arah transaksi ditentukan oleh `type`.
- [ ] Semua query dashboard hanya menghitung transaksi `completed`.
- [ ] Index tersedia untuk `wallet_id`, `category_id`, `type`, dan `transaction_date`.

---

## REST API Issues

## Issue API-001: Auth Register dan Login

Endpoint:
- `POST /api/auth/register`
- `POST /api/auth/login`

Register payload:

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Login payload:

```json
{
  "email": "budi@example.com",
  "password": "password123"
}
```

Response sukses:

```json
{
  "success": true,
  "data": {
    "token": "jwt_or_sanctum_token",
    "user": {
      "id": 1,
      "name": "Budi Santoso",
      "email": "budi@example.com",
      "avatar_initial": "B"
    }
  },
  "message": "Login berhasil"
}
```

Tasks:
- [ ] Buat/ubah request validation untuk register agar menerima `name`.
- [ ] Simpan ke `full_name` jika database masih memakai `full_name`.
- [ ] Buat wallet default setelah user register.
- [ ] Return token dan user profile minimal.
- [ ] Tambahkan feature test register dan login.

Acceptance criteria:
- [ ] Register valid mengembalikan HTTP 201.
- [ ] Login valid mengembalikan HTTP 200.
- [ ] Password salah mengembalikan HTTP 401.
- [ ] Response mengikuti global API standard.

---

## Issue API-002: User Profile dan Budget

Endpoint:
- `GET /api/user/profile`
- `PUT /api/user/profile`
- `PUT /api/user/budget`

`GET /api/user/profile` response:

```json
{
  "success": true,
  "data": {
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "avatar_initial": "B",
    "member_status": "MEMBER AKTIF",
    "stats": {
      "total_transactions": 42,
      "transactions_this_month": 12,
      "largest_transaction_amount": 450000,
      "unique_categories_used": 6
    },
    "budget": {
      "monthly_budget": 3000000,
      "budget_used": 1250000,
      "budget_remaining": 1750000,
      "budget_percentage": 41
    }
  },
  "message": "Profil berhasil diambil"
}
```

`PUT /api/user/profile` payload:

```json
{
  "name": "Budi Santoso",
  "email": "budi@example.com"
}
```

`PUT /api/user/budget` payload:

```json
{
  "monthly_budget": 3000000
}
```

Calculation notes:
- `budget_used` = total expense bulan berjalan.
- `budget_remaining` = `monthly_budget - budget_used`, minimum boleh negatif jika overspending.
- `budget_percentage` = `(budget_used / monthly_budget) * 100`, bulatkan integer, jika budget 0 maka 0.
- `largest_transaction_amount` ambil amount terbesar dari semua transaksi completed user.

Tasks:
- [ ] Buat `UserController`.
- [ ] Buat request validation `UpdateProfileRequest`.
- [ ] Buat request validation `UpdateBudgetRequest`.
- [ ] Buat resource/helper untuk profile response.
- [ ] Daftarkan route private di `routes/api.php`.
- [ ] Tambahkan test profile dan budget.

Acceptance criteria:
- [ ] User hanya melihat data miliknya sendiri.
- [ ] Email update wajib unique kecuali email user sendiri.
- [ ] Budget tidak boleh negatif.
- [ ] Semua nominal response integer.

---

## Issue API-003: Dashboard Aggregated Data

Endpoint:
- `GET /api/dashboard`

Response:

```json
{
  "success": true,
  "data": {
    "current_month_balance": 2150000,
    "total_income": 5000000,
    "total_expense": 2850000,
    "insight_strip": {
      "text": "Pengeluaran makanan +23%",
      "subtext": "Dibanding minggu lalu · Rp 385.000",
      "icon": "💡"
    },
    "recent_transactions": [
      {
        "id": 10,
        "description": "Kopi di Starbucks",
        "amount": 65000,
        "type": "expense",
        "category_name": "MAKANAN",
        "category_icon": "☕",
        "date_formatted": "13 Mei 2026"
      }
    ],
    "expense_by_category": [
      {
        "category_name": "MAKANAN",
        "category_icon": "☕",
        "amount": 850000,
        "percentage_of_expense": 30
      }
    ]
  },
  "message": "Dashboard berhasil diambil"
}
```

Calculation notes:
- Bulan berjalan berdasarkan `transaction_date`.
- `current_month_balance` = `total_income - total_expense`.
- `total_income` = sum amount type `income`.
- `total_expense` = sum amount type `expense`.
- `recent_transactions` ambil 3 sampai 5 transaksi terakhir user.
- `expense_by_category` hanya expense bulan berjalan.
- `percentage_of_expense` = amount kategori / total expense * 100, integer.
- `insight_strip` boleh rule-based dulu:
  - Jika expense makanan minggu ini lebih besar dari minggu lalu, tampilkan kenaikan.
  - Jika tidak ada data cukup, tampilkan insight umum seperti "Mulai catat transaksi hari ini".

Tasks:
- [ ] Buat `DashboardController`.
- [ ] Buat `DashboardService`.
- [ ] Query hanya transaksi milik authenticated user.
- [ ] Eager load category untuk menghindari N+1 query.
- [ ] Tambahkan feature test dashboard.

Acceptance criteria:
- [ ] Dashboard kosong tetap mengembalikan struktur lengkap dengan angka 0 dan array kosong.
- [ ] Dashboard dengan seed data mengembalikan transaksi dan kategori.
- [ ] Semua amount integer.

---

## Issue API-004: AI Chat Transaction Parser

Endpoint:
- `POST /api/transactions/chat`

Payload:

```json
{
  "message": "Beli kopi di Starbucks 65 ribu"
}
```

Response:

```json
{
  "success": true,
  "data": {
    "reply_message": "Sip, udah dicatat! ☕",
    "parsed_data": {
      "description": "Kopi di Starbucks",
      "amount": 65000,
      "category": "MAKANAN",
      "category_icon": "☕",
      "type": "expense"
    }
  },
  "message": "Transaksi berhasil dicatat"
}
```

Implementation phase 1:
- Gunakan `TransactionParserService` berbasis regex dan keyword terlebih dahulu.
- Siapkan interface/method agar nanti mudah diganti ke LLM.
- Jangan panggil LLM asli dulu kecuali API key dan requirement sudah tersedia.

Parser rules minimum:
- Nominal:
  - `65 ribu` = `65000`
  - `65rb` = `65000`
  - `100k` = `100000`
  - `1 juta` = `1000000`
  - `250000` = `250000`
- Type:
  - Income jika ada keyword: `gaji`, `bonus`, `dibayar`, `transfer masuk`, `dapat`.
  - Expense default jika tidak ada keyword income.
- Category:
  - Cocokkan keyword dari table `categories.keywords`.
  - Jika tidak cocok, pakai `LAINNYA`.
- Description:
  - Bersihkan kata kerja umum seperti `beli`, `bayar`, `buat`, nominal, dan kata satuan uang.
  - Jika hasil kosong, gunakan message asli.

Tasks:
- [ ] Buat `TransactionController@chat`.
- [ ] Buat `ChatTransactionRequest`.
- [ ] Buat `TransactionParserService`.
- [ ] Simpan transaksi dengan `source = chat` dan `raw_message = message`.
- [ ] Update wallet balance jika business rule wallet balance masih dipakai.
- [ ] Tambahkan unit test parser untuk minimal 8 variasi input.
- [ ] Tambahkan feature test endpoint chat.

Acceptance criteria:
- [ ] Input "Beli kopi di Starbucks 65 ribu" tersimpan sebagai expense 65000 kategori MAKANAN.
- [ ] Input "Gaji freelance 2 juta" tersimpan sebagai income 2000000 kategori GAJI.
- [ ] Response mengembalikan `reply_message` dan `parsed_data`.
- [ ] Transaksi baru muncul di dashboard dan history.

---

## Issue API-005: Transaction History Grouped by Month

Endpoint:
- `GET /api/transactions`

Query params:
- `filter=SEMUA`
- `filter=PEMASUKAN`
- `filter=PENGELUARAN`
- `filter=MAKANAN`
- Bisa juga nama kategori lain seperti `TRANSPORT`, `BELANJA`, `GAJI`.

Response:

```json
{
  "success": true,
  "data": [
    {
      "month_label": "MEI 2026",
      "transactions": [
        {
          "id": 10,
          "description": "Kopi di Starbucks",
          "amount": 65000,
          "type": "expense",
          "category_name": "MAKANAN",
          "category_icon": "☕",
          "date_formatted": "13 Mei 2026",
          "source": "chat"
        }
      ]
    },
    {
      "month_label": "APRIL 2026",
      "transactions": []
    }
  ],
  "message": "Riwayat transaksi berhasil diambil"
}
```

Filter mapping:
- `SEMUA` = semua transaksi completed user.
- `PEMASUKAN` = `type = income`.
- `PENGELUARAN` = `type = expense`.
- Selain itu dianggap sebagai `categories.name`.

Sorting:
- Group bulan terbaru di atas.
- Transaksi dalam bulan diurutkan terbaru ke terlama.

Tasks:
- [ ] Buat `TransactionController@index`.
- [ ] Buat reusable formatter `TransactionResource`.
- [ ] Implement filter query.
- [ ] Group data berdasarkan bulan dari `transaction_date`.
- [ ] Tambahkan test untuk semua filter utama.

Acceptance criteria:
- [ ] Response selalu grouped by month.
- [ ] Jika tidak ada transaksi, `data` berupa array kosong.
- [ ] Filter kategori tidak case sensitive.
- [ ] User tidak bisa melihat transaksi user lain.

---

## Seeder Requirement

## Issue SEED-001: Seeder demo DOMPET minimal 2 bulan

Seeder wajib membuat data agar frontend bisa langsung menampilkan dashboard, chart, progress bar kategori, recent transactions, dan history.

Data minimum:
- 1 user demo:
  - email: `demo@dompet.test`
  - password: `password`
  - name: `Demo DOMPET`
  - monthly_budget: `3000000`
- 1 wallet aktif untuk user demo.
- Semua category default.
- Minimal 30 transaksi:
  - Tersebar di bulan berjalan dan bulan sebelumnya.
  - Minimal 5 income.
  - Minimal 20 expense.
  - Minimal 5 kategori berbeda.
  - Minimal 8 transaksi dengan `source = chat`.

Contoh transaksi:
- Gaji bulanan 5000000, kategori GAJI, income.
- Kopi di Starbucks 65000, kategori MAKANAN, expense.
- GoRide ke kantor 28000, kategori TRANSPORT, expense.
- Belanja bulanan 450000, kategori BELANJA, expense.
- Bayar internet 350000, kategori TAGIHAN, expense.

Tasks:
- [ ] Buat `CategorySeeder`.
- [ ] Buat `DompetDemoSeeder`.
- [ ] Panggil dari `DatabaseSeeder`.
- [ ] Pastikan seeder idempotent memakai `updateOrCreate` atau `firstOrCreate`.
- [ ] Jalankan `php artisan migrate:fresh --seed`.

Acceptance criteria:
- [ ] Setelah seed, user demo bisa login.
- [ ] Dashboard user demo tidak kosong.
- [ ] History memiliki group bulan berjalan dan bulan sebelumnya.
- [ ] `expense_by_category` punya lebih dari 3 kategori.

---

## Routes Target

Tambahkan route berikut di `routes/api.php`.

```php
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});

Route::middleware('jwt.auth')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::put('/user/budget', [UserController::class, 'updateBudget']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/chat', [TransactionController::class, 'chat']);
});
```

Jika project memakai Sanctum, ganti middleware menjadi `auth:sanctum`.

---

## Recommended Work Order

Kerjakan berurutan agar tidak bingung:

1. **DB-001, DB-002, DB-003** - Rapikan schema dulu.
2. **SEED-001** - Isi dummy data agar endpoint mudah dites.
3. **API-001** - Pastikan auth stabil.
4. **API-002** - Profile dan budget.
5. **API-005** - History karena lebih sederhana.
6. **API-003** - Dashboard aggregation.
7. **API-004** - Chat parser dan create transaction.
8. Jalankan semua test dan generate API docs jika project memakai Scribe.

---

## Definition of Done

Satu issue dianggap selesai jika:
- [ ] Endpoint/schema sesuai acceptance criteria.
- [ ] Response mengikuti global API standard.
- [ ] Nominal uang dikembalikan integer.
- [ ] Ada validasi request.
- [ ] Ada test minimal untuk happy path dan 1 error path.
- [ ] Tidak ada akses data lintas user.
- [ ] Seeder bisa dijalankan tanpa error.
- [ ] Dokumentasi endpoint diperbarui jika memakai Scribe/OpenAPI.

---

## Manual Test Checklist

Gunakan checklist ini setelah implementasi:

- [ ] `php artisan migrate:fresh --seed` sukses.
- [ ] Login `demo@dompet.test` / `password` sukses.
- [ ] `GET /api/user/profile` mengembalikan stats dan budget.
- [ ] `GET /api/dashboard` mengembalikan income, expense, recent transactions, category progress.
- [ ] `POST /api/transactions/chat` dengan "Beli kopi di Starbucks 65 ribu" membuat transaksi baru.
- [ ] Transaksi chat muncul di `GET /api/transactions`.
- [ ] `GET /api/transactions?filter=MAKANAN` hanya menampilkan kategori MAKANAN.
- [ ] `GET /api/transactions?filter=PEMASUKAN` hanya menampilkan income.
- [ ] User A tidak bisa melihat data User B.
