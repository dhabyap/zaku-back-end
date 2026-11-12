<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\EmailRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\VerificationCode;
use App\Models\Wallet;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register user
     *
     * Membuat user baru, ledger internal, kode verifikasi email, dan JWT token.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email user. Example: user@example.com
     * @bodyParam password string required Minimal 8 karakter. Example: password123
     * @bodyParam full_name string required Nama lengkap user. Example: John Doe
     * @bodyParam phone_number string Nomor telepon opsional. Example: 08123456789
     *
     * @response 201 {
     *   "status": "success",
     *   "message": "User registered successfully",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "full_name": "John Doe",
     *       "email": "user@example.com",
     *       "avatar_initial": "J"
     *     },
     *     "token": "jwt_token_here"
     *   }
     * }
     */
    public function register(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'email' => $request->string('email')->lower()->toString(),
                'password' => $request->string('password')->toString(),
                'full_name' => $request->input('name', $request->input('full_name')),
                'phone_number' => $request->input('phone_number'),
                'is_verified' => !config('app.feature_email_verification', false),
            ]);

            Wallet::create([
                'user_id' => $user->id,
                'balance_cents' => 0,
                'status' => Wallet::STATUS_ACTIVE,
            ]);

            return $user->load('wallet');
        });

        $token = JWTAuth::fromUser($user);

        // Log user registration
        ActivityLog::create([
            'user_id' => $user->id,
            'loggable_id' => $user->id,
            'loggable_type' => User::class,
            'event' => 'user_registered',
            'old_values' => null,
            'new_values' => ['email' => $user->email],
            'description' => 'User mendaftar akun baru',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse([
            'user' => new UserResource($user),
            'token' => $token,
            'needs_onboarding' => ! $user->onboarding_completed,
        ], 'User registered successfully', 201)
            ->withCookie($this->createAuthCookie($token, false));
    }

    /**
     * Login user
     *
     * Mengautentikasi user yang email-nya sudah diverifikasi dan mengembalikan JWT token.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email user. Example: user@example.com
     * @bodyParam password string required Password user. Example: password123
     * @bodyParam remember boolean Jika true, token berlaku 30 hari. Example: true
     *
     * @response {
     *   "status": "success",
     *   "message": "Login successful",
     *   "data": {
     *     "token": "jwt_token_here",
     *     "user": {
     *       "id": 1,
     *       "full_name": "John Doe",
     *       "email": "user@example.com",
     *       "is_verified": true
     *     }
     *   }
     * }
     * @response 401 {
     *   "status": "error",
     *   "message": "Email atau kata sandi yang Anda masukkan belum benar. Periksa kembali lalu coba lagi."
     * }
     * @response 403 {
     *   "status": "error",
     *   "message": "Email address is not verified."
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->lower())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            // Log failed login attempt
            if ($user) {
                ActivityLog::create([
                    'user_id' => $user->id,
                    'loggable_id' => $user->id,
                    'loggable_type' => User::class,
                    'event' => 'login_failed',
                    'old_values' => null,
                    'new_values' => ['reason' => 'invalid_password'],
                    'description' => 'Login gagal: password salah',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
            return $this->unauthorizedResponse('Email atau kata sandi yang Anda masukkan belum benar. Periksa kembali lalu coba lagi.');
        }

        if (! $user->isVerified() || $user->verification_code) {
            ActivityLog::create([
                'user_id' => $user->id,
                'loggable_id' => $user->id,
                'loggable_type' => User::class,
                'event' => 'login_failed',
                'old_values' => null,
                'new_values' => ['reason' => 'email_not_verified'],
                'description' => 'Login gagal: email belum diverifikasi',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->forbiddenResponse('Email Anda belum diverifikasi. Buka email dan klik tautan verifikasi sebelum masuk.');
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $jwtFactory = JWTAuth::factory();
        $defaultTtl = $jwtFactory->getTTL();
        $jwtFactory->setTTL($request->boolean('remember')
            ? (int) config('jwt.remember_ttl', 43200)
            : (int) config('jwt.ttl', 60));

        try {
            $token = JWTAuth::fromUser($user);
        } finally {
            // Avoid carrying per-login TTL into later requests in long-lived workers.
            $jwtFactory->setTTL($defaultTtl);
        }

        $remember = $request->boolean('remember');

        // Log successful login
        ActivityLog::create([
            'user_id' => $user->id,
            'loggable_id' => $user->id,
            'loggable_type' => User::class,
            'event' => 'login_success',
            'old_values' => null,
            'new_values' => ['remember' => $remember],
            'description' => 'Login berhasil',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse([
            'token' => $token,
            'user' => new UserResource($user->load('wallet')),
            'needs_onboarding' => ! $user->onboarding_completed,
        ], 'Anda berhasil masuk')
            ->withCookie($this->createAuthCookie($token, $remember));
    }

    /**
     * Get authenticated user profile
     *
     * Mengambil data profil user yang sedang login.
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response {
     *   "status": "success",
     *   "data": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "user@example.com"
     *   }
     * }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
        ], 'Data profil berhasil dimuat');
    }

    /**
     * Refresh JWT token
     *
     * Membuat token JWT baru dan menginvalidasi token lama.
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response {
     *   "status": "success",
     *   "data": {
     *     "access_token": "jwt_token_here",
     *     "refresh_token": "jwt_token_here"
     *   }
     * }
     */
    /**
     * Refresh JWT token (token rotation via blacklist)
     *
     * Menginvalidasi token lama (blacklist) dan mengembalikan token baru.
     * Access token TTL = JWT_TTL (default 1440 menit).
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response {
     *   "status": "success",
     *   "data": {
     *     "access_token": "jwt_token_here",
     *     "refresh_token": "jwt_token_here"
     *   }
     * }
     */
    public function refresh(): JsonResponse
    {
        $token = JWTAuth::parseToken()->refresh();

        return $this->successResponse([
            'access_token' => $token,
            'refresh_token' => $token,
        ], 'Sesi masuk berhasil diperbarui')
            ->withCookie($this->createAuthCookie($token, false));
    }

    /**
     * Logout user
     *
     * Menginvalidasi token JWT yang sedang digunakan.
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response {
     *   "status": "success",
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(): JsonResponse
    {
        $user = auth()->user();

        JWTAuth::parseToken()->invalidate();

        // Log logout
        if ($user) {
            ActivityLog::create([
                'user_id' => $user->id,
                'loggable_id' => $user->id,
                'loggable_type' => User::class,
                'event' => 'logout',
                'old_values' => null,
                'new_values' => null,
                'description' => 'Logout berhasil',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }

        return $this->successResponse(null, 'Anda berhasil keluar')
            ->withCookie($this->clearAuthCookie());
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $email = $request->string('email')->lower()->toString();
        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $row || ! Hash::check($request->string('token')->toString(), $row->token)) {
            return $this->errorResponse('Tautan ubah kata sandi tidak valid. Coba minta tautan baru.', 422, [
                'token' => ['Tautan ubah kata sandi tidak valid. Coba minta tautan baru.'],
            ]);
        }

        $expiresAt = Carbon::parse($row->created_at)->addMinutes(config('auth.passwords.users.expire', 60));
        if ($expiresAt->isPast()) {
            return $this->errorResponse('Tautan ubah kata sandi sudah kedaluwarsa. Coba minta tautan baru.', 422, [
                'token' => ['Tautan ubah kata sandi sudah kedaluwarsa. Coba minta tautan baru.'],
            ]);
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return $this->errorResponse('Tautan ubah kata sandi tidak valid. Coba minta tautan baru.', 422, [
                'token' => ['Tautan ubah kata sandi tidak valid. Coba minta tautan baru.'],
            ]);
        }

        $user->forceFill([
            'password' => $request->string('password')->toString(),
            'password_changed_at' => now(),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Log successful password reset
        ActivityLog::create([
            'user_id' => $user->id,
            'loggable_id' => $user->id,
            'loggable_type' => User::class,
            'event' => 'password_reset',
            'old_values' => null,
            'new_values' => null,
            'description' => 'Kata sandi berhasil direset via email',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse(null, 'Kata sandi berhasil diubah');
    }

    /**
     * Verify email
     *
     * Memvalidasi kode verifikasi dan menandai email user sebagai terverifikasi.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam user_id integer required ID user yang akan diverifikasi. Example: 1
     * @bodyParam code string required Kode verifikasi 6 digit dari email/log. Example: 123456
     *
     * @response {
     *   "status": "success",
     *   "message": "Email verified successfully"
     * }
     * @response 422 {
     *   "status": "error",
     *   "message": "Verification code has expired."
     * }
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $verificationCode = VerificationCode::where('code', $request->code)
            ->whereHas('user', function ($query) use ($request) {
                $query->where('email', $request->email);
            })
            ->first();

        if (! $verificationCode) {
            return $this->notFoundResponse('Kode verifikasi tidak ditemukan. Coba periksa email atau minta kode baru.');
        }

        if ($verificationCode->isUsed()) {
            return $this->errorResponse(
                'Kode verifikasi sudah pernah digunakan. Coba kirim kode baru.',
                422
            );
        }

        if ($verificationCode->isExpired()) {
            return $this->errorResponse(
                'Kode verifikasi sudah kedaluwarsa. Coba kirim kode baru.',
                422
            );
        }

        DB::transaction(function () use ($verificationCode) {

            $verificationCode->user->forceFill([
                'is_verified' => !config('app.feature_email_verification', false),
                'verification_code' => null,
            ])->save();

            $verificationCode->markAsUsed();

        });

        return $this->successResponse(
            null,
            'Email berhasil diverifikasi'
        );
    }

    /**
     * Resend verification code
     *
     * Mengirim ulang kode verifikasi untuk user yang belum terverifikasi.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email user. Example: user@example.com
     *
     * @response {
     *   "status": "success",
     *   "message": "Verification code sent to your email"
     * }
     */
    public function resendVerification(EmailRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->lower())->first();

        if (! $user) {
            return $this->successResponse(null, 'Kode verifikasi sudah dikirim ke email Anda');
        }

        if ($user->isVerified()) {
            return $this->errorResponse('Email ini sudah diverifikasi. Silakan masuk.', 422);
        }

        $verificationCode = DB::transaction(function () use ($user) {
            VerificationCode::forUser($user->id)->unused()->update([
                'is_used' => true,
                'used_at' => now(),
            ]);

            return $this->createVerificationCode($user);
        });

        $this->sendVerificationEmail($user, $verificationCode->code);

        return $this->successResponse(null, 'Kode verifikasi sudah dikirim ke email Anda');
    }

    /**
     * Change password
     *
     * Mengubah password user yang sedang login. Endpoint ini membutuhkan JWT Bearer token.
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @bodyParam old_password string required Password lama. Example: password123
     * @bodyParam new_password string required Password baru minimal 8 karakter. Example: password456
     * @bodyParam new_password_confirmation string required Konfirmasi password baru. Example: password456
     *
     * @response {
     *   "status": "success",
     *   "message": "Password changed successfully"
     * }
     * @response 401 {
     *   "status": "error",
     *   "message": "Authorization token is required."
     * }
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! Hash::check($request->string('old_password')->toString(), $user->password)) {
            // Log failed password change attempt
            ActivityLog::create([
                'user_id' => $user->id,
                'loggable_id' => $user->id,
                'loggable_type' => User::class,
                'event' => 'password_change_failed',
                'old_values' => null,
                'new_values' => ['reason' => 'invalid_current_password'],
                'description' => 'Ubah kata sandi gagal: kata sandi saat ini salah',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return $this->errorResponse('Kata sandi saat ini belum benar. Periksa kembali, lalu coba lagi.', 422);
        }

        $user->forceFill([
            'password' => $request->string('new_password')->toString(),
            'password_changed_at' => now(),
        ])->save();

        // Invalidate current token (user will need to login again)
        JWTAuth::parseToken()->invalidate();

        // Log successful password change
        ActivityLog::create([
            'user_id' => $user->id,
            'loggable_id' => $user->id,
            'loggable_type' => User::class,
            'event' => 'password_changed',
            'old_values' => null,
            'new_values' => null,
            'description' => 'Kata sandi berhasil diubah',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->successResponse(null, 'Kata sandi berhasil diubah. Silakan login kembali.');
    }

    /**
     * Forgot password
     *
     * Membuat token reset password dan mengirimkannya ke email user.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam email string required Email user. Example: user@example.com
     *
     * @response {
     *   "status": "success",
     *   "message": "Password reset link sent to your email"
     * }
     */
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

            // Log password reset request
            ActivityLog::create([
                'user_id' => $user->id,
                'loggable_id' => $user->id,
                'loggable_type' => User::class,
                'event' => 'password_reset_requested',
                'old_values' => null,
                'new_values' => null,
                'description' => 'User meminta reset kata sandi',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $this->successResponse(null, 'Tautan ubah kata sandi sudah dikirim ke email Anda');
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

    /**
     * Create httpOnly auth cookie
     */
    private function createAuthCookie(string $token, bool $remember = false): \Symfony\Component\HttpFoundation\Cookie
    {
        $ttl = $remember ? (int) config('jwt.remember_ttl', 43200) : (int) config('jwt.ttl', 60);
        $isProduction = app()->environment('production');
        $domain = $isProduction ? '.abysoft.my.id' : null;

        // Use 'lax' for cross-subdomain (frontend on zaku.abysoft.my.id, API on api-zaku.abysoft.my.id)
        return cookie('zaku_token', $token, $ttl, '/', $domain, $isProduction, true, false, 'lax');
    }

    /**
     * Clear auth cookie
     */
    private function clearAuthCookie(): \Illuminate\Cookie\CookieJar
    {
        $isProduction = app()->environment('production');
        $domain = $isProduction ? '.abysoft.my.id' : null;

        return cookie('zaku_token', '', -1, '/', $domain, $isProduction, true, false, 'lax');
    }
}
