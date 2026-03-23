<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    /*
     * AUTHENTICATION (401)
     */
    public function test_index_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/messages?conversation_id=1');

        $response->assertStatus(401);
    }

    public function test_store_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/messages', [
            'conversation_id' => 1,
            'message' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    public function test_mark_as_read_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/messages/mark-read', [
            'message_ids' => [1],
        ]);

        $response->assertStatus(401);
    }

    public function test_destroy_returns_401_when_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/messages/1');

        $response->assertStatus(401);
    }

    /*
     * INDEX
     */
    public function test_index_returns_422_when_conversation_id_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/messages');

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_index_returns_422_when_conversation_does_not_exist(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/messages?conversation_id=999999');

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_index_returns_403_when_user_has_no_access_to_conversation(): void
    {
        $user = User::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $userA->id,
            'user2_id' => $userB->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/messages?conversation_id=' . $conversation->id);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу',
            ]);
    }

    public function test_index_returns_messages_when_user_has_access(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/messages?conversation_id=' . $conversation->id);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'messages',
                    'next_cursor',
                ],
            ]);
    }

    /*
     * STORE
     */
    public function test_store_returns_422_when_conversation_id_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages', [
            'message' => 'Hello',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_store_returns_422_when_message_missing(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => User::factory()->create()->id,
        ]);
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages', [
            'conversation_id' => $conversation->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_store_returns_422_when_conversation_does_not_exist(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages', [
            'conversation_id' => 999999,
            'message' => 'Hello',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_store_returns_403_when_user_has_no_access_to_conversation(): void
    {
        $user = User::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $userA->id,
            'user2_id' => $userB->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages', [
            'conversation_id' => $conversation->id,
            'message' => 'Hello',
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'У вас нет доступа к этому диалогу',
            ]);
    }

    public function test_store_creates_message_and_returns_201(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages', [
            'conversation_id' => $conversation->id,
            'message' => 'Hello world',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'message' => [
                    'id',
                    'conversation_id',
                    'sender' => ['id', 'name'],
                    'message',
                    'is_read',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
        ]);
    }

    /*
     * MARK AS READ
     */
    public function test_mark_as_read_returns_422_when_message_ids_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages/mark-read', []);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_mark_as_read_returns_422_when_message_does_not_exist(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages/mark-read', [
            'message_ids' => [999999],
        ]);

        $response
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['message']);
    }

    public function test_mark_as_read_returns_success_and_marked_count(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $otherUser->id,
            'is_read' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/messages/mark-read', [
            'message_ids' => [$message->id],
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Сообщения отмечены как прочитанные',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['marked_count'],
            ]);
    }

    /*
     * DESTROY
     */
    public function test_destroy_returns_404_when_message_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/messages/999999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Сообщение не найдено',
            ]);
    }

    public function test_destroy_returns_403_when_user_is_not_sender(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/messages/' . $message->id);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Вы можете удалять только свои сообщения',
            ]);
    }

    public function test_destroy_deletes_message_when_user_is_sender(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $user->id,
            'user2_id' => $otherUser->id,
        ]);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/messages/' . $message->id);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Сообщение удалено',
            ]);

        $this->assertSoftDeleted('messages', [
            'id' => $message->id,
        ]);
    }
}
