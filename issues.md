# Issues - Zaku Backend

## Issue #1: Production Email Configuration Error - Mailpit Host Not Found

**Status:** Open  
**Priority:** HIGH  
**Reported:** 2026-05-21  
**Environment:** Production

---

### Deskripsi

Saat user membuat akun baru di production, muncul error:

```
Connection could not be established with host "mailpit:1025": 
stream_socket_client(): php_network_getaddresses: getaddrinfo for mailpit failed: 
Name or service not known
```

### Root Cause

File `.env.example` (line 32-34) menggunakan konfigurasi mail development:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

**Mailpit** adalah tool testing email untuk development/local saja. Host `mailpit` tidak tersedia di server production, sehingga menyebabkan error DNS resolution gagal.

Kemungkinan penyebab:
1. File `.env` di production tidak dikonfigurasi dengan benar (masih menggunakan nilai default dari `.env.example`)
2. Tidak ada konfigurasi mailer production yang terpisah

### Dampak

- User tidak bisa membuat akun baru di production
- Email verification tidak terkirim
- Registrasi user gagal total

### Keputusan

**Tidak menggunakan email verification untuk sementara (masa percobaan).**
User akan langsung terverifikasi otomatis setelah register. Email verification bisa diaktifkan kembali nanti saat sudah siap production.

### Solusi yang Diperlukan

#### 1. Update `.env.example` agar lebih aman

Ubah default mailer ke `log` agar tidak mencoba koneksi SMTP jika `.env` belum dikonfigurasi:

```env
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
```

#### 2. Auto-verify user setelah register

Di `AuthController@register`, set `is_verified = true` langsung saat create user, dan hapus/skip pengiriman email verifikasi.

### File yang Perlu Dicek

| File | Line | Keterangan |
|------|------|------------|
| `.env.example` | 32-39 | Konfigurasi mail default |
| `config/mail.php` | - | Mailer configuration |
| Server production `.env` | - | Perlu dicek dan diupdate |

### Checklist

- [x] Update `.env.example` dengan `MAIL_MAILER=log` sebagai default yang aman
- [x] Auto-verify user setelah register (`is_verified = true`)
- [x] Hapus pengiriman email verifikasi di proses register
- [ ] Test registrasi user di production setelah fix
- [ ] (Nanti) Aktifkan kembali email verification saat sudah siap production

### Referensi

- Mailpit: https://github.com/axllent/mailpit (development only)
- Laravel Mail Docs: https://laravel.com/docs/mail
