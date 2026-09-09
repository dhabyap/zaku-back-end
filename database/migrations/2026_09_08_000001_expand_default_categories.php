<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $categories = [
            // ── EXPENSE ──
            ['name' => 'MAKANAN', 'icon' => '☕', 'type' => 'expense', 'keywords' => ['kopi', 'starbucks', 'makan', 'nasi', 'ayam', 'resto', 'restoran', 'bakso', 'soto', 'warteg', 'mie', 'gorengan', 'jajan']],
            ['name' => 'BELANJA', 'icon' => '🛒', 'type' => 'expense', 'keywords' => ['belanja', 'market', 'alfamart', 'indomaret', 'sepatu', 'baju', 'bulanan', 'minimarket', 'supermarket']],
            ['name' => 'TAGIHAN', 'icon' => '🧾', 'type' => 'expense', 'keywords' => ['internet', 'listrik', 'air', 'tagihan', 'pdam', 'wifi', 'pln']],
            ['name' => 'TELEKOMUNIKASI', 'icon' => '📱', 'type' => 'expense', 'keywords' => ['pulsa', 'paket data', 'telkomsel', 'indosat', 'xl', 'axis', 'smartfren', 'kuota']],
            ['name' => 'HIBURAN', 'icon' => '🎮', 'type' => 'expense', 'keywords' => ['game', 'bioskop', 'netflix', 'spotify', 'hiburan', 'tiket', 'youtube', 'streaming']],
            ['name' => 'KESEHATAN', 'icon' => '💊', 'type' => 'expense', 'keywords' => ['obat', 'apotek', 'dokter', 'rs', 'rumah sakit', 'vitamin', 'suplemen', 'klinik']],
            ['name' => 'KECANTIKAN', 'icon' => '💄', 'type' => 'expense', 'keywords' => ['shampoo', 'sabun', 'parfum', 'skincare', 'salon', 'kosmetik', 'makeup']],
            ['name' => 'PENDIDIKAN', 'icon' => '📚', 'type' => 'expense', 'keywords' => ['buku', 'les', 'kursus', 'sekolah', 'kuliah', 'tugas', 'pelatihan']],
            ['name' => 'RUMAH TANGGA', 'icon' => '🏠', 'type' => 'expense', 'keywords' => ['perabot', 'alat rumah', 'furniture', 'galon', 'gas', 'sabun cuci']],
            ['name' => 'INVESTASI', 'icon' => '📈', 'type' => 'both', 'keywords' => ['saham', 'reksadana', 'crypto', 'emas', 'trading', 'obligasi', 'nabung', 'dividen', 'return', 'capital gain', 'bunga', 'cuan saham', 'cuan']],
            ['name' => 'ASURANSI', 'icon' => '🛡️', 'type' => 'expense', 'keywords' => ['asuransi', 'premi', 'polis']],
            ['name' => 'CICILAN', 'icon' => '💳', 'type' => 'expense', 'keywords' => ['cicilan', 'angsuran', 'kredit', 'leasing']],
            ['name' => 'DONASI', 'icon' => '🤲', 'type' => 'expense', 'keywords' => ['donasi', 'sedekah', 'zakat', 'infaq', 'amal', 'wakaf']],
            ['name' => 'TRAVEL', 'icon' => '✈️', 'type' => 'expense', 'keywords' => ['tiket pesawat', 'hotel', 'liburan', 'travel', 'tiket kereta']],
            ['name' => 'OLAHRAGA', 'icon' => '🏋️', 'type' => 'expense', 'keywords' => ['gym', 'fitness', 'olahraga', 'yoga', 'sepeda']],

            // ── INCOME ──
            ['name' => 'GAJI', 'icon' => '💰', 'type' => 'income', 'keywords' => ['gaji', 'salary', 'gajian', 'paycheck', 'thr']],
            ['name' => 'BONUS', 'icon' => '🎁', 'type' => 'income', 'keywords' => ['bonus', 'insentif', 'komisi']],
            ['name' => 'FREELANCE', 'icon' => '💻', 'type' => 'income', 'keywords' => ['freelance', 'project', 'proyek', 'client', 'klien', 'fee']],
            ['name' => 'HADIAH', 'icon' => '🎀', 'type' => 'income', 'keywords' => ['hadiah', 'giveaway', 'warisan']],
            ['name' => 'PENDAPATAN LAINNYA', 'icon' => '💵', 'type' => 'income', 'keywords' => ['pendapatan', 'pemasukan', 'transfer masuk', 'refund', 'dibayar', 'dapat']],

            // ── BOTH ──
            ['name' => 'LAINNYA', 'icon' => '📌', 'type' => 'both', 'keywords' => ['lainnya', 'misc']],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }

        // Rename legacy TRANSPORT -> TRANSPORTASI (id stays the same, transactions safe)
        $legacy = Category::where('name', 'TRANSPORT')->first();
        $exists = Category::where('name', 'TRANSPORTASI')->exists();
        if ($legacy && ! $exists) {
            $legacy->update([
                'name' => 'TRANSPORTASI',
                'keywords' => ['goride', 'gojek', 'grab', 'bensin', 'parkir', 'tol', 'transport', 'ojek', 'ojol', 'taxi', 'taksi'],
            ]);
        } elseif ($legacy && $exists) {
            // TRANSPORTASI already exists, just delete legacy TRANSPORT
            $legacy->delete();
        }

    }

    public function down(): void
    {
        // Best-effort rollback: rename TRANSPORTASI -> TRANSPORT
        Category::where('name', 'TRANSPORTASI')->update(['name' => 'TRANSPORT']);

        // Remove the newly added categories (leave the original 7 intact)
        Category::whereIn('name', [
            'TELEKOMUNIKASI', 'KESEHATAN', 'KECANTIKAN', 'PENDIDIKAN',
            'RUMAH TANGGA', 'INVESTASI', 'ASURANSI', 'CICILAN', 'DONASI',
            'TRAVEL', 'OLAHRAGA', 'BONUS', 'FREELANCE',
            'HADIAH', 'PENDAPATAN LAINNYA',
        ])->delete();
    }
};
