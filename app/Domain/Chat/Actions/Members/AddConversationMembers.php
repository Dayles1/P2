<?php

namespace App\Domain\Chat\Actions\Members;

use App\Domain\Chat\Enums\ConversationPermission;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Repositories\ConversationRepositoryInterface;
use App\Domain\Chat\Services\ConversationPermissionService;
use App\Domain\Chat\Services\SystemMessageService;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;

class AddConversationMembers
{
    public function __construct(
        protected ConversationRepositoryInterface $repository,
        protected ConversationPermissionService $permissionService,
        protected SystemMessageService $systemMessageService,
    ) {
    }

    public function handle(User $actor, Conversation $conversation, array $userIds): array
    {
        $this->permissionService->authorize(
            $actor,
            $conversation,
            ConversationPermission::MANAGE_MEMBERS
        );

        if (! in_array($conversation->type, ['group', 'channel'], true)) {
            throw ValidationException::withMessages([
                'conversation' => __('messages.chat.cannot_add_member_to_private_chat'),
            ]);
        }

        $result = $this->repository->addMembers($conversation, $userIds, $actor->id);

        $targetIds = array_values(array_unique(array_merge(
            $result['added'] ?? [],
            $result['restored'] ?? []
        )));

        if ($targetIds !== []) {
            $targets = User::query()
                ->whereIn('id', $targetIds)
                ->get();

            $message = $this->systemMessageService->addUsers(
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