<?php

namespace App\Domain\Chat\Enums;

enum ConversationLeftReason: string
{
    case LEFT = 'left';
    case REMOVED = 'removed';
    case KICKED = 'kicked';
    case BANNED = 'banned';
}