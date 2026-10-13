<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        $updates = [
            [
                'version' => '#147',
                'title' => 'Aksesibilitas zoom, navigasi, dan fokus keyboard',
                'description' => 'Izinkan browser zoom, tandai halaman navigasi aktif untuk screen reader, perjelas focus ring, dan jaga fokus keyboard di inbox notifikasi.',
                'status' => 'pending',
                'issues' => ['FE-010', 'Browser zoom terkunci', 'Navigasi aktif tidak diumumkan', 'Fokus keyboard tidak konsisten'],
            ],
            [
                'version' => '#146',
                'title' => 'Navigasi sidebar desktop',
                'description' => 'Tambahkan sidebar desktop berkelompok untuk menu utama, keuangan, dan akun; navigasi bawah empat item tetap dipakai pada layar kecil.',
                'status' => 'pending',
                'issues' => ['Desktop navigation coverage'],
            ],
            [
                'version' => '#145',
                'title' => 'Status pengaturan notifikasi yang akurat',
                'description' => 'Ganti toggle notifikasi yang belum didukung backend dengan informasi status yang jujur; preferensi mata uang tetap tersimpan lokal.',
                'status' => 'pending',
                'issues' => ['Notification preferences'],
            ],
            [
                'version' => '#144',
                'title' => 'Inbox notifikasi',
                'description' => 'Tambahkan inbox notifikasi di dashboard dengan jumlah belum dibaca, aksi tandai dibaca, serta kondisi memuat, kosong, dan gagal.',
                'status' => 'solved',
                'issues' => ['Notification inbox'],
            ],
            [
                'version' => '#143',
                'title' => 'Navigasi bawah empat item',
                'description' => 'Sederhanakan navigasi mobile menjadi Beranda, Riwayat, Kalender, dan AI Chat tanpa tombol tambah atau popover.',
                'status' => 'solved',
                'issues' => ['Bottom navigation'],
            ],
            [
                'version' => '#142',
                'title' => 'Detail dan edit transaksi',
                'description' => 'Tambahkan tampilan detail transaksi responsif dengan aksi edit yang jelas dan kontras memadai.',
                'status' => 'solved',
                'issues' => ['Transaction detail and edit'],
            ],
            [
                'version' => '#141',
                'title' => 'Manifest asset navigasi untuk deployment',
                'description' => 'Perbarui manifest Vite agar deployment shared hosting menggunakan CSS navigasi mobile terbaru.',
                'status' => 'solved',
                'issues' => ['Production asset manifest'],
            ],
            [
                'version' => '#140',
                'title' => 'Perbaikan navigasi bawah pada layar kecil',
                'description' => 'Sesuaikan ukuran dan jarak enam item navigasi agar label tetap terlihat pada layar ponsel 360px.',
                'status' => 'solved',
                'issues' => ['Mobile bottom navigation at 360px'],
            ],
            [
                'version' => '#139',
                'title' => 'Rapikan navigasi bawah mobile',
                'description' => 'Perbaiki pemotongan label navigasi bawah pada layar ponsel kecil.',
                'status' => 'solved',
                'issues' => ['Mobile bottom navigation labels'],
            ],
            [
                'version' => '#138',
                'title' => 'Perbaikan tampilan riwayat transaksi',
                'description' => 'Rapikan teks judul transaksi, jarak konten dari tombol tambah, dan format nominal pada halaman riwayat.',
                'status' => 'solved',
                'issues' => ['Transaction history layout and amount formatting'],
            ],
        ];

        DB::table('changelogs')->insert(array_map(
            static fn (array $update, int $index): array => [
                ...$update,
                'author' => 'Zaku Team',
                'issues' => json_encode($update['issues'], JSON_THROW_ON_ERROR),
                'created_at' => $timestamp->copy()->subMinutes($index),
                'updated_at' => $timestamp->copy()->subMinutes($index),
            ],
            $updates,
            array_keys($updates),
        ));
    }

    public function down(): void
    {
        DB::table('changelogs')
            ->where('author', 'Zaku Team')
            ->whereIn('title', [
                'Aksesibilitas zoom, navigasi, dan fokus keyboard',
                'Navigasi sidebar desktop',
                'Status pengaturan notifikasi yang akurat',
                'Inbox notifikasi',
                'Navigasi bawah empat item',
                'Detail dan edit transaksi',
                'Manifest asset navigasi untuk deployment',
                'Perbaikan navigasi bawah pada layar kecil',
                'Rapikan navigasi bawah mobile',
                'Perbaikan tampilan riwayat transaksi',
            ])
            ->delete();
    }
};
