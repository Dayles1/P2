<?php

namespace App\Domain\Chat\Models;

use App\Domain\Attachment\Models\Attachment;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'parent_message_id',
        'type',
        'body',
        'meta',
        'edited_at',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_message_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }
}