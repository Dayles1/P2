<?php

namespace App\Infrastructure\Persistence\Eloquent\Chat;

use App\Domain\Attachment\Models\Attachment;
use App\Domain\Chat\Models\Conversation;
use App\Domain\Chat\Models\Message;
use App\Domain\Chat\Repositories\MessageRepositoryInterface;
use App\Domain\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MessageRepository implements MessageRepositoryInterface
{
    public function store(
        Conversation $conversation,
        User $user,
        array $data
    ): Message {
        return DB::transaction(function () use ($conversation, $user, $data) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'parent_message_id' => $data['parent_message_id'] ?? null,
                'type' => $data['type'],
                'body' => $data['body'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            $this->storeAttachments(
                message: $message,
                files: $data['attachments'] ?? []
            );

            $message->load(['user', 'parent', 'attachments']);

            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ]);

            return $message;
        });
    }

    private function storeAttachments(Message $message, array $files): void
    {
        if ($files === []) {
            return;
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $disk = 'public';
            $directory = "chat/conversations/{$message->conversation_id}/messages/{$message->id}";

            $originalName = $file->getClientOriginalName();
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $mimeType = (string) $file->getMimeType();
            $size = (int) $file->getSize();

            $storedPath = $file->store($directory, $disk);

            $message->attachments()->create([
                'collection' => 'attachments',
                'disk' => $disk,
                'path' => $storedPath,
                'original_name' => $originalName,
                'filename' => pathinfo($storedPath, PATHINFO_BASENAME),
                'extension' => $extension,
                'mime_type' => $mimeType,
                'size' => $size,
                'meta' => [
                    'message_type' => $message->type,
                ],
            ]);
        }
    }
}