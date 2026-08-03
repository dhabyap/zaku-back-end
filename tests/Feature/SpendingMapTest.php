<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpendingMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_endpoint_filters_by_month_and_returns_expected_fields()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0, 'status' => Wallet::STATUS_ACTIVE]);

        $food = Category::create(['name' => 'MAKANAN', 'icon' => '☕', 'type' => 'expense']);
        $other = Category::create(['name' => 'LAINNYA', 'icon' => '📌', 'type' => 'both']);

        // transactions in June 2026
        Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $food->id,
            'amount' => 50000,
            'type' => Transaction::TYPE_EXPENSE,
            'description' => 'Beli makan',
            'source' => Transaction::SOURCE_MANUAL,
            'status' => Transaction::STATUS_COMPLETED,
            'transaction_date' => '2026-06-05',
        ]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $food->id,
            'amount' => 15000,
            'description' => 'Snack',
            'source' => Transaction::SOURCE_MANUAL,
            'type' => Transaction::TYPE_EXPENSE,
            'status' => Transaction::STATUS_COMPLETED,
            'transaction_date' => '2026-06-10',
        ]);

        // transaction outside month
        Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $other->id,
            'amount' => 20000,
            'description' => 'Lainnya',
            'source' => Transaction::SOURCE_MANUAL,
            'type' => Transaction::TYPE_EXPENSE,
            'status' => Transaction::STATUS_COMPLETED,
            'transaction_date' => '2026-05-10',
        ]);

        $jwt = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$jwt)
            ->getJson('/api/v1/transactions/categories?month=2026-06');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'icon',
                        'amount',
                        'transaction_count',
                        'percentage',
                        'pct',
                    ],
                ],
            ]);

        $body = $response->json('data');

        // only one category (MAKANAN) present for June
        $this->assertCount(1, $body);
        $this->assertEquals('MAKANAN', $body[0]['name']);
        $this->assertEquals(65000, $body[0]['amount']);
        $this->assertEquals(2, $body[0]['transaction_count']);
    }
}
