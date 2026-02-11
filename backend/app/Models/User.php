<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Les attributs autorisés en assignation massive.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'skill_level',
    ];

    /**
     * Les attributs masqués en sérialisation.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Relations **/
    public function characters(): HasMany
    {
        return $this->hasMany(UserCharacter::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }
}
