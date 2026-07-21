<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon_path',
    ];

    public function userCharacters(): HasMany
    {
        return $this->hasMany(UserCharacter::class);
    }
}
