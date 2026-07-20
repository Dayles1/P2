<?php

namespace App\Domain\Chat\Models;

use App\Domain\Chat\Enums\ConversationLeftReason;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationUser extends Pivot
{
    protected $table = 'conversation_users';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'joined_by',
        'left_at',
        'muted_until',
        'unread_count',
        'last_read_message_id',
        'last_read_at',
        'is_pinned',
        'is_hidden',
        'notifications_enabled',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'left_reason' => ConversationLeftReason::class,
            'muted_until' => 'datetime',
            'last_read_at' => 'datetime',
            'is_pinned' => 'boolean',
            'is_hidden' => 'boolean',
            'notifications_enabled' => 'boolean',
            
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(ConversationUserPermission::class);
    }
}