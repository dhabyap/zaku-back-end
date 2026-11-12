<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OnboardingStep1Request;
use App\Http\Requests\OnboardingStep2Request;
use App\Http\Requests\OnboardingStep3Request;
use App\Models\ActivityLog;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    /**
     * Get onboarding status
     *
     * @group Onboarding
     * @authenticated
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'step' => $user->onboarding_step,
                'completed' => $user->onboarding_completed,
                'needs_onboarding' => ! $user->onboarding_completed,
            ],
        ]);
    }

    /**
     * Step 1 — Profile basics
     *
     * Menyimpan nama lengkap dan nomor telepon.
     *
     * @group Onboarding
     * @authenticated
     */
    public function step1(OnboardingStep1Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'full_name' => $request->input('full_name'),
            'phone_number' => $request->input('phone_number'),
            'onboarding_step' => 1,
        ])->save();

        ActivityLog::create([
            'user_id' => $user->id,
            'loggable_id' => $user->id,
            'loggable_type' => \App\Models\User::class,
            'event' => 'onboarding_step1_completed',
            'old_values' => null,
            'new_values' => ['step' => 1, 'full_name' => $user->full_name],
            'description' => 'Onboarding langkah 1 selesai: profil dasar',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil dasar tersimpan',
            'data' => ['step' => 1, 'completed' => false],
        ]);
    }

    /**
     * Step 2 — Budget & preferences
     *
     * Menyimpan anggaran bulanan dan preferensi tema.
     *
     * @group Onboarding
     * @authenticated
     */
    public function step2(OnboardingStep2Request $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'monthly_budget' => $request->input('monthly_budget'),
            'theme_preference' => $request->input('theme_preference', 'light'),
            'onboarding_step' => 2,
        ])->save();

        // Auto-create a budget for the current month
        $now = now();
        Budget::firstOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => null,
                'month' => $now->format('Y-m'),
            ],
            [
                'amount_cents' => $request->input('monthly_budget') * 100,
                'spent_cents' => 0,
            ]
        );

        ActivityLog::create([
            'user_id' => $user->id,
            'loggable_id' => $user->id,
            'loggable_type' => \App\Models\User::class,
            'event' => 'onboarding_step2_completed',
            'old_values' => null,
            'new_values' => ['step' => 2, 'monthly_budget' => $user->monthly_budget],
            'description' => 'Onboarding langkah 2 selesai: anggaran bulanan',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Anggaran bulanan tersimpan',
            'data' => ['step' => 2, 'completed' => false],
        ]);
    }

    /**
     * Step 3 — First category + optional initial balance
     *
     * Membuat kategori pertama dan/atau mengatur saldo awal wallet.
     *
     * @group Onboarding
     * @authenticated
     */
    public function step3(OnboardingStep3Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            // Update or create the first category
            if ($request->filled('category_name')) {
                Category::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'name' => $request->input('category_name'),
                    ],
                    [
                        'type' => $request->input('category_type', 'expense'),
                        'icon' => $request->input('category_icon', '📄'),
                        'color' => $request->input('category_color', '#6b7280'),
                        'is_active' => true,
                    ]
                );
            }

            // Update wallet balance if initial balance provided
            $initialBalance = $request->input('initial_balance');
            if ($initialBalance !== null && $initialBalance > 0) {
                $wallet = Wallet::where('user_id', $user->id)->first();
                if ($wallet) {
                    $wallet->forceFill([
                        'balance_cents' => $wallet->balance_cents + ((float) $initialBalance * 100),
                    ])->save();

                    // Record as income transaction
                    Transaction::create([
                        'user_id' => $user->id,
                        'category_id' => null,
                        'wallet_id' => $wallet->id,
                        'type' => 'income',
                        'amount_cents' => (float) $initialBalance * 100,
                        'description' => 'Saldo awal',
                        'transaction_date' => now(),
                        'is_verified' => true,
                    ]);
                }
            }

            // Mark onboarding complete
            $user->forceFill([
                'onboarding_completed' => true,
                'onboarding_step' => 3,
            ])->save();
        });

        ActivityLog::create([
            'user_id' => $user->id,
            'loggable_id' => $user->id,
            'loggable_type' => \App\Models\User::class,
            'event' => 'onboarding_completed',
            'old_values' => null,
            'new_values' => ['step' => 3],
            'description' => 'Onboarding selesai',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Onboarding selesai! Selamat menggunakan Zaku.',
            'data' => [
                'step' => 3,
                'completed' => true,
                'user' => new \App\Http\Resources\UserResource($user->fresh()),
            ],
        ]);
    }
}
