<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_export_monthly_pdf_returns_pdf_download(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 5000000, 'status' => 'active']);
        $cat = Category::where('name', 'MAKANAN')->firstOrFail();

        Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => 150000,
            'description' => 'Makan siang',
            'status' => Transaction::STATUS_COMPLETED,
            'source' => Transaction::SOURCE_MANUAL,
            'transaction_date' => now()->subMonth()->startOfMonth()->addDays(3),
        ]);

        $prev = now()->subMonth();
        $response = $this->getJson(
            "/api/v1/export/monthly-pdf?month={$prev->month}&year={$prev->year}",
            $this->authHeaders($user)
        );

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
        // PDF magic number
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_export_requires_auth(): void
    {
        $prev = now()->subMonth();
        $this->getJson("/api/v1/export/monthly-pdf?month={$prev->month}&year={$prev->year}")
            ->assertStatus(401);
    }
}