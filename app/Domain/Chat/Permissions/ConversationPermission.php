<?php

namespace App\Domain\Chat\Enums;

enum ConversationPermission: string
{
    case MANAGE_MEMBERS = 'manage_members';
    case CHANGE_INFO = 'change_info';
    case CHANGE_AVATAR = 'change_avatar';
    case DELETE_MESSAGES = 'delete_messages';
    case PIN_MESSAGES = 'pin_messages';
}