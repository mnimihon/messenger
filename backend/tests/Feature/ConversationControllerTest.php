<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    /*
     * AUTHENTICATION (401)
     */
    public function test_index_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/conversations');

        $response->assertStatus(401);
    }

    public function test_store_returns_401_when_unauthenticated(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->postJson('/api/conversations', [
            'other_user_id' => $otherUser->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_show_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/conversations/1');

        $response->assertStatus(401);
    }

    public function test_destroy_returns_401_when_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/conversations/1');

        $response->assertStatus(401);
    }

    /*
     * INDEX
     */
    public function test_index_returns_conversations_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/conversations');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /*
     * STORE
     */
    public function test_store_fails_when_creating_conversation_with_self(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/conversations', [
            'other_user_id' => $user->id,
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Не удается создать диалог с самим собой',
            ]);
    }

    public function test_store_returns_422_when_other_user_id_is_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/conversations', []);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['message']);
    }

    public function test_store_returns_422_when_other_user_id_is_not_integer(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/conversations', [
            'other_user_id' => 'not-an-integer',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['message']);
    }

    public function test_store_returns_422_when_other_user_does_not_exist(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $nonExistentUserId = 999999;

        $response = $this->postJson('/api/conversations', [
            'other_user_id' => $nonExistentUserId,
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure(['message']);
    }

    public function test_store_returns_existing_conversation_message_if_conversation_already_exists(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // существующий диалог между пользователями
        Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/conversations', [
            'other_user_id' => $otherUser->id,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Диалог уже существует',
            ]);
    }

    public function test_store_creates_new_conversation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/conversations', [
            'other_user_id' => $otherUser->id,
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Диалог успешно создан',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'other_user' => [
                        'id',
                        'name',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('conversations', [
            // порядок user1_id/user2_id задаётся сервисом, поэтому просто проверяем наличие записи с данными пользователями
            // один из вариантов
            'user1_id' => min($user->id, $otherUser->id),
            'user2_id' => max($user->id, $otherUser->id),
        ]);
    }

    /*
     * SHOW
     */
    public function test_show_returns_not_found_when_conversation_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/conversations/999999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Диалог не найден',
            ]);
    }

    public function test_show_returns_forbidden_when_user_has_no_access(): void
    {
        $user = User::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $userA->id,
            'user2_id' => $userB->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу',
            ]);
    }

    public function test_show_returns_conversation_when_user_has_access(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'other_user' => [
                        'id',
                        'name',
                    ],
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    /*
     * DESTROY
     */
    public function test_destroy_returns_not_found_when_conversation_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/conversations/999999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Диалог не найден',
            ]);
    }

    public function test_destroy_returns_forbidden_when_user_has_no_access(): void
    {
        $user = User::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $userA->id,
            'user2_id' => $userB->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/conversations/{$conversation->id}");

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу',
            ]);
    }

    public function test_destroy_deletes_conversation_when_user_has_access(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/conversations/{$conversation->id}");

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Диалог успешно удален',
            ]);

        $this->assertSoftDeleted('conversations', [
            'id' => $conversation->id,
        ]);
    }
}

