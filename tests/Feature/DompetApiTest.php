<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
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

    public function test_frontend_gap_transaction_and_wallet_endpoints(): void
    {
        $user = User::factory()->create([
            'email' => 'sender@example.com',
        ]);
        $recipient = User::factory()->create([
            'email' => 'recipient@example.com',
        ]);
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 1000000, 'status' => Wallet::STATUS_ACTIVE]);
        Wallet::create(['user_id' => $recipient->id, 'balance' => 250000, 'status' => Wallet::STATUS_ACTIVE]);
        $food = Category::where('name', 'MAKANAN')->firstOrFail();

        $transaction = Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $food->id,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => 35000,
            'description' => 'Beli makan siang',
            'status' => Transaction::STATUS_COMPLETED,
            'source' => Transaction::SOURCE_MANUAL,
            'transaction_date' => now(),
        ]);

        $headers = $this->authHeaders($user);

        $this->getJson("/api/transactions/{$transaction->id}", $headers)
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.amount', 35000)
            ->assertJsonPath('data.category', 'MAKANAN');

        $otherUser = User::factory()->create();
        $otherWallet = Wallet::create(['user_id' => $otherUser->id, 'balance' => 0, 'status' => Wallet::STATUS_ACTIVE]);
        $otherTransaction = Transaction::create([
            'wallet_id' => $otherWallet->id,
            'category_id' => $food->id,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => 10000,
            'description' => 'Transaksi user lain',
            'status' => Transaction::STATUS_COMPLETED,
            'source' => Transaction::SOURCE_MANUAL,
            'transaction_date' => now(),
        ]);

        $this->getJson("/api/transactions/{$otherTransaction->id}", $headers)
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->getJson('/api/transactions/stats', $headers)
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.biggest', 35000)
            ->assertJsonPath('data.categories', 1);

        $this->getJson('/api/transactions/categories', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'MAKANAN')
            ->assertJsonPath('data.0.amount', 35000);

        $this->getJson('/api/wallet/balance', $headers)
            ->assertOk()
            ->assertJsonPath('data.balance', 1000000)
            ->assertJsonPath('data.total_expense', 35000);

        $this->postJson('/api/wallet/topup', ['amount' => 100000], $headers)
            ->assertOk()
            ->assertJsonPath('data.balance', 1100000)
            ->assertJsonPath('data.message', 'Top up berhasil.');

        $this->postJson('/api/wallet/topup', ['amount' => 0], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->postJson('/api/wallet/withdraw', [
            'amount' => 200000,
            'account_number' => '1234567890',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.balance', 900000)
            ->assertJsonPath('data.message', 'Penarikan berhasil diproses.');

        $this->postJson('/api/wallet/send', [
            'recipient_email' => 'recipient@example.com',
            'amount' => 50000,
            'note' => 'opsional',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.balance', 850000)
            ->assertJsonPath('data.message', 'Uang berhasil dikirim.');

        $this->postJson('/api/wallet/send', [
            'recipient_email' => 'missing@example.com',
            'amount' => 50000,
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->postJson('/api/wallet/send', [
            'recipient_email' => 'recipient@example.com',
            'amount' => 999999999,
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->postJson('/api/wallet/withdraw', [
            'amount' => 999999999,
            'account_number' => '1234567890',
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_dashboard_does_not_fail_when_categories_table_is_missing(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 0, 'status' => Wallet::STATUS_ACTIVE]);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => Transaction::TYPE_EXPENSE,
            'amount' => 35000,
            'description' => 'Beli makan siang',
            'status' => Transaction::STATUS_COMPLETED,
            'source' => Transaction::SOURCE_MANUAL,
            'transaction_date' => now(),
        ]);

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('categories');
        Schema::enableForeignKeyConstraints();

        $this->getJson('/api/dashboard', $this->authHeaders($user))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.insight_strip.text', 'Catatan bulan ini siap dipantau')
            ->assertJsonPath('data.recent_transactions.0.category_name', 'LAINNYA');
    }

    private function authHeaders(User $user): array
    {
        return [
            'Authorization' => 'Bearer '.JWTAuth::fromUser($user),
        ];
    }
}
