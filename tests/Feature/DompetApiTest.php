<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DompetApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'test-jwt-secret']);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_register_and_login_follow_dompet_contract(): void
    {
        Mail::fake();

        $register = $this->postJson('/api/auth/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $register->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.name', 'Budi Santoso')
            ->assertJsonPath('data.user.avatar_initial', 'B')
            ->assertJsonStructure(['data' => ['token']]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'budi@example.com');

        $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_profile_budget_dashboard_history_and_chat_endpoints(): void
    {
        $user = User::factory()->create([
            'full_name' => 'Demo DOMPET',
            'email' => 'demo@example.com',
            'monthly_budget' => 3000000,
        ]);
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0, 'status' => Wallet::STATUS_ACTIVE]);
        $food = Category::where('name', 'MAKANAN')->firstOrFail();
        $salary = Category::where('name', 'GAJI')->firstOrFail();

        Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $salary->id,
            'type' => Transaction::TYPE_INCOME,
            'amount' => 5000000,
            'description' => 'Gaji bulanan',
            'status' => Transaction::STATUS_COMPLETED,
            'source' => Transaction::SOURCE_MANUAL,
            'transaction_date' => now()->startOfMonth()->addDay(),
        ]);
        Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $food->id,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => 65000,
            'description' => 'Kopi di Starbucks',
            'status' => Transaction::STATUS_COMPLETED,
            'source' => Transaction::SOURCE_CHAT,
            'raw_message' => 'Beli kopi di Starbucks 65 ribu',
            'transaction_date' => now(),
        ]);

        $headers = $this->authHeaders($user);

        $this->getJson('/api/user/profile', $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.avatar_initial', 'D')
            ->assertJsonPath('data.budget.monthly_budget', 3000000)
            ->assertJsonPath('data.budget.budget_used', 65000);

        $this->putJson('/api/user/budget', ['monthly_budget' => 4000000], $headers)
            ->assertOk()
            ->assertJsonPath('data.monthly_budget', 4000000);

        $this->getJson('/api/dashboard', $headers)
            ->assertOk()
            ->assertJsonPath('data.total_income', 5000000)
            ->assertJsonPath('data.total_expense', 65000)
            ->assertJsonCount(2, 'data.recent_transactions');

        $this->getJson('/api/transactions?filter=MAKANAN', $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.transactions.0.category_name', 'MAKANAN');

        $this->postJson('/api/transactions/chat', [
            'message' => 'Beli kopi di Starbucks 65 ribu',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.parsed_data.amount', 65000)
            ->assertJsonPath('data.parsed_data.category', 'MAKANAN')
            ->assertJsonPath('data.parsed_data.type', 'expense');
    }

    private function authHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ];
    }
}
