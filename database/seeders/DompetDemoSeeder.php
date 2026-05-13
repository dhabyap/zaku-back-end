<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DompetDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@dompet.test'],
            [
                'full_name' => 'Demo DOMPET',
                'password' => Hash::make('password'),
                'monthly_budget' => 3000000,
                'phone_number' => '081234567890',
                'is_verified' => true,
            ],
        );

        $wallet = Wallet::updateOrCreate(
            ['user_id' => $user->id],
            ['status' => Wallet::STATUS_ACTIVE],
        );

        $categories = Category::all()->keyBy('name');
        $current = now();
        $previous = now()->subMonth();

        $rows = [
            [$current->copy()->day(1), 'GAJI', 'income', 5000000, 'Gaji bulanan', 'manual'],
            [$current->copy()->day(2), 'MAKANAN', 'expense', 65000, 'Kopi di Starbucks', 'chat', 'Beli kopi di Starbucks 65 ribu'],
            [$current->copy()->day(2), 'TRANSPORT', 'expense', 28000, 'GoRide ke kantor', 'chat', 'Goride ke kantor 28 ribu'],
            [$current->copy()->day(3), 'BELANJA', 'expense', 450000, 'Belanja bulanan', 'manual'],
            [$current->copy()->day(4), 'TAGIHAN', 'expense', 350000, 'Bayar internet rumah', 'chat', 'Bayar internet rumah 350 ribu'],
            [$current->copy()->day(5), 'MAKANAN', 'expense', 52000, 'Makan siang ayam geprek', 'manual'],
            [$current->copy()->day(6), 'HIBURAN', 'expense', 89000, 'Langganan Spotify', 'manual'],
            [$current->copy()->day(7), 'TRANSPORT', 'expense', 150000, 'Isi bensin motor', 'chat', 'Isi bensin motor 150 ribu'],
            [$current->copy()->day(8), 'GAJI', 'income', 750000, 'Bonus proyek kecil', 'chat', 'Dapat bonus proyek kecil 750 ribu'],
            [$current->copy()->day(9), 'BELANJA', 'expense', 125000, 'Beli kemeja kerja', 'manual'],
            [$current->copy()->day(10), 'MAKANAN', 'expense', 85000, 'Dinner ramen', 'chat', 'Makan ramen 85 ribu'],
            [$current->copy()->day(11), 'TAGIHAN', 'expense', 225000, 'Bayar listrik', 'manual'],
            [$current->copy()->day(12), 'LAINNYA', 'expense', 40000, 'Donasi komunitas', 'manual'],
            [$current->copy()->day(13), 'MAKANAN', 'expense', 72000, 'Kopi dan pastry', 'chat', 'Beli kopi dan pastry 72 ribu'],
            [$current->copy()->day(13), 'GAJI', 'income', 300000, 'Fee konsultasi', 'chat', 'Dibayar fee konsultasi 300 ribu'],
            [$previous->copy()->day(2), 'GAJI', 'income', 5000000, 'Gaji bulan lalu', 'manual'],
            [$previous->copy()->day(3), 'MAKANAN', 'expense', 48000, 'Sarapan bubur ayam', 'manual'],
            [$previous->copy()->day(4), 'TRANSPORT', 'expense', 30000, 'Grab ke meeting', 'manual'],
            [$previous->copy()->day(5), 'BELANJA', 'expense', 520000, 'Belanja groceries', 'manual'],
            [$previous->copy()->day(6), 'TAGIHAN', 'expense', 350000, 'Bayar internet bulan lalu', 'manual'],
            [$previous->copy()->day(7), 'HIBURAN', 'expense', 125000, 'Tiket bioskop', 'chat', 'Beli tiket bioskop 125 ribu'],
            [$previous->copy()->day(8), 'MAKANAN', 'expense', 67000, 'Kopi meeting client', 'manual'],
            [$previous->copy()->day(9), 'TRANSPORT', 'expense', 175000, 'Service motor ringan', 'manual'],
            [$previous->copy()->day(10), 'GAJI', 'income', 1200000, 'Freelance landing page', 'chat', 'Gaji freelance landing page 1.2 juta'],
            [$previous->copy()->day(11), 'BELANJA', 'expense', 99000, 'Beli mouse pad', 'manual'],
            [$previous->copy()->day(12), 'MAKANAN', 'expense', 58000, 'Makan soto', 'manual'],
            [$previous->copy()->day(13), 'TAGIHAN', 'expense', 100000, 'Isi pulsa', 'manual'],
            [$previous->copy()->day(14), 'HIBURAN', 'expense', 59000, 'Top up game', 'manual'],
            [$previous->copy()->day(15), 'LAINNYA', 'expense', 85000, 'Print dokumen', 'manual'],
            [$previous->copy()->day(16), 'MAKANAN', 'expense', 92000, 'Makan keluarga', 'manual'],
        ];

        Transaction::where('wallet_id', $wallet->id)->delete();

        foreach ($rows as $row) {
            [$date, $categoryName, $type, $amount, $description, $source] = $row;

            Transaction::create([
                'wallet_id' => $wallet->id,
                'category_id' => $categories[$categoryName]->id,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'source' => $source,
                'raw_message' => $row[6] ?? null,
                'status' => Transaction::STATUS_COMPLETED,
                'transaction_date' => Carbon::parse($date)->setTime(9, 0),
            ]);
        }

        $income = Transaction::where('wallet_id', $wallet->id)->where('type', Transaction::TYPE_INCOME)->sum('amount');
        $expense = Transaction::where('wallet_id', $wallet->id)->where('type', Transaction::TYPE_EXPENSE)->sum('amount');

        $wallet->forceFill([
            'balance' => number_format(((float) $income) - ((float) $expense), 2, '.', ''),
        ])->save();
    }
}
