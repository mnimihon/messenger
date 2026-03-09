<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhotoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /*
     * AUTHENTICATION (401)
     */
    public function test_index_returns_401_when_unauthenticated(): void
    {
        $response = $this->getJson('/api/photos');

        $response->assertStatus(401);
    }

    public function test_store_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/photos', []);

        $response->assertStatus(401);
    }

    public function test_set_main_returns_401_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/photos/1/set-main');

        $response->assertStatus(401);
    }

    public function test_destroy_returns_401_when_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/photos/1');

        $response->assertStatus(401);
    }

    /*
     * INDEX
     */
    public function test_index_returns_photos_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/photos');

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
    public function test_store_returns_422_when_photos_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/photos', []);

        $response
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_store_returns_422_when_photos_exceeds_max_count(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $files = [];
        for ($i = 0; $i < 11; $i++) {
            $files[] = UploadedFile::fake()->create('photo' . $i . '.jpg', 100, 'image/jpeg');
        }

        $response = $this->post('/api/photos', [
            'photos' => $files,
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure(['message']);
    }

    public function test_store_uploads_photos_and_returns_201(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $response = $this->post('/api/photos', [
            'photos' => [$file],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'uploaded',
                    'errors',
                    'total_photos',
                ],
            ]);

        $this->assertDatabaseHas('user_photos', [
            'user_id' => $user->id,
        ]);
    }

    /*
     * SET MAIN
     */
    public function test_set_main_returns_404_when_photo_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/photos/999999/set-main');

        $response->assertStatus(404);
    }

    public function test_set_main_returns_403_when_photo_belongs_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $photo = UserPhoto::create([
            'user_id' => $otherUser->id,
            'path' => 'users/' . $otherUser->id . '/photos/test.jpg',
            'is_approved' => true,
            'is_main' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/photos/' . $photo->id . '/set-main');

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Нет доступа',
            ]);
    }

    public function test_set_main_succeeds_when_photo_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $photo = UserPhoto::create([
            'user_id' => $user->id,
            'path' => 'users/' . $user->id . '/photos/test.jpg',
            'is_approved' => true,
            'is_main' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/photos/' . $photo->id . '/set-main');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Фото установлено как главное',
            ]);

        $photo->refresh();
        $this->assertTrue((bool) $photo->is_main);
    }

    /*
     * DESTROY
     */
    public function test_destroy_returns_404_when_photo_not_found(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/photos/999999');

        $response->assertStatus(404);
    }

    public function test_destroy_returns_403_when_photo_belongs_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $photo = UserPhoto::create([
            'user_id' => $otherUser->id,
            'path' => 'users/' . $otherUser->id . '/photos/test.jpg',
            'is_approved' => true,
            'is_main' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/photos/' . $photo->id);

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Нет доступа',
            ]);

        $this->assertDatabaseHas('user_photos', ['id' => $photo->id]);
    }

    public function test_destroy_deletes_photo_when_user_is_owner(): void
    {
        $user = User::factory()->create();

        $photo = UserPhoto::create([
            'user_id' => $user->id,
            'path' => 'users/' . $user->id . '/photos/test.jpg',
            'is_approved' => true,
            'is_main' => false,
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/photos/' . $photo->id);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Фото удалено',
            ]);

        $this->assertSoftDeleted('user_photos', ['id' => $photo->id]);
    }
}
