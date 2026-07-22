<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Models\ConversationUser;
use App\Domain\Chat\Models\Message;
use App\Domain\Chat\Notifications\NewChatMessageNotification;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class MessageSideEffectsService
{
    public function handle(Message $message): void
    {
        $unreadRecipientIds = $this->unreadRecipientIds($message);

        if ($unreadRecipientIds->isNotEmpty()) {
            ConversationUser::query()
                ->where('conversation_id', $message->conversation_id)
                ->whereIn('user_id', $unreadRecipientIds->all())
                ->whereNull('left_at')
                ->increment('unread_count');
        }

        $notifiableUsers = User::query()
            ->whereIn('id', $this->notificationRecipientIds($message)->all())
            ->with('avatar')
            ->get();

        if ($notifiableUsers->isNotEmpty()) {
            Notification::send(
                $notifiableUsers,
                new NewChatMessageNotification($message)
            );
        }
    }

    private function unreadRecipientIds(Message $message): Collection
    {
        return ConversationUser::query()
            ->where('conversation_id', $message->conversation_id)
            ->where('user_id', '!=', $message->user_id)
            ->whereNull('left_at')
            ->pluck('user_id');
    }

    private function notificationRecipientIds(Message $message): Collection
    {
        return ConversationUser::query()
            ->where('conversation_id', $message->conversation_id)
            ->where('user_id', '!=', $message->user_id)
            ->whereNull('left_at')
            ->where('notifications_enabled', true)
            ->pluck('user_id');
    }
}