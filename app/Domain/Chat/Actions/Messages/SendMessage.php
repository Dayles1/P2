<?php

namespace App\Domain\Chat\Actions\Messages;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Domain\Chat\Repositories\MessageRepositoryInterface;
use App\Domain\Identity\Models\User;

class SendMessage
{
    public function __construct(
        protected MessageRepositoryInterface $repository,
    ) {
    }

    public function handle(
        User $user,
        Conversation $conversation,
        array $data
    ): Message {
        return $this->repository->store(
            conversation: $conversation,
            user: $user,
            data: $data
        );
    }
}