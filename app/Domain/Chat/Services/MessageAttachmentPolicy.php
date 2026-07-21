<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Enums\MessageType;
use App\Domain\Setting\Services\SettingService;
use Illuminate\Http\UploadedFile;

class MessageAttachmentPolicy
{
    private ?int $maxUploadSizeKb = null;
    private ?array $cache = [];

    public function __construct(
        protected SettingService $settings,
    ) {
    }

    public function requiresAttachments(string $type): bool
    {
        return in_array(
            $type,
            $this->settings->json(
                'chat.attachment_message_types',
                ['image', 'video', 'audio', 'voice', 'file']
            ),
            true
        );
    }

    public function maxUploadSizeKb(): int
    {
        if ($this->maxUploadSizeKb === null) {
            $this->maxUploadSizeKb = max(
                1,
                $this->settings->integer('upload.max_upload_size', 10240)
            );
        }

        return $this->maxUploadSizeKb;
    }

    public function maxAttachmentsCount(): int
    {
        return max(1, $this->settings->integer('chat.max_attachments_count', 10));
    }

    public function validate(UploadedFile $file, string $type): ?string
    {
        $sizeKb = (int) ceil(((int) $file->getSize()) / 1024);

        if ($sizeKb > $this->maxUploadSizeKb()) {
            return __('messages.chat.attachment_too_large', [
                'max' => $this->maxUploadSizeKb(),
            ]);
        }

        return match ($type) {
            MessageType::IMAGE->value => $this->validateByCategory(
                $file,
                'chat.image_extensions',
                'chat.image_mime_types',
                __('messages.chat.only_image_files_allowed')
            ),

            MessageType::VIDEO->value => $this->validateByCategory(
                $file,
                'chat.video_extensions',
                'chat.video_mime_types',
                __('messages.chat.only_video_files_allowed')
            ),

            MessageType::AUDIO->value => $this->validateByCategory(
                $file,
                'chat.audio_extensions',
                'chat.audio_mime_types',
                __('messages.chat.only_audio_files_allowed')
            ),

            MessageType::VOICE->value => $this->validateByCategory(
                $file,
                'chat.voice_extensions',
                'chat.voice_mime_types',
                __('messages.chat.only_audio_files_allowed')
            ),

            MessageType::FILE->value => $this->validateByCategory(
                $file,
                'chat.file_extensions',
                'chat.file_mime_types',
                __('messages.chat.file_type_not_allowed')
            ),

            default => null,
        };
    }

    private function validateByCategory(
        UploadedFile $file,
        string $extensionsKey,
        string $mimeTypesKey,
        string $fallbackMessage
    ): ?string {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        $allowedExtensions = $this->normalizedJson($extensionsKey);
        $allowedMimeTypes = $this->normalizedJson($mimeTypesKey);

        if ($allowedExtensions !== [] && ! in_array($extension, $allowedExtensions, true)) {
            return $fallbackMessage;
        }

        if ($allowedMimeTypes !== [] && ! in_array($mime, $allowedMimeTypes, true)) {
            return $fallbackMessage;
        }

        return null;
    }

    private function normalizedJson(string $key): array
    {
        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = array_values(array_filter(array_map(
                static fn ($value) => strtolower(trim((string) $value)),
                $this->settings->json($key, [])
            )));
        }

        return $this->cache[$key];
    }
}