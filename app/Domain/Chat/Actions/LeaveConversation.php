<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Enums\ConversationLeftReason;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\ConversationUser;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class LeaveConversation
{
    public function handle(User $user, Conversation $conversation): void
    {
        DB::transaction(function () use ($user, $conversation) {
            $membership = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->first();

            if (! $membership) {
                throw new ModelNotFoundException('User is not an active participant of this conversation.');
            }

            $membership->update([
                'left_at' => now(),
                'left_reason' => ConversationLeftReason::LEFT,
                'is_hidden' => true,
            ]);
        });
    }
}