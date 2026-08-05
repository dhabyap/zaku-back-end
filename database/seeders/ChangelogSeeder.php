<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChangelogSeeder extends Seeder
{
    public function run(): void
    {
        $logs = [
            [
                'title' => 'Landing Page Baru',
                'description' => 'Halaman utama sekarang menampilkan landing page profesional dengan desain dark theme. Stats pengguna dan transaksi diambil real-time dari database.',
                'author' => 'Putra',
                'version' => 'v1.4.0',
                'status' => 'solved',
                'issues' => ['Landing page sebelumnya kosong', 'Stats hardcoded diganti data real dari API'],
                'created_at' => now(),
            ],
            [
                'title' => 'Fix Login & Session Expired',
                'description' => 'Session expired sekarang otomatis redirect ke halaman login. JWT token dicek di 3 layer: page load, request interceptor, dan response interceptor.',
                'author' => 'Putra',
                'version' => 'v1.4.0',
                'status' => 'solved',
                'issues' => ['Session expired tidak redirect ke login', 'Dashboard kosong saat token habis'],
                'created_at' => now()->subMinutes(5),
            ],
            [
                'title' => 'Fix AI Parser — Kata "Dapet" = Pemasukan',
                'description' => 'AI parser sekarang benar mengenali "dapat", "dapet", "dpt" sebagai pemasukan. Ditambah keyword override post-processing agar tidak bergantung LLM.',
                'author' => 'Putra',
                'version' => 'v1.4.0',
                'status' => 'solved',
                'issues' => ['"dapet 10 ribu" salah klasifikasi sebagai pengeluaran', 'AI parser probabilistic, kadang salah'],
                'created_at' => now()->subMinutes(15),
            ],
            [
                'title' => 'Edit Transaksi dari Detail',
                'description' => 'Halaman detail transaksi sekarang bisa edit semua field: deskripsi, jumlah, tanggal, tipe, dan kategori. Tombol SIMPAN/BATAL tersedia.',
                'author' => 'Putra',
                'version' => 'v1.4.0',
                'status' => 'solved',
                'issues' => ['Kategori transaksi kadang tidak sesuai', 'Tidak bisa edit dari halaman detail'],
                'created_at' => now()->subMinutes(30),
            ],
            [
                'title' => 'Cetak Struk Lebih Rapi',
                'description' => 'Tombol cetak sekarang menampilkan struk thermal receipt 80mm yang rapi, bukan seluruh halaman web. Font monospace Courier New, layout bersih.',
                'author' => 'Putra',
                'version' => 'v1.4.0',
                'status' => 'solved',
                'issues' => ['Cetak struk menampilkan sidebar dan dark theme', 'Layout cetak berantakan'],
                'created_at' => now()->subMinutes(45),
            ],
            [
                'title' => 'Fix Mixed Content — HTTPS API',
                'description' => 'API URL diubah dari HTTP ke HTTPS untuk mengatasi mixed content blocking di production (zaku.abysoft.my.id).',
                'author' => 'Putra',
                'version' => 'v1.3.1',
                'status' => 'solved',
                'issues' => ['Login gagal di production karena mixed content', 'Browser block HTTP request dari HTTPS page'],
                'created_at' => now()->subHour(),
            ],
            [
                'title' => 'Fix Chat Response Format',
                'description' => 'Chat sekarang menampilkan kartu transaksi dengan format yang benar — deskripsi, jumlah, kategori, dan tipe. Mendukung reply_message dan parsed_data dari API.',
                'author' => 'Putra',
                'version' => 'v1.3.0',
                'status' => 'solved',
                'issues' => ['Chat response tidak menampilkan kartu transaksi', 'Format data tidak konsisten'],
                'created_at' => now()->subHour(2),
            ],
            [
                'title' => 'Fix Detail Transaksi Link',
                'description' => 'Link dari daftar transaksi ke halaman detail sekarang menggunakan route web yang benar, bukan endpoint API langsung.',
                'author' => 'Putra',
                'version' => 'v1.3.0',
                'status' => 'solved',
                'issues' => ['Klik transaksi di riwayat tidak membuka detail', 'Link mengarah ke API endpoint'],
                'created_at' => now()->subHour(3),
            ],
            [
                'title' => 'Fix Profile Menu',
                'description' => 'Semua menu di halaman profil sekarang berfungsi: Edit Profil, Atur Budget, Export CSV, dan Keluar. Modal menggunakan class CSS yang benar.',
                'author' => 'Putra',
                'version' => 'v1.3.0',
                'status' => 'solved',
                'issues' => ['Modal edit profil tidak muncul', 'Export data CSV belum ada fiturnya'],
                'created_at' => now()->subHour(4),
            ],
        ];

        DB::table('changelogs')->insert($logs);
    }
}
