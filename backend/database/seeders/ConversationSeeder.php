<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        $shuffledUsers1 = $users->shuffle();
        $shuffledUsers2 = $users->shuffle();

        $pairs = [];

        foreach ($shuffledUsers1 as $user1) {
            foreach ($shuffledUsers2 as $user2) {
                if ($user1->id === $user2->id) continue;

                $pair = [$user1->id, $user2->id];
                $reversePair = [$user2->id, $user1->id];
                if (!in_array($pair, $pairs) && !in_array($reversePair, $pairs)) {
                    $pairs[] = $pair;

                    if (count($pairs) == 30) {
                        break 2;
                    }
                }
            }
        }

        foreach ($pairs as $pair) {
            Conversation::factory()->create(['user1_id' => $pair[0], 'user2_id' => $pair[1]]);
        }
    }
}
