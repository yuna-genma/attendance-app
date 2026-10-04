<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class UserAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_view_user_login(): void
    {
        $response = $this->get(route('user.login'));

        $response->assertStatus(200);
    }

    /** @test */
    public function test_can_user_login()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('user.login'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function test_user_does_not_exist_cannot_login()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('user.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
        $this->assertGuest();
    }

    /** @test */
    public function test_user_login_email_required()
    {
        User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('user.login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
    }

    /** @test */
    public function test_user_login_password_required()
    {
        $user = User::factory()->create();

        $response = $this->post(route('user.login'), [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
    }

}
