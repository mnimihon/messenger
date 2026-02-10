<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'message' => $this->faker->text(200),
            'is_read' => $this->faker->boolean(70),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function forConversation(Conversation $conversation): self
    {
        return $this->state([
            'conversation_id' => $conversation->id,
        ]);
    }

    public function forSender(User $user): self
    {
        return $this->state([
            'sender_id' => $user->id,
        ]);
    }
}
