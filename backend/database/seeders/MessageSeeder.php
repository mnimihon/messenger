<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Database\Factories\MessageFactory;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        /** @var MessageFactory $factory */
        $factory = Message::factory();
        $conversations = Conversation::all();
        foreach ($conversations as $conversation) {
            $senderID = rand(0, 1) ? $conversation->user1_id : $conversation->user2_id;
            $factory
                ->count(rand(5, 25))
                ->forConversation($conversation)
                ->forSender(User::find($senderID))
                ->create();
        }
    }
}
