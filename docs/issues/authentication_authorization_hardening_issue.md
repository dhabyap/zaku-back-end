# ISSUE-BE-AUTH-001: Audit dan hardening Authentication & Authorization

## 📋 Title
Audit keamanan autentikasi, otorisasi, dan sesi JWT Zaku

## 🔴 Problem
Audit awal menunjukkan fondasi penting sudah ada: password di-hash lewat cast `hashed`, login/reset punya rate limit, endpoint privat memakai `jwt.auth`, dan beberapa endpoint admin memakai middleware `admin`. Jadi pekerjaan ini bukan membangun ulang auth.

| No | Temuan | Bukti kode | Risiko / tindak lanjut |
|---|---|---|---|
| 1 | Kontrak refresh menyebut dua token, implementasi mengembalikan token yang sama pada `access_token` dan `refresh_token`. | `backend/app/Http/Controllers/Api/AuthController.php:215-222` | Pastikan kontrak frontend. Perjelas response dan dokumentasi; jangan menambah refresh-token terpisah tanpa keputusan desain dan kompatibilitas klien. |
| 2 | Reset dan ganti password belum mencabut sesi/token lain secara eksplisit. | `AuthController.php:271-277`, `408-420`; logout hanya invalidasi token aktif di `239-243`. | Tentukan perilaku keamanan setelah kredensial berubah; uji apakah blacklist/strategi versi token mencabut semua sesi. |
| 3 | Jejak audit autentikasi belum tampak di alur login, reset, ganti password, logout, dan keputusan admin. | `AuthController.php:118-149`, `215-243`, `246-277`, `408-460`; middleware `backend/app/Http/Middleware/AdminOnly.php:11-23`. | Pastikan ada log yang aman dan berguna untuk investigasi. Cari log/observer lain sebelum menambah implementasi agar tidak duplikat. |
| 4 | Otorisasi admin terlihat diterapkan pada operasi kategori dan dashboard analytics; belum ada bukti matriks peran/izin untuk semua aksi administratif. | `backend/routes/api.php:88-101`; `AdminOnly.php:15-19`. | Audit semua endpoint sensitif dan kepemilikan data. Tambah rule hanya untuk aksi yang memang perlu pembatasan. |
| 5 | Coverage test autentikasi yang terlihat belum mencakup seluruh kasus keamanan sesi dan otorisasi. | `backend/tests/Feature/JwtConfigurationTest.php`; beberapa test reset password di `ZakuApiTest.php`. | Tambahkan regression tests untuk akses anonim, role salah, akses data user lain, refresh, logout, dan perubahan password. |

## ✅ Tujuan / Expected Result
- Kategori: **Backend**; cek kontrak frontend sebelum perubahan API.
- Pertahankan perilaku yang sudah benar dan hindari rebuild auth.
- Dokumentasikan alur JWT aktual, termasuk apakah sistem memakai satu token yang di-refresh atau access/refresh token terpisah.
- Pastikan semua endpoint sensitif memeriksa identitas, izin, dan kepemilikan resource di server.
- Tentukan lalu uji kebijakan pencabutan sesi setelah reset/ganti password.
- Tambahkan audit trail tanpa menyimpan password, token, kode reset, atau secret.
- Semua perubahan punya feature tests dan tidak merusak kontrak klien.

