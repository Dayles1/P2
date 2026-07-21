<?php

namespace App\Http\Controllers\Api\Chat;

use App\Domain\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\CHat\MessageResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class MessageController extends Controller
{
    public function __construct(
        
    )
    {

    }

    public function index()
    {

    }

    public function store(
        SendMessageRequest $request,
        Conversation $conversation
    ): JsonResponse {
        $message = $this->sendMessage->handle(
            actor: $request->user(),
            conversation: $conversation,
            data: $request->validated(),
            files: $request->file('attachments', []),
        );

        return $this->success(
            new MessageResource($message),
            __('messages.chat.message_sent')
        );
    }

    public function show()
    {

    }

    public function update()
    {

    }

    public function destroy()
    {

    }

    public function resend()
    {

    }

    public function pin()
    {

    }

    public function unpin()
    {

    }
}
