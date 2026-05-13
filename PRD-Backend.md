# PRD - DOMPET Backend API
## Laravel REST API Server

**Project Name:** DOMPET Backend API  
**Language:** PHP with Laravel Framework  
**Database:** MySQL / PostgreSQL  
**Authentication:** JWT (JSON Web Tokens) or Laravel Sanctum  
**API Style:** RESTful JSON API  
**Hosting:** Shared Hosting (separate domain from frontend)  
**Target Audience:** Backend Developer / API Developer

---

## 🏗️ Tech Stack

- **Framework:** Laravel (Latest stable version)
- **Language:** PHP 8.1+
- **Database:** MySQL 8.0+ / PostgreSQL 13+
- **Authentication:** JWT (`tymon/jwt-auth`) or Laravel Sanctum
- **Package Manager:** Composer
- **API Documentation:** Swagger/OpenAPI (darkaonline/l5-swagger)
- **Email:** Laravel Mail with SMTP
- **Security:** HTTPS, Password hashing (bcrypt), CORS

---

## 📁 Project Setup

### Initial Setup Command
```bash
laravel new dompet-backend
cd dompet-backend
composer require tymon/jwt-auth
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```

### Folder Structure
```
/dompet-backend
├── /app
│   ├── /Http
│   │   ├── /Controllers
│   │   │   ├── /Api
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── WalletController.php
│   │   │   │   ├── TransactionController.php
│   │   │   │   └── UserController.php
│   │   │   └── Controller.php
│   │   ├── /Middleware
│   │   │   └── JwtMiddleware.php
│   │   ├── /Resources
│   │   │   ├── UserResource.php
│   │   │   ├── WalletResource.php
│   │   │   └── TransactionResource.php
│   │   └── /Requests
│   │       ├── LoginRequest.php
│   │       ├── RegisterRequest.php
│   │       └── TopupRequest.php
│   ├── /Models
│   │   ├── User.php
│   │   ├── Wallet.php
│   │   ├── Transaction.php
│   │   └── VerificationCode.php
│   ├── /Traits
│   │   └── ApiResponse.php (untuk consistent response format)
│   └── /Exceptions
│       └── ApiException.php
├── /database
│   ├── /migrations
│   │   ├── users_table.php
│   │   ├── wallets_table.php
│   │   ├── transactions_table.php
│   │   └── verification_codes_table.php
│   └── /seeders
│       └── DatabaseSeeder.php
├── /routes
│   ├── api.php (semua route API di sini)
│   └── web.php
├── /config
│   ├── auth.php
│   ├── jwt.php
│   └── cors.php
├── .env (environment variables)
├── composer.json
└── artisan
```

### Environment Configuration (.env)
```env
APP_NAME="DOMPET Backend"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.dompet.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=dompet_db
DB_USERNAME=dompet_user
DB_PASSWORD=strong_password_here

JWT_SECRET=your-jwt-secret-key-here
JWT_ALGORITHM=HS256

FRONTEND_URL=https://dompet.com
FRONTEND_URL_DEV=http://localhost:3000

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@dompet.com
MAIL_FROM_NAME="DOMPET"

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

---

## 🗄️ Database Schema

### Migration: users_table
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('email')->unique();
    $table->string('password');
    $table->string('nama');
    $table->string('nomor_telepon')->nullable();
    $table->boolean('is_verified')->default(false);
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamps();
    $table->index('email');
});
```

### Migration: wallets_table
```php
Schema::create('wallets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
    $table->decimal('saldo', 15, 2)->default(0);
    $table->string('currency', 3)->default('IDR');
    $table->timestamps();
    $table->index('user_id');
});
```

### Migration: transactions_table
```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
    $table->enum('tipe', ['top_up', 'withdraw', 'transfer_out', 'transfer_in']);
    $table->decimal('jumlah', 15, 2);
    $table->string('deskripsi')->nullable();
    $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
    $table->string('reference_id')->nullable();
    $table->timestamp('timestamp')->useCurrent();
    $table->timestamps();
    $table->index(['wallet_id', 'timestamp']);
});
```

