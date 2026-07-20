<?php

namespace App\Infrastructure\Persistence\Eloquent\Chat;

use App\Domain\Chat\Enums\ConversationLeftReason;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\ConversationUser;
use App\Domain\Chat\Repositories\ConversationRepositoryInterface;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function getUserConversations(
        User $user,
        array $filters
    ) {
        $query = $user->conversations()
            ->with([
                'users:id,name',
                'users.avatar',
                'lastMessage.sender:id,name',
            ])
            ->withPivot([
                'unread_count',
                'last_read_message_id',
                'last_read_at',
                'is_pinned',
                'is_hidden',
                'notifications_enabled',
            ]);

        $type = $filters['type'] ?? 'all';

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            if ($type === 'private') {
                $query->whereHas('users', function ($q) use ($user, $search) {
                    $q->where('users.id', '!=', $user->id)
                        ->where('users.name', 'like', "%{$search}%");
                });
            } else {
                $query->where('name', 'like', "%{$search}%");
            }
        }

        return $query
            ->orderByDesc('conversation_users.is_pinned')
            ->orderByRaw('COALESCE(conversation_users.unread_count, 0) DESC')
            ->orderByDesc('last_message_at')
            ->paginate(30);
    }
    public function addMembers(Conversation $conversation, array $userIds, int $joinedBy): array
    {
        $userIds = collect($userIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $added = [];
        $restored = [];
        $skipped = [];

        DB::transaction(function () use ($conversation, $userIds, $joinedBy, &$added, &$restored, &$skipped) {
            $now = now();

            $activeIds = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->whereIn('user_id', $userIds)
                ->whereNull('left_at')
                ->pluck('user_id')
                ->all();

            $leftMembers = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->whereIn('user_id', $userIds)
                ->whereNotNull('left_at')
                ->get()
                ->keyBy('user_id');

            foreach ($userIds as $userId) {
                if (in_array($userId, $activeIds, true)) {
                    $skipped[] = $userId;
                    continue;
                }

                if ($leftMembers->has($userId)) {
                    $leftMembers[$userId]->update([
                        'left_at' => null,
                        'left_reason' => null,
                        'removed_by' => null,
                        'joined_by' => $joinedBy,
                        'joined_at' => $now,
                        'is_hidden' => false,
                        'is_pinned' => false,
                        'notifications_enabled' => true,
                    ]);

                    $restored[] = $userId;
                    continue;
                }

                ConversationUser::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'role' => 'member',
                    'joined_by' => $joinedBy,
                    'joined_at' => $now,
                    'is_hidden' => false,
                    'is_pinned' => false,
                    'notifications_enabled' => true,
                ]);

                $added[] = $userId;
            }
        });

        return [
            'added' => $added,
            'restored' => $restored,
            'skipped' => $skipped,
        ];
    }

    public function removeMembers(Conversation $conversation, array $userIds, int $removedBy): array
    {
        $userIds = collect($userIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $removed = [];
        $skipped = [];

        DB::transaction(function () use ($conversation, $userIds, $removedBy, &$removed, &$skipped) {
            foreach ($userIds as $userId) {
                $pivot = ConversationUser::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('user_id', $userId)
                    ->first();

                if (!$pivot || $pivot->left_at) {
                    $skipped[] = $userId;
                    continue;
                }

                if ($pivot->user_id === $conversation->owner_id) {
                    $skipped[] = $userId;
                    continue;
                }

                $pivot->update([
                    'left_at' => now(),
                    'left_reason' => ConversationLeftReason::REMOVED,
                    'removed_by' => $removedBy,
                    'is_hidden' => true,
                    'is_pinned' => false,
                ]);

                $removed[] = $userId;
            }

        });

        return [
            'removed' => $removed,
            'skipped' => $skipped,
        ];
    }

    public function store(array $data, int $actorId): Conversation
    {
        return DB::transaction(function () use ($data, $actorId) {
            $type = $data['type'];
            $now = now();

            $userIds = collect($data['user_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn ($id) => $id === $actorId)
                ->values();

            $privateKey = null;

            if ($type === 'private') {
                if ($userIds->count() !== 1) {
                    throw ValidationException::withMessages([
                        'user_ids' => __('messages.chat.private_chat_requires_one_user'),
                    ]);
                }

                $privateKey = $this->makePrivateKey($actorId, $userIds->first());

                $existing = Conversation::query()
                    ->where('type', 'private')
                    ->where('private_key', $privateKey)
                    ->first();

                if ($existing) {
                    $this->ensureParticipantsExist($existing, $actorId, $userIds->all());

                    return $existing;
                }
            }

            $conversation = Conversation::create([
                'type'         => $type,
                'title'        => $data['title'] ?? null,
                'created_by'   => $actorId,
                'owner_id'     => $actorId,
                'private_key'  => $privateKey,
            ]);

            ConversationUser::create([
                'conversation_id'       => $conversation->id,
                'user_id'               => $actorId,
                'role'                  => 'creator',
                'joined_at'             => $now,
                'joined_by'             => $actorId,
                'is_hidden'             => false,
                'is_pinned'             => false,
                'notifications_enabled' => true,
            ]);

            $members = $userIds
                ->map(fn ($id) => [
                    'conversation_id'       => $conversation->id,
                    'user_id'               => $id,
                    'role'                  => 'member',
                    'joined_at'             => $now,
                    'joined_by'             => $actorId,
                    'is_hidden'             => false,
                    'is_pinned'             => false,
                    'notifications_enabled' => true,
                ])
                ->all();

            if ($members !== []) {
                ConversationUser::insert($members);
            }

            return $conversation;
        });
    }
    protected function ensureParticipantsExist(Conversation $conversation, int $actorId, array $targetIds): void
    {
        $now = now();

        $ids = collect([$actorId, ...$targetIds])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($ids as $userId) {
            $pivot = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $userId)
                ->first();

            if ($pivot) {
                if ($pivot->left_at) {
                    $pivot->update([
                        'left_at'              => null,
                        'left_reason'          => null,
                        'removed_by'           => null,
                        'joined_by'            => $actorId,
                        'joined_at'            => $now,
                        'is_hidden'            => false,
                        'is_pinned'            => false,
                        'notifications_enabled'=> true,
                    ]);
                }

                continue;
            }

            ConversationUser::create([
                'conversation_id'       => $conversation->id,
                'user_id'               => $userId,
                'role'                  => $userId === $actorId ? 'creator' : 'member',
                'joined_at'             => $now,
                'joined_by'             => $actorId,
                'is_hidden'             => false,
                'is_pinned'             => false,
                'notifications_enabled' => true,
            ]);
        }
    }

    protected function makePrivateKey(int $userId1, int $userId2): string
    {
        return collect([$userId1, $userId2])
            ->sort()
            ->values()
            ->implode(':');
    }
}