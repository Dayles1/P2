<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Enums\ConversationPermission;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Repositories\ConversationRepositoryInterface;
use App\Domain\Chat\Services\ConversationPermissionService;
use App\Domain\Chat\Services\SystemMessageService;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveConversationMembers
{
    public function __construct(
        protected ConversationRepositoryInterface $repository,
        protected ConversationPermissionService $permissionService,
        protected SystemMessageService $systemMessageService,
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

        $result = $this->repository->removeMembers(
            $conversation,
            $userIds,
            $actor->id
        );


        if ($result['removed'] !== []) {

            $targets = User::query()
                ->whereIn('id', $result['removed'])
                ->get();


            $message = $this->systemMessageService->deleteUsers(
                conversation: $conversation,
                actor: $actor,
                targets: $targets,
                extra: $result,
            );


            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ]);
        }


        return $result;
    }
}