## 📦 File yang harus diperiksa / mungkin diubah
| No | File | Aksi | Keterangan |
|---|---|---|---|
| 1 | `backend/app/Http/Controllers/Api/AuthController.php` | Audit / ubah bila disepakati | Login, refresh, logout, reset password, ganti password; titik terkait sekitar baris 118-149, 215-243, 246-277, 408-460. |
| 2 | `backend/routes/api.php` | Audit / ubah bila perlu | Daftar route privat dan grup `admin`; baris 37-102. |
| 3 | `backend/app/Http/Middleware/AdminOnly.php` | Audit / ubah bila perlu | Pemeriksaan role admin; baris 11-23. |
| 4 | `backend/app/Models/User.php` | Audit / ubah bila perlu | Cast password `hashed`, role, dan identitas JWT; baris 23-56, 83-91. |
| 5 | `backend/config/jwt.php` | Audit konfigurasi | TTL, refresh TTL, algoritma, blacklist. Jangan mengubah secret produksi. |
| 6 | `backend/tests/Feature/ZakuApiTest.php` | Tambah regression tests | Sudah berisi test reset password; gunakan pola test yang ada. |
| 7 | `backend/tests/Feature/JwtConfigurationTest.php` | Tambah regression tests bila sesuai | Saat ini memverifikasi konfigurasi dasar dan penerbitan token. |

## 🔧 Langkah-Langkah Implementasi

### Langkah 1 — Petakan kontrak token sebelum mengubah API
**Akar masalah:** dokumentasi method refresh menyebut access token dan refresh token, tetapi implementasi `AuthController.php:215-222` memanggil `JWTAuth::parseToken()->refresh()` lalu mengisi kedua field dengan nilai token yang sama. Ini bisa merupakan rotasi token satu-token yang disengaja; belum cukup bukti untuk mengganti arsitektur.

- Periksa frontend dan dokumentasi API: field yang benar-benar dibaca klien, cara menyimpan token, dan perilaku saat refresh gagal.
- Catat kontrak yang disepakati. Jika satu token dipakai dan dirotasi, ubah dokumentasi response agar tidak mengklaim ada dua token berbeda.
- Jika perlu refresh token terpisah, buat desain/migrasi terpisah dan kompatibel dengan klien; jangan sekadar mengganti nama field.

**Verifikasi:** test refresh memastikan token lama tidak berlaku setelah rotasi sesuai konfigurasi blacklist, token baru dapat dipakai, serta response cocok dengan kontrak frontend.

### Langkah 2 — Tetapkan kebijakan sesi setelah perubahan password
**Akar masalah:** reset/ganti password menyimpan password baru, tetapi potongan alur yang diaudit tidak menunjukkan pencabutan seluruh sesi pengguna.

- Periksa kemampuan blacklist JWT dan konfigurasi saat ini; jangan menganggap blacklist aktif hanya dari keberadaan package.
- Tentukan apakah reset/ganti password harus mencabut token aktif saja atau seluruh sesi.
- Implementasikan sesuai kebijakan terpilih, lalu uji token lama dari sesi lain ditolak. Pertahankan sesi atau paksa login ulang secara konsisten dengan keputusan produk.

**Verifikasi:** buat dua token/session untuk user yang sama, ubah password lewat masing-masing alur, lalu uji hasil pemakaian token lama sesuai kebijakan.

### Langkah 3 — Audit otorisasi route dan kepemilikan data
**Akar masalah:** route privat memakai `jwt.auth`; middleware admin ditemukan pada operasi kategori dan dashboard analytics. Autentikasi saja tidak membuktikan akses ke resource milik user lain aman.

- Inventaris semua route di `backend/routes/api.php` dan identifikasi aksi admin/sensitif.
- Periksa query resource per controller memastikan dibatasi ke user yang sedang login. Contoh pola yang sudah tampak: `TransactionController` memakai `baseQuery($request)` dan `UserController` membatasi transaksi berdasarkan `user_id`.
- Pastikan role tidak dapat diubah lewat registrasi atau update profil biasa. `User::$fillable` memuat `role`, tetapi controller yang diaudit menggunakan field eksplisit; uji request aktual sebelum mengubah model.
- Tambahkan middleware/policy hanya pada aksi yang membutuhkan izin tambahan. Jangan membatasi endpoint user biasa tanpa kebutuhan produk.

**Verifikasi:** user A tidak bisa membaca/mengubah/menghapus data user B; non-admin ditolak pada aksi admin dengan 403; admin tetap dapat menjalankan aksi yang sah.

