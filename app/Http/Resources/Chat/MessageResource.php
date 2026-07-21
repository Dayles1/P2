<?php

namespace App\Http\Resources\Chat;

use App\Domain\Setting\Services\UserDateFormatter;
use App\Http\Resources\Attachment\AttachmentResource;
use App\Http\Resources\User\UserShortResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $formatter = app(UserDateFormatter::class);

        $user = $request->user();

        return [
            'id' => $this->id,

            'conversation_id' => $this->conversation_id,

            'type' => $this->type,

            'body' => $this->body,

            'meta' => $this->meta,

            'user' => UserShortResource::make(
                $this->whenLoaded('user')
            ),

            'reply_to' => self::make(
                $this->whenLoaded('parent')
            ),

            'attachments' => AttachmentResource::collection(
                $this->whenLoaded('attachments')
            ),

            'replies_count' => $this->whenCounted('replies'),

            'reactions_count' => $this->whenCounted('reactions'),

            'is_edited' => $formatter->format($this->is_edited, $user) !== null,

            'edited_at' => $formatter->format($this->edited_at, $user),

            'created_at' => $formatter->format($this->created_at, $user),

            'deleted_at' => $formatter->format($this->deleted_at, $user),
        ];
    }
}