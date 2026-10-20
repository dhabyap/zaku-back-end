# Audit Bahasa Response API untuk Pengguna

## Tujuan

Membuat semua pesan error dan status yang terlihat pengguna konsisten, jelas, dan menggunakan bahasa Indonesia sehari-hari. Dokumen ini audit dan usulan; belum mengubah kode.

## Temuan utama

- Response backend mencampur bahasa Indonesia dan Inggris.
- Beberapa pesan terlalu teknis atau tidak menjelaskan langkah berikutnya, misalnya `Invalid credentials.`, `Authorization token has expired.`, `Budget not found.`, dan `Validation failed`.
- Pesan validasi Laravel bisa mengandung teks bahasa Inggris jika tidak disetel ke locale Indonesia.
- Frontend kadang menampilkan pesan API mentah; fallback pada tiap halaman juga tidak selalu konsisten.
- `ThrottleRequestsException` sudah diformat khusus di handler, tetapi perlu dipastikan semua bentuk respons 429 melewati handler dan frontend menampilkan waktu coba lagi.

## Usulan pesan

| Kondisi | Pesan untuk pengguna |
|---|---|
| Login gagal | Email atau kata sandi belum benar. Periksa kembali, lalu coba lagi. |
| Akun belum diverifikasi | Email Anda belum diverifikasi. Buka email dan klik tautan verifikasi sebelum masuk. |
| Sesi/kunci masuk kedaluwarsa | Sesi Anda sudah berakhir. Silakan masuk kembali. |
| Sesi/kunci masuk tidak valid | Sesi tidak dapat digunakan. Silakan masuk kembali. |
| Belum masuk | Silakan masuk terlebih dahulu untuk melanjutkan. |
| Tidak punya akses | Anda tidak memiliki izin untuk melakukan ini. |
| Data tidak ditemukan | Data yang diminta tidak ditemukan atau sudah dihapus. |
| Input tidak valid | Ada data yang belum sesuai. Periksa bagian yang ditandai, lalu coba lagi. |
| Terlalu banyak permintaan | Anda sudah mencoba beberapa kali. Coba lagi dalam {durasi/waktu}. |
| Email sudah terdaftar | Email ini sudah digunakan. Coba masuk atau gunakan email lain. |
| Kode verifikasi salah/tidak ditemukan | Kode verifikasi tidak cocok. Periksa email atau minta kode baru. |
| Kode verifikasi kedaluwarsa | Kode verifikasi sudah kedaluwarsa. Minta kode baru untuk melanjutkan. |
| Tautan reset kata sandi tidak valid | Tautan ubah kata sandi tidak valid. Minta tautan baru. |
| Tautan reset kedaluwarsa | Tautan ubah kata sandi sudah kedaluwarsa. Minta tautan baru. |
| Kata sandi lama salah | Kata sandi saat ini belum benar. Periksa kembali, lalu coba lagi. |
| Kategori masih dipakai | Kategori ini masih digunakan oleh transaksi. Pindahkan atau hapus transaksi terkait terlebih dahulu. |
| Gangguan server/jaringan | Terjadi kendala saat memproses permintaan. Periksa koneksi dan coba lagi. Jika masalah berlanjut, hubungi dukungan. |

## Inventaris area yang perlu diseragamkan

### Backend

1. `app/Exceptions/Handler.php`
   - Pastikan exception throttle yang benar ditangani.
   - Response 429 berisi pesan sederhana, `retry_after`, `retry_at`, dan `retry_at_local`.
   - Hindari membocorkan exception class, file server, atau stack trace pada response produksi.
2. `app/Http/Middleware/JwtAuthenticate.php`
   - Ganti pesan teknis token menjadi arahan masuk kembali.
3. `app/Http/Controllers/Api/AuthController.php`
   - Terjemahkan pesan verifikasi, reset kata sandi, perubahan kata sandi, logout, dan sukses.
4. `app/Http/Controllers/Api/BudgetController.php`, `TransactionController.php`, dan controller lain
   - Ubah sisa pesan Inggris seperti `Budget not found.` dan `Transaction not found.`.
   - Jangan tampilkan exception database mentah (`$e->getMessage()`) kepada pengguna; log detail di server dan kirim pesan aman.
5. `app/Traits/ApiResponse.php` dan `app/Http/Requests/ApiFormRequest.php`
   - Ganti default `Operation failed`, `Forbidden`, `Resource not found`, dan `Validation failed`.
6. Validasi request
   - Pastikan pesan validasi dan nama atribut mudah dimengerti dalam Bahasa Indonesia; gunakan pesan khusus untuk kasus yang membingungkan.
7. Error produksi
   - Pastikan `APP_DEBUG=false` di server produksi sehingga exception dan trace tidak dikirim ke klien.

### Frontend

1. `dompet-frontend/resources/js/utils.js`
   - Normalisasi pesan 401, 403, 404, 422, 429, 500, timeout, dan gangguan jaringan.
   - Untuk 422, tampilkan pesan validasi per kolom dengan bahasa sederhana.
   - Jangan tampilkan pesan Inggris/stack trace mentah dari server sebagai toast.
2. `dompet-frontend/resources/js/alpine-components.js`
   - Rapikan fallback pesan auth dan form agar konsisten dengan API.
3. Blade dan komponen lain
   - Audit pesan toast/alert langsung yang tidak melewati `parseApiError`.

## Pedoman bahasa

- Gunakan “kata sandi”, “masuk”, “data”, dan “coba lagi”; hindari istilah teknis seperti credential, token, payload, request, dan unauthorized pada pesan pengguna.
- Sampaikan tindakan berikutnya jika ada: periksa email, masuk kembali, minta kode baru, atau coba lagi setelah waktu tertentu.
- Jangan mengungkap apakah email tertentu terdaftar pada alur yang perlu mencegah enumerasi akun; gunakan pesan generik sesuai kebijakan autentikasi.
- Pisahkan pesan pengguna dari detail diagnosis. Detail teknis masuk log server, bukan response produksi.
- Pertahankan format JSON dan kode status API agar perubahan teks tidak merusak klien.

## Rencana penerapan

1. Setujui gaya bahasa dan pesan yang diusulkan.
2. Buat kamus pesan backend terpusat dan terapkan pada exception, middleware, controller, serta validasi.
3. Normalisasi pesan frontend dan tampilkan waktu tunggu throttle dengan hitung mundur bila tersedia.
4. Tambahkan/ubah tes untuk response 401, 403, 404, 422, 429, 500, dan validasi.
5. Jalankan tes backend dan build frontend; deploy backend serta frontend setelah verifikasi.

## Kriteria selesai

- Tidak ada pesan teknis/Inggris yang ditampilkan pada alur utama pengguna.
- Respons 429 menyebut kapan pengguna dapat mencoba lagi; UI memakai durasi dari server.
- Respons produksi tidak membocorkan trace, path server, atau exception internal.
- Struktur JSON, status HTTP, dan field yang digunakan frontend tetap kompatibel.
- Tes mencakup respons umum dan pesan validasi.
