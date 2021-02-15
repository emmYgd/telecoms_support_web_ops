<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use WithFaker;

    /**
     * A basic unit test example.
     *
     * @return void
     */

    public function testRegisterUser()
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->safeEmail,
            'phone' => '0810000006',
            'password' => 'bakinde',
            'confirm_password' => 'bakinde'
        ];

        $response = $this->postJson('/api/auth/register', $data);

        $response->assertStatus(200);
        $response->assertJson([
            'code' => 1,
            'message' => 'success',
            'short_description' => 'Successfully created account'
        ]);

    }

    public function testLoginUser()
    {
        $data = [
            'email' => 'qbotsford@example.com',
            'password' => 'bakinde'
        ];

        $response = $this->postJson('/api/auth/login', $data);

        $response->assertStatus(200);

        $response->assertJson([
            'code' => 1,
            'message' => 'success',
            'short_description' => 'Access granted',
            'data' => [
                'accessToken' => false
            ]
        ]);
    }

    public function testTwoFactorAuth()
    {

        $data = [
            'code' => 'ByyxBO'
        ];

        $response = $this->postJson('/api/auth/two-factor-auth', $data);

        $response->assertStatus(401);

    }

}
