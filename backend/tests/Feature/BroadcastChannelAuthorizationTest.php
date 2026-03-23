<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BroadcastChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        $broadcastManager = $this->app->make(BroadcastManager::class);
        $broadcastManager->purge();

        require base_path('routes/channels.php');
    }

    protected function tearDown(): void
    {
        config(['broadcasting.default' => env('BROADCAST_CONNECTION', 'log')]);

        $this->app->make(BroadcastManager::class)->purge();

        parent::tearDown();
    }

    public function test_broadcasting_auth_allows_participant(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $userA->id,
            'user2_id' => $userB->id,
        ]);

        Sanctum::actingAs($userA, ['*']);

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-conversation.'.$conversation->id,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['auth']);
    }

    public function test_broadcasting_auth_denies_non_participant(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $intruder = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $userA->id,
            'user2_id' => $userB->id,
        ]);

        Sanctum::actingAs($intruder, ['*']);

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-conversation.'.$conversation->id,
        ]);

        $response->assertForbidden();
    }

    public function test_broadcasting_auth_returns_401_when_unauthenticated(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::factory()->create([
            'user1_id' => $userA->id,
            'user2_id' => $userB->id,
        ]);

        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-conversation.'.$conversation->id,
        ]);

        $response->assertUnauthorized();
    }
}
