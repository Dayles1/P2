<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Enums\ConversationLeftReason;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\ConversationUser;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteConversation
{
    public function handle(User $user, Conversation $conversation): void
    {
        DB::transaction(function () use ($user, $conversation) {
            $pivot = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->first();

            if (! $pivot) {
                throw ValidationException::withMessages([
                    'conversation' => __('messages.chat.not_a_member'),
                ]);
            }

            $pivot->update([
                'left_at'     => now(),
                'left_reason' => ConversationLeftReason::LEFT,
                'is_hidden'   => true,
                'is_pinned'   => false,
            ]);
        });
    }
}