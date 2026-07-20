<?php

namespace App\Domain\Chat\Actions;

use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Repositories\ConversationRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class ChatStore
{
    public function __construct(
        protected ConversationRepositoryInterface $repository
    ) {
    }

    public function handle(array $data): Conversation
    {
        return $this->repository->store($data, Auth::id());
    }
}