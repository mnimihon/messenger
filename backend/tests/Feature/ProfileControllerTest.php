<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    /*
     * AUTHENTICATION (401)
     */
    public function test_show_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401);
    }

    public function test_update_name_returns_401_when_unauthenticated(): void
    {
        $response = $this->putJson('/api/profile/name', [
            'name' => 'New Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_password_returns_401_when_unauthenticated(): void
    {
        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }

    public function test_delete_account_returns_401_when_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/profile/delete_account', [
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    /*
     * SHOW
     */
    public function test_show_returns_profile_data_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/profile');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'John Doe',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name'],
            ]);
    }

    /*
     * UPDATE NAME
     */
    public function test_update_name_returns_422_when_name_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/profile/name', []);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_update_name_returns_422_when_name_exceeds_max_length(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/profile/name', [
            'name' => str_repeat('a', 256),
        ]);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_update_name_succeeds_and_returns_updated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'john@example.com',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/profile/name', [
            'name' => 'New Name',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Имя успешно изменено',
                'user' => [
                    'id' => $user->id,
                    'name' => 'New Name',
                    'email' => 'john@example.com',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'user' => ['id', 'name', 'email'],
            ]);

        $user->refresh();
        $this->assertSame('New Name', $user->name);
    }

    /*
     * UPDATE PASSWORD
     */
    public function test_update_password_returns_422_when_validation_fails(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'password',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_update_password_returns_403_when_current_password_wrong(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Неверный текущий пароль',
            ]);
    }

    public function test_update_password_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/profile/password', [
            'current_password' => 'old-password',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Пароль успешно изменен',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /*
     * DELETE ACCOUNT
     */
    public function test_delete_account_returns_422_when_password_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/profile/delete_account', []);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_delete_account_returns_403_when_password_wrong(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/profile/delete_account', [
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Неверный пароль',
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_delete_account_succeeds_and_soft_deletes_user(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('my-password'),
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/profile/delete_account', [
            'password' => 'my-password',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Аккаунт успешно удален',
            ]);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
