<?php

namespace App\Http\Requests\Chat;

use App\Domain\Chat\Enums\MessageType;
use App\Domain\Chat\Models\ConversationUser;
use App\Domain\Chat\Services\ChatMessageTypeService;
use App\Domain\Chat\Services\MessageAttachmentPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $body = $this->input('body');

        $this->merge([
            'type' => $this->input('type', MessageType::TEXT->value),
            'body' => is_string($body) ? trim($body) : $body,
        ]);
    }

    public function rules(): array
    {
        $conversationId = $this->conversationId();
        $typeService = $this->typeService();
        $filePolicy = $this->attachmentPolicy();

        return [
            'type' => [
                'required',
                'string',
                Rule::in($typeService->enabledTypes()),
            ],

            'body' => [
                Rule::requiredIf(fn () => $this->input('type') === MessageType::TEXT->value),
                'nullable',
                'string',
                'max:' . $typeService->maxBodyLength(),
            ],

            'parent_message_id' => [
                'nullable',
                'integer',
                Rule::exists('messages', 'id')->where(function ($query) use ($conversationId) {
                    $query->where('conversation_id', $conversationId)
                        ->whereNull('deleted_at');
                }),
            ],

            'attachments' => [
                Rule::requiredIf(fn () => $typeService->requiresAttachments($this->input('type'))),
                'nullable',
                'array',
                'max:' . $filePolicy->maxAttachmentsCount(),
            ],

            'attachments.*' => [
                'file',
                'max:' . $filePolicy->maxUploadSizeKb(),
            ],

            'meta' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $conversation = $this->route('conversation');
            $user = $this->user();

            if (! $conversation || ! $user) {
                return;
            }

            $type = $this->input('type');
            $files = $this->file('attachments', []);
            $typeService = $this->typeService();
            $filePolicy = $this->attachmentPolicy();

            $isParticipant = ConversationUser::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->whereNull('left_at')
                ->exists();

            if (! $isParticipant) {
                $validator->errors()->add(
                    'conversation',
                    __('messages.chat.user_not_in_conversation')
                );

                return;
            }

            if ($type === MessageType::TEXT->value && ! empty($files)) {
                $validator->errors()->add(
                    'attachments',
                    __('messages.chat.text_message_cannot_have_attachments')
                );

                return;
            }

            if ($typeService->requiresAttachments($type) && empty($files)) {
                $validator->errors()->add(
                    'attachments',
                    __('messages.chat.attachments_required')
                );

                return;
            }

            foreach ($files as $index => $file) {
                $error = $filePolicy->validate($file, $type);

                if ($error !== null) {
                    $validator->errors()->add("attachments.$index", $error);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.in' => __('messages.chat.invalid_message_type'),
            'body.required' => __('messages.chat.body_required'),
            'body.string' => __('validation.string', ['attribute' => 'body']),
            'body.max' => __('validation.max.string', ['attribute' => 'body']),
            'parent_message_id.exists' => __('messages.chat.invalid_reply_message'),
            'attachments.array' => __('messages.chat.attachments_must_be_array'),
            'attachments.max' => __('messages.chat.attachments_max_exceeded'),
            'attachments.*.file' => __('messages.chat.attachment_must_be_file'),
            'attachments.*.max' => __('messages.chat.attachment_too_large'),
            'meta.array' => __('validation.array', ['attribute' => 'meta']),
        ];
    }

    private function conversationId(): int|string|null
    {
        $conversation = $this->route('conversation');

        return is_object($conversation) ? $conversation->id : $conversation;
    }

    private function typeService(): ChatMessageTypeService
    {
        return app(ChatMessageTypeService::class);
    }

    private function attachmentPolicy(): MessageAttachmentPolicy
    {
        return app(MessageAttachmentPolicy::class);
    }
}