<?php

namespace App\Domain\Chat\Enums;

enum MessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case VIDEO = 'video';
    case AUDIO = 'audio';
    case VOICE = 'voice';
    case FILE = 'file';
    case LOCATION = 'location';
    case CONTACT = 'contact';
    case SYSTEM = 'system';

    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    public function requiresAttachments(): bool
    {
        return in_array($this, [
            self::IMAGE,
            self::VIDEO,
            self::AUDIO,
            self::VOICE,
            self::FILE,
        ], true);
    }

    public function isText(): bool
    {
        return $this === self::TEXT;
    }
}