<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'user_id',
        'content',
    ];

    protected $appends = ['vote_balance'];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ForumPostVote::class, 'post_id');
    }

    protected function voteBalance(): Attribute
    {
        return Attribute::get(function () {
            $up = $this->votes->where('vote', 'up')->count();
            $down = $this->votes->where('vote', 'down')->count();

            return $up - $down;
        });
    }
}
