<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A reaction pinned to a second of the title ("😱 at 1:04:12"), or a short text line. */
class WatchPartyReaction extends Model
{
    public const UPDATED_AT = null;

    /** key => [emoji, word used in summaries]. */
    public const KINDS = [
        'scream' => ['😱', 'jumped'],
        'laugh' => ['😂', 'laughed'],
        'cry' => ['😭', 'cried'],
        'fire' => ['🔥', 'loved it'],
        'mind' => ['🤯', 'mind blown'],
        'love' => ['❤️', 'awww'],
    ];

    /** How a title page names the peak minute of each reaction. */
    public const PEAKS = [
        'scream' => 'Jumpiest moment',
        'laugh' => 'Biggest laugh',
        'cry' => 'Saddest moment',
        'fire' => 'Best scene',
        'mind' => 'Mind-blowing moment',
        'love' => 'Sweetest moment',
    ];

    protected $fillable = ['watch_party_id', 'watch_party_member_id', 'kind', 'body', 'position_s'];

    protected function casts(): array
    {
        return ['position_s' => 'integer', 'created_at' => 'datetime'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(WatchPartyMember::class, 'watch_party_member_id');
    }

    public function emoji(): string
    {
        return self::KINDS[$this->kind][0] ?? '💬';
    }
}
