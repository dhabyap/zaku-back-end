<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ZakuApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => 'test-jwt-secret']);
        $this->seed(\Database\Seeders\CategorySeeder::class);
    }

    public function test_register_and_login_follow_zaku_contract(): void
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
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.name', 'Budi Santoso')
            ->assertJsonPath('data.user.avatar_initial', 'B')
            ->assertJsonStructure(['data' => ['token']]);

        $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'password123',
        ])->assertForbidden()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Email address is not verified.');

        $code = \App\Models\VerificationCode::whereHas('user', fn ($query) => $query->where('email', 'budi@example.com'))->firstOrFail();

        $this->postJson('/api/auth/verify-email', [
            'email' => 'budi@example.com',
            'code' => $code->code,
        ])->assertOk()
            ->assertJsonPath('status', 'success');

        $login = $this->postJson('/api/auth/login', [
            'email' => 'budi@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'success')
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
            'full_name' => 'Demo Zaku',
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
            ->assertJsonPath('data.net_cashflow', 4935000)
            ->assertJsonPath('data.monthly_budget', 4000000)
            ->assertJsonPath('data.budget_remaining', 3935000)
            ->assertJsonPath('data.budget_used_percentage', 2)
            ->assertJsonPath('data.budget_status', 'aman')
            ->assertJsonPath('data.top_spending_category.name', 'MAKANAN')
            ->assertJsonPath('data.top_spending_category.amount', 65000)
            ->assertJsonPath('data.top_spending_category.percentage', 100)
            ->assertJsonCount(2, 'data.recent_transactions');

        $this->getJson('/api/transactions?filter=MAKANAN', $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.groups.0.transactions.0.category_name', 'MAKANAN')
            ->assertJsonPath('data.meta.total', 1);

        $this->postJson('/api/transactions/chat', [
            'message' => 'Beli kopi di Starbucks 65 ribu',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.parsed_data.amount', 65000)
            ->assertJsonPath('data.parsed_data.category', 'MAKANAN')
            ->assertJsonPath('data.parsed_data.type', 'expense');

        $this->postJson('/api/ai/chat', [
            'message' => 'Beli makan siang 35rb',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.description', 'Makan siang')
            ->assertJsonPath('data.amount', 35000)
            ->assertJsonPath('data.amount_formatted', '-Rp 35.000')
            ->assertJsonPath('data.type', 'expense')
            ->assertJsonStructure([
                'data' => [
                    'response',
                    'description',
                    'amount',
                    'amount_formatted',
                    'category',
                    'type',
                ],
            ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Beli makan siang',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.amount', null)
            ->assertJsonPath('data.amount_formatted', null)
            ->assertJsonPath('data.category', null)
            ->assertJsonPath('data.type', null);

        config([
            'services.groq.key' => 'test-groq-key',
            'services.gemini.key' => 'test-gemini-key',
        ]);

        Http::fake([
            'api.groq.com/*' => Http::sequence()
                ->push([
                    'choices' => [
                        [
                            'message' => [
                                'content' => json_encode([
                                    'response' => 'Oke, makan siang udah dicatat.',
                                    'description' => 'Makan siang',
                                    'amount' => 35000,
                                    'category' => 'MAKANAN',
                                    'type' => 'expense',
                                ]),
                            ],
                        ],
                    ],
                ])
                ->push([
                    'error' => [
                        'message' => 'Rate limit exceeded',
                    ],
                ], 429),
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'response' => 'Gaji freelance sudah dicatat.',
                                        'description' => 'Gaji freelance',
                                        'amount' => 1000000,
                                        'category' => 'GAJI',
                                        'type' => 'income',
                                    ]),
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->postJson('/api/ai/chat', [
            'message' => 'Beli makan siang 35rb',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.response', 'Oke, makan siang udah dicatat.')
            ->assertJsonPath('data.description', 'Makan siang')
            ->assertJsonPath('data.amount', 35000)
            ->assertJsonPath('data.category', '☕ MAKANAN');

        Http::assertSentCount(1);

        $this->postJson('/api/ai/chat', [
            'message' => 'Gaji freelance 1 juta',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('data.response', 'Gaji freelance sudah dicatat.')
            ->assertJsonPath('data.description', 'Gaji freelance')
            ->assertJsonPath('data.amount', 1000000)
            ->assertJsonPath('data.amount_formatted', '+Rp 1.000.000')
            ->assertJsonPath('data.type', 'income');

        Http::assertSentCount(3);
    }

    public function test_transaction_tracking_endpoints_follow_zaku_scope(): void
    {
        $user = User::factory()->create([
            'email' => 'sender@example.com',
        ]);
        $wallet = Wallet::create(['user_id' => $user->id, 'balance' => 1000000, 'status' => Wallet::STATUS_ACTIVE]);
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

        $this->postJson('/api/transactions', [
            'type' => 'income',
            'amount' => 250000,
            'description' => 'Fee konsultasi',
            'category' => 'GAJI',
            'transaction_date' => now()->toDateString(),
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.type', 'income')
            ->assertJsonPath('data.amount', 250000)
            ->assertJsonPath('data.category_name', 'GAJI');

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

        $this->deleteJson("/api/transactions/{$otherTransaction->id}", [], $headers)
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->getJson('/api/transactions/stats', $headers)
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.biggest', 250000)
            ->assertJsonPath('data.categories', 2);

        $this->getJson('/api/transactions/categories', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.name', 'MAKANAN')
            ->assertJsonPath('data.0.amount', 35000);

        $this->getJson('/api/transactions?limit=1', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data.groups.0.transactions')
            ->assertJsonPath('data.meta.limit', 1)
            ->assertJsonPath('data.meta.has_more', true);

        $this->getJson('/api/wallet/balance', $headers)->assertNotFound();
        $this->postJson('/api/wallet/topup', ['amount' => 100000], $headers)->assertNotFound();
        $this->postJson('/api/wallet/withdraw', ['amount' => 200000], $headers)->assertNotFound();
        $this->postJson('/api/wallet/send', ['amount' => 50000], $headers)->assertNotFound();

        $this->deleteJson("/api/transactions/{$transaction->id}", [], $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.balance', 1285000);

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);

        $wallet->refresh();
        $this->assertSame('1285000.00', $wallet->balance);
    }

    public function test_password_reset_contract_resets_password_and_deletes_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $token = 'plain-reset-token';

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Password has been reset successfully');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_password_reset_rejects_invalid_and_expired_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'expired-reset@example.com',
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'missing-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Invalid password reset token.');

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('expired-token'),
            'created_at' => now()->subMinutes(120),
        ]);

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'expired-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Password reset token has expired.');

        $this->postJson('/api/auth/reset-password', [
            'email' => $user->email,
            'token' => 'expired-token',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Validation failed');
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
