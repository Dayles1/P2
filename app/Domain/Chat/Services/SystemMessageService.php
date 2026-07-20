<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Domain\Identity\Models\User;

class SystemMessageService
{
    public function leaveUser(
        Conversation $conversation,
        User $actor,
        ?User $newOwner = null,
        array $extra = []
    ): Message {
        return $this->create(
            conversation: $conversation,
            actor: $actor,
            action: 'member_left',
            targets: [],
            extra: array_merge($extra, [
                'new_owner' => $newOwner ? $this->userPayload($newOwner) : null,
            ]),
        );
    }

    public function addUsers(
        Conversation $conversation,
        User $actor,
        iterable $targets,
        array $extra = []
    ): Message {
        return $this->create(
            conversation: $conversation,
            actor: $actor,
            action: 'members_added',
            targets: $this->normalizeUsers($targets),
            extra: $extra,
        );
    }

    public function deleteUsers(
        Conversation $conversation,
        User $actor,
        iterable $targets,
        array $extra = []
    ): Message {
        return $this->create(
            conversation: $conversation,
            actor: $actor,
            action: 'members_removed',
            targets: $this->normalizeUsers($targets),
            extra: $extra,
        );
    }

    public function joinUsers(
        Conversation $conversation,
        User $actor,
        string $joinType = 'simple',
        ?string $sourceValue = null,
        array $extra = []
    ): Message {
        return $this->create(
            conversation: $conversation,
            actor: $actor,
            action: 'member_joined',
            targets: [],
            extra: array_merge($extra, [
                'source' => [
                    'type' => $joinType,   // simple | link | code
                    'value' => $sourceValue,
                ],
            ]),
        );
    }

    public function renameConversation(
        Conversation $conversation,
        User $actor,
        string $oldTitle,
        string $newTitle,
        array $extra = []
    ): Message {
        return $this->create(
            conversation: $conversation,
            actor: $actor,
            action: 'conversation_renamed',
            targets: [],
            extra: array_merge($extra, [
                'old_title' => $oldTitle,
                'new_title' => $newTitle,
            ]),
        );
    }

    public function changeAvatar(
        Conversation $conversation,
        User $actor,
        ?int $avatarId = null,
        array $extra = []
    ): Message {
        return $this->create(
            conversation: $conversation,
            actor: $actor,
            action: 'avatar_changed',
            targets: [],
            extra: array_merge($extra, [
                'avatar_id' => $avatarId,
            ]),
        );
    }

    public function pinMessage(
        Conversation $conversation,
        User $actor,
        int $messageId,
        array $extra = []
    ): Message {
        return $this->create(
            conversation: $conversation,
            actor: $actor,
            action: 'message_pinned',
            targets: [],
            extra: array_merge($extra, [
                'message_id' => $messageId,
            ]),
        );
    }

    private function create(
        Conversation $conversation,
        ?User $actor,
        string $action,
        array $targets = [],
        array $extra = []
    ): Message {
        $meta = [
            'action' => $action,
            'display' => 'system',
            'actor' => $actor ? $this->userPayload($actor) : null,
            'targets' => $targets,
            'conversation' => [
                'id' => $conversation->id,
                'type' => $conversation->type,
                'title' => $conversation->title,
            ],
            'extra' => $extra,
        ];

        return Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $actor?->id,
            'type' => 'system',
            'body' => $this->buildBody($action, $actor, $targets, $extra),
            'meta' => $meta,
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    private function normalizeUsers(iterable $users): array
    {
        $payloads = [];

        foreach ($users as $user) {
            if ($user instanceof User) {
                $payloads[] = $this->userPayload($user);
                continue;
            }

            if (is_array($user) && isset($user['id'])) {
                $payloads[] = [
                    'id' => $user['id'],
                    'name' => $user['name'] ?? null,
                ];
            }
        }

        return array_values($payloads);
    }

    private function buildBody(
        string $action,
        ?User $actor,
        array $targets,
        array $extra = []
    ): string {
        $actorName = $actor?->name ?? 'System';
        $targetNames = collect($targets)
            ->pluck('name')
            ->filter()
            ->implode(', ');

        return match ($action) {
            'member_left' => isset($extra['new_owner']['name']) && $extra['new_owner']['name']
                ? "{$actorName} left the conversation. Ownership moved to {$extra['new_owner']['name']}."
                : "{$actorName} left the conversation.",

            'members_added' => $targetNames !== ''
                ? "{$actorName} added {$targetNames} to the conversation."
                : "{$actorName} added members to the conversation.",

            'members_removed' => $targetNames !== ''
                ? "{$actorName} removed {$targetNames} from the conversation."
                : "{$actorName} removed members from the conversation.",

            'member_joined' => isset($extra['source']['type']) && $extra['source']['type']
                ? "{$actorName} joined the conversation via {$extra['source']['type']}."
                : "{$actorName} joined the conversation.",

            'conversation_renamed' => isset($extra['new_title']) && $extra['new_title']
                ? "{$actorName} changed the conversation title to \"{$extra['new_title']}\"."
                : "{$actorName} changed the conversation title.",

            'avatar_changed' => "{$actorName} changed the conversation avatar.",

            'message_pinned' => "{$actorName} pinned a message.",

            default => "{$actorName} performed an action.",
        };
    }
}