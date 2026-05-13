<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Services\TransactionParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionParserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_parser_handles_common_dompet_messages(): void
    {
        $parser = app(TransactionParserService::class);

        $cases = [
            ['Beli kopi di Starbucks 65 ribu', 65000, 'expense', 'MAKANAN'],
            ['Gaji freelance 2 juta', 2000000, 'income', 'GAJI'],
            ['Bayar internet 350rb', 350000, 'expense', 'TAGIHAN'],
            ['Dapat bonus 100k', 100000, 'income', 'GAJI'],
            ['Belanja bulanan 450000', 450000, 'expense', 'BELANJA'],
            ['Isi bensin 150 ribu', 150000, 'expense', 'TRANSPORT'],
            ['Tiket bioskop 125 ribu', 125000, 'expense', 'HIBURAN'],
            ['Print dokumen 85000', 85000, 'expense', 'LAINNYA'],
        ];

        foreach ($cases as [$message, $amount, $type, $category]) {
            $result = $parser->parse($message);

            $this->assertSame($amount, $result['amount']);
            $this->assertSame($type, $result['type']);
            $this->assertInstanceOf(Category::class, $result['category']);
            $this->assertSame($category, $result['category']->name);
        }
    }
}
