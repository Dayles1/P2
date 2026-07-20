<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Enums\ConversationPermission;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Repositories\ConversationRepositoryInterface;
use App\Domain\Chat\Services\ConversationPermissionService;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveConversationMembers
{
    public function __construct(
        protected ConversationRepositoryInterface $repository,
        protected ConversationPermissionService $permissionService
    ) {
    }

    public function handle(
        User $actor,
        Conversation $conversation,
        array $userIds
    ): array {
        $this->permissionService->authorize(
            $actor,
            $conversation,
            ConversationPermission::MANAGE_MEMBERS
        );

        if ($conversation->type === 'private') {
            throw ValidationException::withMessages([
                'conversation' => __('messages.chat.cannot_remove_member_from_private_chat'),
            ]);
        }

        return $this->repository->removeMembers(
            $conversation,
            $userIds,
            $actor->id
        );
    }
}