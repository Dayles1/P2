<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Enums\MessageType;
use App\Domain\Setting\Services\SettingService;

class ChatMessageTypeService
{
    private ?array $enabledTypes = null;
    private ?array $attachmentTypes = null;
    private ?int $maxBodyLength = null;
    private ?int $maxAttachmentsCount = null;

    public function __construct(
        protected SettingService $settings,
    ) {
    }

    public function enabledTypes(): array
    {
        if ($this->enabledTypes === null) {
            $configured = $this->settings->json(
                'chat.enabled_message_types',
                MessageType::values()
            );

            $this->enabledTypes = array_values(array_intersect(
                $configured,
                MessageType::values()
            ));
        }

        return $this->enabledTypes;
    }

    public function attachmentTypes(): array
    {
        if ($this->attachmentTypes === null) {
            $configured = $this->settings->json(
                'chat.attachment_message_types',
                ['image', 'video', 'audio', 'voice', 'file']
            );

            $this->attachmentTypes = array_values(array_intersect(
                $configured,
                MessageType::values()
            ));
        }

        return $this->attachmentTypes;
    }

    public function requiresAttachments(string $type): bool
    {
        return in_array($type, $this->attachmentTypes(), true);
    }

    public function isEnabled(string $type): bool
    {
        return in_array($type, $this->enabledTypes(), true);
    }

    public function maxBodyLength(): int
    {
        if ($this->maxBodyLength === null) {
            $this->maxBodyLength = max(
                1,
                $this->settings->integer('chat.max_message_body_length', 5000)
            );
        }

        return $this->maxBodyLength;
    }

    public function maxAttachmentsCount(): int
    {
        if ($this->maxAttachmentsCount === null) {
            $this->maxAttachmentsCount = max(
                1,
                $this->settings->integer('chat.max_attachments_count', 10)
            );
        }

        return $this->maxAttachmentsCount;
    }
}