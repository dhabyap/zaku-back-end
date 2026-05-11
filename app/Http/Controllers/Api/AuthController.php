<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\EmailRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\Wallet;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'email' => $request->string('email')->lower()->toString(),
                'password' => $request->string('password')->toString(),
                'full_name' => $request->string('full_name')->toString(),
                'phone_number' => $request->input('phone_number'),
            ]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ]);

            $verificationCode = $this->createVerificationCode($user);
            $this->sendVerificationEmail($user, $verificationCode->code);

            return $user->load('wallet');
        });

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => JWTAuth::fromUser($user),
        ], 'User registered successfully', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->lower())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            return $this->unauthorizedResponse('Invalid credentials.');
        }

        if (! $user->isVerified()) {
            return $this->forbiddenResponse('Email address is not verified.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->successResponse([
            'token' => JWTAuth::fromUser($user),
            'user' => new UserResource($user->load('wallet')),
        ], 'Login successful');
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $verificationCode = VerificationCode::forUser((int) $request->input('user_id'))
            ->where('code', $request->string('code')->toString())
            ->first();

        if (! $verificationCode) {
            return $this->notFoundResponse('Verification code not found.');
        }

        if ($verificationCode->isUsed()) {
            return $this->errorResponse('Verification code has already been used.', 422);
        }

        if ($verificationCode->isExpired()) {
            return $this->errorResponse('Verification code has expired.', 422);
        }

        DB::transaction(function () use ($verificationCode) {
            $verificationCode->user->forceFill([
                'is_verified' => true,
                'verification_code' => null,
            ])->save();

            $verificationCode->markAsUsed();
        });

        return $this->successResponse(null, 'Email verified successfully');
    }

    public function resendVerification(EmailRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->lower())->first();

        if (! $user) {
            return $this->successResponse(null, 'Verification code sent to your email');
        }

        if ($user->isVerified()) {
            return $this->errorResponse('Email address is already verified.', 422);
        }

        $verificationCode = DB::transaction(function () use ($user) {
            VerificationCode::forUser($user->id)->unused()->update([
                'is_used' => true,
                'used_at' => now(),
            ]);

            return $this->createVerificationCode($user);
        });

        $this->sendVerificationEmail($user, $verificationCode->code);

        return $this->successResponse(null, 'Verification code sent to your email');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('old_password')->toString(), $user->password)) {
            return $this->errorResponse('Old password is incorrect.', 422);
        }

        $user->forceFill([
            'password' => $request->string('new_password')->toString(),
        ])->save();

        return $this->successResponse(null, 'Password changed successfully');
    }

    public function forgotPassword(EmailRequest $request): JsonResponse
    {
        $email = $request->string('email')->lower()->toString();
        $user = User::where('email', $email)->first();

        if ($user) {
            $token = VerificationCode::generateToken();

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ],
            );

            Mail::raw("Use this password reset token: {$token}", function ($message) use ($user) {
                $message->to($user->email)->subject('Reset your password');
            });
        }

        return $this->successResponse(null, 'Password reset link sent to your email');
    }

    private function createVerificationCode(User $user): VerificationCode
    {
        $code = VerificationCode::create([
            'user_id' => $user->id,
            'code' => VerificationCode::generateCode(),
            'expires_at' => now()->addHours(24),
        ]);

        $user->forceFill(['verification_code' => $code->code])->save();

        return $code;
    }

    private function sendVerificationEmail(User $user, string $code): void
    {
        Mail::raw("Your verification code is: {$code}", function ($message) use ($user) {
            $message->to($user->email)->subject('Verify your email address');
        });
    }
}
