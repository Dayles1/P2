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
            translationKey: 'messages.chat.system.member_left',
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
            translationKey: 'messages.chat.system.members_added',
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
            translationKey: 'messages.chat.system.members_removed',
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
            translationKey: 'messages.chat.system.member_joined',
            targets: [],
            extra: array_merge($extra, [
                'source' => [
                    'type' => $joinType,
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
            translationKey: 'messages.chat.system.conversation_renamed',
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
            translationKey: 'messages.chat.system.avatar_changed',
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
            translationKey: 'messages.chat.system.message_pinned',
            targets: [],
            extra: array_merge($extra, [
                'message_id' => $messageId,
            ]),
        );
    }

    private function create(
        Conversation $conversation,
        ?User $actor,
        string $translationKey,
        array $targets = [],
        array $extra = []
    ): Message {
        $replacements = [
            'actor' => $actor?->name ?? __('messages.chat.system.system_user'),
            'targets' => collect($targets)
                ->pluck('name')
                ->filter()
                ->implode(', '),
        ];

        $meta = [
            'action' => $translationKey,
            'display' => 'system',
            'translation_key' => $translationKey,
            'translation_params' => $replacements,
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
            'body' => __($translationKey, $replacements),
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
}