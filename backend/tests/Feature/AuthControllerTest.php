<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /*
     * REGISTER
     */
    public function test_register_creates_user_and_sends_verification_code(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Регистрация успешна. Требуется подтверждение email.',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertNotNull($user->verification_code);
        $this->assertNotNull($user->verification_code_expires_at);
        $this->assertNotNull($user->verification_code_sent_expires_at);

        Notification::assertSentTimes(
            \App\Notifications\EmailVerificationCodeNotification::class,
            1
        );
    }

    /*
     * VERIFY EMAIL
     */
    public function test_verify_email_returns_error_if_already_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email уже подтвержден',
            ]);
    }

    public function test_verify_email_fails_with_invalid_or_expired_code(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        $user->verification_code = 111111;
        $user->verification_code_expires_at = now()->addMinutes(10);
        $user->verification_code_sent_expires_at = now()->addSeconds(User::CAN_RESEND_AFTER);
        $user->save();

        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '222222',
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Неверный или истекший код подтверждения',
            ]);
    }

    public function test_verify_email_succeeds_with_valid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        $user->verification_code = 123456;
        $user->verification_code_expires_at = now()->addMinutes(10);
        $user->verification_code_sent_expires_at = now()->addSeconds(User::CAN_RESEND_AFTER);
        $user->save();

        $response = $this->postJson('/api/verify-email', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Email успешно подтвержден',
            ])
            ->assertJsonStructure([
                'access_token',
                'expires_at',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->verification_code);
        $this->assertNull($user->verification_code_expires_at);
        $this->assertNull($user->verification_code_sent_expires_at);
    }

    /*
     * RESEND CODE
     */
    public function test_resend_code_returns_error_if_email_already_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/resend-code', [
            'email' => $user->email,
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Email уже подтвержден',
            ]);
    }

    public function test_resend_code_returns_too_many_requests_if_recently_sent(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        $user->verification_code_sent_expires_at = now()->addSeconds(30);
        $user->save();

        $response = $this->postJson('/api/resend-code', [
            'email' => $user->email,
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Код уже был отправлен. Попробуйте через минуту.',
            ]);
    }

    public function test_resend_code_sends_new_code(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => null,
        ]);

        $user->verification_code = 111111;
        $user->verification_code_expires_at = now()->subMinutes(10);
        $user->verification_code_sent_expires_at = now()->subMinutes(2);
        $user->save();

        $response = $this->postJson('/api/resend-code', [
            'email' => $user->email,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Новый код подтверждения отправлен на ваш email',
            ]);

        $user->refresh();
        $this->assertNotNull($user->verification_code);
        $this->assertNotNull($user->verification_code_expires_at);
        $this->assertNotNull($user->verification_code_sent_expires_at);

        Notification::assertSentTimes(
            \App\Notifications\EmailVerificationCodeNotification::class,
            1
        );
    }

    /*
     * LOGIN
     */
    public function test_login_fails_when_too_many_attempts_already_locked(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'login_attempts' => User::LOGIN_ATTEMPTS,
            'login_locked_until' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
                'locked_until',
                'attempts_left',
            ]);
    }

    public function test_login_locks_after_reaching_attempt_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'login_attempts' => User::LOGIN_ATTEMPTS - 1,
            'login_locked_until' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Слишком много неудачных попыток входа. Доступ заблокирован на 15 минут.',
                'attempts_left' => 0,
            ])
            ->assertJsonStructure([
                'locked_until',
            ]);
    }

    public function test_login_fails_with_wrong_credentials_and_increments_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('correct-password'),
            'login_attempts' => 0,
            'login_locked_until' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
                'attempts_left',
            ]);

        $user->refresh();
        $this->assertEquals(1, $user->login_attempts);
    }

    public function test_login_fails_for_non_existing_user(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'some-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Неверный email или пароль',
            ]);
    }

    public function test_login_fails_if_email_not_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Email не подтвержден',
                'email' => $user->email,
            ]);
    }

    public function test_login_succeeds_and_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'login_attempts' => 2,
            'login_locked_until' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Вход выполнен успешно',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ])
            ->assertJsonStructure([
                'access_token',
                'expires_at',
                'user' => ['id', 'name', 'email'],
            ]);

        $user->refresh();
        $this->assertEquals(0, $user->login_attempts);
    }

    /*
     * FORGOT PASSWORD
     */
    public function test_forgot_password_fails_when_code_recently_sent(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $user->reset_password_code_sent_at = now()->subSeconds(30);
        $user->save();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
                'message' => 'Код уже был отправлен. Попробуйте через ' . User::RESET_PASSWORD_MINUTES . ' минуту.',
            ])
            ->assertJsonStructure([
                'can_resend_after',
            ]);
    }

    public function test_forgot_password_fails_when_locked(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $user->reset_password_locked_until = now()->addMinutes(10);
        $user->save();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
                'locked_until',
            ]);
    }

    public function test_forgot_password_sends_code(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Код для сброса пароля отправлен на ваш email',
            ]);

        Notification::assertSentTimes(
            \App\Notifications\ResetPasswordNotification::class,
            1
        );
    }

    /*
     * RESET PASSWORD
     */
    public function test_reset_password_fails_when_locked(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $user->reset_password_locked_until = now()->addMinutes(10);
        $user->reset_password_code = 123456;
        $user->reset_password_code_expires_at = now()->addMinutes(10);
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
                'locked_until',
            ]);
    }

    public function test_reset_password_fails_when_code_missing(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $user->reset_password_code = null;
        $user->reset_password_code_expires_at = null;
        $user->reset_password_locked_until = null;
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Код не найден. Запросите новый код.',
            ]);
    }

    public function test_reset_password_fails_when_code_expired(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $user->reset_password_code = 123456;
        $user->reset_password_code_expires_at = now()->subMinutes(1);
        $user->reset_password_locked_until = null;
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Код истек. Запросите новый код.',
            ]);
    }

    public function test_reset_password_fails_with_invalid_code_and_increments_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $user->reset_password_code = 111111;
        $user->reset_password_code_expires_at = now()->addMinutes(10);
        $user->reset_password_attempts = 0;
        $user->reset_password_locked_until = null;
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '222222',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
                'attempts_left',
            ]);

        $user->refresh();
        $this->assertEquals(1, $user->reset_password_attempts);
    }

    public function test_reset_password_locks_after_reaching_attempt_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $user->reset_password_code = 111111;
        $user->reset_password_code_expires_at = now()->addMinutes(10);
        $user->reset_password_attempts = User::RESET_PASSWORD_ATTEMPTS - 1;
        $user->reset_password_locked_until = null;
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '222222',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertStatus(429)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'message',
                'locked_until',
            ]);
    }

    public function test_reset_password_succeeds_with_valid_code(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $user->reset_password_code = 123456;
        $user->reset_password_code_expires_at = now()->addMinutes(10);
        $user->reset_password_attempts = 0;
        $user->reset_password_locked_until = null;
        $user->save();

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'code' => '123456',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Пароль успешно изменен',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ])
            ->assertJsonStructure([
                'access_token',
                'expires_at',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    /*
     * LOGOUT / USER
     */
    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/logout');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Успешный выход',
            ]);
    }

    public function test_user_returns_authenticated_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ])
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'created_at',
                'email_verified_at',
            ]);
    }
}

