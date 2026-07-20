<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\ConversationUser;
use App\Domain\Chat\Services\SystemMessageService;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class LeaveConversation
{
    public function __construct(
        protected SystemMessageService $systemMessageService,
    ) {
    }

    public function handle(User $user, Conversation $conversation): Conversation
    {
        return DB::transaction(function () use ($user, $conversation) {
            $membership = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->first();

            if (! $membership) {
                throw new ModelNotFoundException('User is not an active participant of this conversation.');
            }

            $now = now();

            $membership->update([
                'left_at' => $now,
                'left_reason' => 'left',
                'is_hidden' => true,
            ]);

            $activeMembersCount = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->whereNull('left_at')
                ->count();

            if ($activeMembersCount === 0) {
                $conversation->update([
                    'owner_id' => null,
                    'is_archived' => true,
                ]);
            }

            $systemMessage = $this->systemMessageService->leaveUser(
                conversation: $conversation,
                actor: $user,
            );

            $conversation->update([
                'last_message_id' => $systemMessage->id,
                'last_message_at' => $now,
            ]);

            return $conversation->fresh([
                'creator',
                'owner',
                'users',
                'lastMessage',
            ]);
        });
    }
}