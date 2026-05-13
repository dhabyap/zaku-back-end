<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'MAKANAN', 'icon' => '☕', 'type' => 'expense', 'keywords' => ['kopi', 'starbucks', 'makan', 'nasi', 'ayam', 'resto', 'bakso', 'soto']],
            ['name' => 'TRANSPORT', 'icon' => '🚗', 'type' => 'expense', 'keywords' => ['goride', 'gojek', 'grab', 'bensin', 'parkir', 'tol', 'transport', 'ojek']],
            ['name' => 'BELANJA', 'icon' => '🛒', 'type' => 'expense', 'keywords' => ['belanja', 'market', 'alfamart', 'indomaret', 'sepatu', 'baju', 'bulanan']],
            ['name' => 'TAGIHAN', 'icon' => '🧾', 'type' => 'expense', 'keywords' => ['internet', 'listrik', 'air', 'tagihan', 'pulsa', 'pdam', 'wifi']],
            ['name' => 'HIBURAN', 'icon' => '🎮', 'type' => 'expense', 'keywords' => ['game', 'bioskop', 'netflix', 'spotify', 'hiburan', 'tiket']],
            ['name' => 'GAJI', 'icon' => '💰', 'type' => 'income', 'keywords' => ['gaji', 'bonus', 'freelance', 'fee', 'dibayar', 'pendapatan']],
            ['name' => 'LAINNYA', 'icon' => '📌', 'type' => 'both', 'keywords' => ['lainnya', 'misc']],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }
    }
}
