<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        $token = auth('api')->login($user);
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_notifications_list_returns_unread_count(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0, 'status' => 'active']);
        Notification::create([
            'user_id' => $user->id,
            'type' => 'budget_warning',
            'title' => 'Budget warning',
            'message' => 'Test',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $user->id,
            'type' => 'budget_risk',
            'title' => 'Budget risk',
            'message' => 'Test 2',
            'is_read' => true,
        ]);

        $response = $this->getJson('/api/v1/notifications', $this->authHeaders($user));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.unread_count', 1);
    }

    public function test_notifications_require_auth(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_mark_single_read(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0, 'status' => 'active']);
        $notif = Notification::create([
            'user_id' => $user->id,
            'type' => 'budget_warning',
            'title' => 'Budget warning',
            'message' => 'Test',
            'is_read' => false,
        ]);

        $response = $this->postJson("/api/v1/notifications/{$notif->id}/read", [], $this->authHeaders($user));
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', ['id' => $notif->id, 'is_read' => true]);
    }

    public function test_mark_all_read(): void
    {
        $user = User::factory()->create();
        Wallet::create(['user_id' => $user->id, 'balance_cents' => 0, 'status' => 'active']);
        Notification::create([
            'user_id' => $user->id, 'type' => 'a', 'title' => 'a', 'message' => 'a', 'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $user->id, 'type' => 'b', 'title' => 'b', 'message' => 'b', 'is_read' => false,
        ]);

        $response = $this->postJson('/api/v1/notifications/read-all', [], $this->authHeaders($user));
        $response->assertStatus(200);
        $this->assertEquals(0, Notification::where('user_id', $user->id)->where('is_read', false)->count());
    }

    public function test_scheduler_creates_notification_when_over_threshold(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $user->id, 'balance_cents' => 0, 'status' => 'active']);
        $cat = Category::where('name', 'MAKANAN')->firstOrFail();

        $budget = Budget::create([
            'user_id' => $user->id,
            'category_id' => $cat->id,
            'amount' => 100000,
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);

        // Spent 90k -> 90% -> warning
        \App\Models\Transaction::create([
            'wallet_id' => $wallet->id,
            'category_id' => $cat->id,
            'type' => \App\Models\Transaction::TYPE_EXPENSE,
            'amount' => 90000,
            'description' => 'Makan',
            'status' => \App\Models\Transaction::STATUS_COMPLETED,
            'source' => \App\Models\Transaction::SOURCE_MANUAL,
            'transaction_date' => now(),
        ]);

        $this->artisan('app:check-budget-notifications')->assertExitCode(0);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'budget_warning',
        ]);
    }
}