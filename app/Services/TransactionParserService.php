<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransactionParserService
{
    /**
     * @return array{description:string, amount:int, category:Category, type:string}
     */
    public function parse(string $message): array
    {
        $normalized = Str::lower($message);
        $amount = $this->parseAmount($normalized);
        $type = $this->parseType($normalized);
        $category = $this->parseCategory($normalized, $type);

        return [
            'description' => $this->parseDescription($message, $amount),
            'amount' => $amount,
            'category' => $category,
            'type' => $type,
        ];
    }

    private function parseAmount(string $message): int
    {
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(juta|jt|miliar|milyar|ribu|rb|k)\b/u', $message, $matches)) {
            $number = (float) str_replace(',', '.', $matches[1]);
            $multiplier = match ($matches[2]) {
                'juta', 'jt' => 1000000,
                'miliar', 'milyar' => 1000000000,
                default => 1000,
            };

            return (int) round($number * $multiplier);
        }

        if (preg_match('/(?:rp\s*)?(\d[\d.,]*)/u', $message, $matches)) {
            return (int) preg_replace('/[^\d]/', '', $matches[1]);
        }

        return 0;
    }

    private function parseType(string $message): string
    {
        $incomeKeywords = ['gaji', 'bonus', 'dibayar', 'transfer masuk', 'dapat', 'pendapatan', 'fee'];

        foreach ($incomeKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                return Transaction::TYPE_INCOME;
            }
        }

        return Transaction::TYPE_EXPENSE;
    }

    private function parseCategory(string $message, string $type): Category
    {
        /** @var Collection<int, Category> $categories */
        $categories = Category::query()
            ->whereIn('type', [$type, Category::TYPE_BOTH])
            ->get();

        foreach ($categories as $category) {
            foreach ($category->keywords ?? [] as $keyword) {
                if (str_contains($message, Str::lower($keyword))) {
                    return $category;
                }
            }
        }

        if ($type === Transaction::TYPE_INCOME) {
            $salary = Category::where('name', 'GAJI')->first();

            if ($salary) {
                return $salary;
            }
        }

        return Category::firstOrCreate(
            ['name' => 'LAINNYA'],
            ['icon' => '📌', 'type' => Category::TYPE_BOTH, 'keywords' => []],
        );
    }

    private function parseDescription(string $message, int $amount): string
    {
        $description = preg_replace('/(?:rp\s*)?\d[\d.,]*(?:\s*(?:juta|jt|miliar|milyar|ribu|rb|k))?/iu', '', $message) ?? $message;
        $description = preg_replace('/\b(beli|bayar|buat|untuk|pengeluaran|pemasukan|dapat|transfer masuk)\b/iu', '', $description) ?? $description;
        $description = trim((string) preg_replace('/\s+/', ' ', $description));

        if ($description === '') {
            return $message;
        }

        return Str::ucfirst($description);
    }
}
