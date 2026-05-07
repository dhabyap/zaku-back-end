<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtConfigurationTest extends TestCase
{
    public function test_jwt_authentication_configuration_can_issue_token(): void
    {
        config(['jwt.secret' => 'test-jwt-secret']);

        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $user->id = 1;

        $token = JWTAuth::fromUser($user);

        $this->assertSame('api', config('auth.defaults.guard'));
        $this->assertSame('jwt', config('auth.guards.api.driver'));
        $this->assertNotEmpty($token);
    }
}
