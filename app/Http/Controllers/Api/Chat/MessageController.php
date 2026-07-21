<?php

namespace App\Http\Controllers\Api\Chat;

use App\Domain\Chat\Actions\Messages\SendMessage;
use App\Domain\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Http\Resources\CHat\MessageResource;
use Symfony\Component\HttpFoundation\JsonResponse;

class MessageController extends Controller
{
    public function __construct(
        protected SendMessage $sendMessage,
    ) {
    }

    public function index()
    {

    }

    public function store(
        SendMessageRequest $request,
        Conversation $conversation
    ): JsonResponse {
        $message = $this->sendMessage->handle(
            user: $request->user(),
            conversation: $conversation,
            data: $request->validated()
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
