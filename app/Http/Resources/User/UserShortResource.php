<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Attachment\AttachmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserShortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'avatar' => AttachmentResource::make(
                $this->whenLoaded('avatar')
            ),
        ];
    }
}