### Migration: verification_codes_table
```php
Schema::create('verification_codes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('kode');
    $table->enum('tipe', ['email', 'password_reset'])->default('email');
    $table->timestamp('expired_at');
    $table->timestamp('used_at')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'expired_at']);
});
```

---

## 🔐 Phase 1: Authentication System

### Endpoint: POST /api/auth/register
**Description:** User registration dengan email & password

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePass123",
  "nama": "John Doe",
  "nomor_telepon": "08123456789"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your email.",
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "nama": "John Doe",
    "is_verified": false,
    "access_token": "eyJhbGc...",
    "refresh_token": "eyJhbGc..."
  }
}
```

**Implementation (AuthController@register):**
```php
public function register(RegisterRequest $request)
{
    DB::beginTransaction();
    try {
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nama' => $request->nama,
            'nomor_telepon' => $request->nomor_telepon,
        ]);
        
        // Create wallet for user
        Wallet::create(['user_id' => $user->id, 'saldo' => 0]);
        
        // Generate verification code
        $kode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        VerificationCode::create([
            'user_id' => $user->id,
            'kode' => $kode,
            'tipe' => 'email',
            'expired_at' => now()->addMinutes(10),
        ]);
        
        // Send verification email
        Mail::queue(new VerificationCodeMail($user, $kode));
        
        // Generate tokens
        $token = auth('api')->login($user);
        $refreshToken = auth('api')->refresh();
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please verify your email.',
            'data' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'nama' => $user->nama,
                'is_verified' => false,
                'access_token' => $token,
                'refresh_token' => $refreshToken,
            ]
        ], 201);
    } catch (Exception $e) {
        DB::rollBack();
        return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
    }
}
```

### Endpoint: POST /api/auth/login
**Description:** User login dengan email & password

**Request:**
```json
{
  "email": "user@example.com",
  "password": "SecurePass123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "nama": "John Doe",
    "is_verified": true,
    "access_token": "eyJhbGc...",
    "refresh_token": "eyJhbGc...",
    "wallet_balance": 50000
  }
}
```

**Implementation (AuthController@login):**
```php
public function login(LoginRequest $request)
{
    $credentials = $request->validated();
    
    if (!$token = auth('api')->attempt($credentials)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }
    
    $user = auth('api')->user();
    
    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user_id' => $user->id,
            'email' => $user->email,
            'nama' => $user->nama,
            'is_verified' => $user->is_verified,
            'access_token' => $token,
            'refresh_token' => auth('api')->refresh(),
            'wallet_balance' => $user->wallet->saldo,
        ]
    ]);
}
```

### Endpoint: GET /api/auth/me
**Middleware:** jwt.auth
**Description:** Get authenticated user profile

**Headers:**
```http
Authorization: Bearer {access_token}
Accept: application/json
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

### Endpoint: POST /api/auth/refresh
**Middleware:** jwt.auth
**Description:** Refresh JWT token dan invalidate token lama

**Headers:**
```http
Authorization: Bearer {access_token}
Accept: application/json
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGc...",
    "refresh_token": "eyJhbGc..."
  }
}
```

**Note:** Backend menggunakan `tymon/jwt-auth`; `refresh_token` dikembalikan untuk kompatibilitas frontend dan saat ini bernilai sama dengan token JWT baru.

### Endpoint: POST /api/auth/logout
**Middleware:** jwt.auth
**Description:** Logout user dan invalidate/blacklist token JWT aktif

**Headers:**
```http
Authorization: Bearer {access_token}
Accept: application/json
```

**Response (200):**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

### Endpoint: POST /api/auth/verify-email
**Description:** Verify email dengan kode yang dikirim

**Request:**
```json
{
  "email": "user@example.com",
  "kode": "123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Email verified successfully"
}
```

### Endpoint: POST /api/auth/resend-verification
**Description:** Resend verification code

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Verification code sent to your email"
}
```

### Endpoint: POST /api/auth/change-password
**Middleware:** jwt.auth
**Description:** Change user password

**Request:**
```json
{
  "old_password": "OldPass123",
  "new_password": "NewPass456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

### Endpoint: POST /api/auth/forgot-password
**Description:** Request password reset

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password reset code sent to your email"
}
```