### Langkah 4 — Audit trail autentikasi yang aman
**Akar masalah:** controller yang diperiksa belum menunjukkan pencatatan peristiwa auth. Sistem lain mungkin sudah mencatat lewat event/listener, jadi cari implementasi existing terlebih dahulu.

- Cari listener, event, middleware, dan tabel log sebelum menambahkan logger baru.
- Jika belum ada, catat minimal peristiwa login berhasil/gagal, reset password diminta/berhasil, ganti password, logout, dan penolakan akses admin.
- Simpan waktu, user ID bila diketahui, jenis aksi, hasil, serta metadata request yang diperlukan dan tersedia.
- Jangan simpan password, JWT utuh, reset token, verification code, atau `JWT_SECRET`. Hindari logging berlebihan atas IP/perangkat; ikuti kebijakan privasi proyek.

**Verifikasi:** test memastikan peristiwa tercatat dan memastikan secret/token tidak muncul di log.

### Langkah 5 — Regression tests dan dokumentasi
- Tambah test untuk token tidak valid/kedaluwarsa, refresh/rotasi, logout, reset/ganti password, otorisasi admin, dan akses lintas-user.
- Tambah test agar response forgot-password tidak membocorkan apakah email terdaftar, bila kontrak sekarang belum memenuhi perilaku generik.
- Jalankan seluruh test backend dan formatter/linter yang tersedia.
- Perbarui dokumentasi API sesuai perilaku nyata, bukan desain yang diasumsikan.

## 🧪 Cara Test
1. Dari `D:/Latihan/zaku/backend`, jalankan `php artisan test`.
2. Jalankan test feature auth yang baru dan yang sudah ada, termasuk `JwtConfigurationTest` dan test reset password di `ZakuApiTest`.
3. Uji manual/API: login, refresh berulang, logout, pakai token lama, reset password, ganti password, request admin sebagai non-admin, dan akses resource user lain.
4. Periksa log test: tidak ada password, JWT, reset token, verification code, atau secret.
5. Jalankan `git diff --check` dan formatter PHP yang dipakai repository.

## ✔️ Acceptance Criteria
- [ ] Kontrak refresh token cocok dengan implementasi dan klien; tidak ada klaim dua token berbeda bila nilainya sama.
- [ ] Kebijakan pencabutan sesi setelah reset/ganti password terdokumentasi dan teruji.
- [ ] Semua route/aksi sensitif punya pemeriksaan otorisasi backend yang tepat.
- [ ] Pengguna tidak dapat mengakses resource milik pengguna lain.
- [ ] Role admin tidak dapat dinaikkan melalui registrasi atau update profil biasa.
- [ ] Audit trail auth mencakup aksi yang disepakati tanpa menyimpan rahasia; atau implementasi existing didokumentasikan dan dites.
- [ ] Forgot-password tidak membocorkan keberadaan email melalui response.
- [ ] `php artisan test` lulus dan `git diff --check` bersih.
- [ ] Kontrak API frontend tetap kompatibel atau perubahan kontrak disepakati dan didokumentasikan.

## 🔗 Reference
- Artikel: https://hackernoon.com/designing-functional-authentication-and-authorization-systems
- `backend/routes/api.php`
- `backend/app/Http/Controllers/Api/AuthController.php`
- `backend/app/Http/Middleware/AdminOnly.php`
- `backend/app/Models/User.php`
- `backend/config/jwt.php`
- `backend/tests/Feature/ZakuApiTest.php`
- `backend/tests/Feature/JwtConfigurationTest.php`

## Catatan scope
Artikel berisi rancangan konseptual, bukan audit keamanan formal. Klaim storage artikelnya keliru; jangan gunakan angka kapasitasnya sebagai dasar. Password Zaku sudah memakai cast Laravel `hashed`; jangan mengganti menjadi enkripsi reversibel.
