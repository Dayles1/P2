<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Enums\ConversationPermission;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;

class ConversationPermissionService
{
    public function can(
        User $user,
        Conversation $conversation,
        ConversationPermission $permission
    ): bool {
        if ($conversation->owner_id === $user->id) {
            return true;
        }

        $member = $conversation->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (! $member) {
            return false;
        }

        return $member->permissions()
            ->where('permission_key', $permission->value)
            ->where('is_allowed', true)
            ->exists();
    }


    public function authorize(
        User $user,
        Conversation $conversation,
        ConversationPermission $permission
    ): void {
        if (! $this->can($user, $conversation, $permission)) {
            throw ValidationException::withMessages([
                'conversation' => __('messages.chat.not_allowed'),
            ]);
        }
    }
}