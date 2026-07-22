<?php

namespace App\Domain\Chat\Actions\Messages;

use App\Domain\Chat\Events\MessageSent;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Domain\Chat\Repositories\MessageRepositoryInterface;
use App\Domain\Identity\Models\User;

class SendMessage
{
    public function __construct(
        protected MessageRepositoryInterface $repository,
    ) {}

    public function handle(User $user, Conversation $conversation, array $data): Message
    {
        $message = $this->repository->store($conversation, $user, $data);

        event(new MessageSent($message));

        return $message;
    }
}