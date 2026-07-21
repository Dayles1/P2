<?php

namespace App\Domain\Chat\Repositories;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Domain\Identity\Models\User;

interface MessageRepositoryInterface
{
    public function store(Conversation $conversation, User $user, array $data): Message;
}