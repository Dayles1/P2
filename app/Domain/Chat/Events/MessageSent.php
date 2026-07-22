<?php

namespace App\Domain\Chat\Events;

use App\Domain\Chat\Models\Message;
use App\Http\Resources\Chat\MessageResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Message $message,
    ) {
        Log::info('MessageSent Event Constructed');
    }

    public function broadcastOn(): array
    {
        Log::info('broadcastOn called');
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        Log::info('broadcastWith called');
        return [
            'message' => (new MessageResource($this->message))->resolve(),
        ];
    }
}