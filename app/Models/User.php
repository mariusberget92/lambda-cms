<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'last_seen_at',
        'banned_at',
        'banned_until',
        'ban_reason',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Appended virtual attributes.
     *
     * @var list<string>
     */
    protected $appends = ['avatar_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'last_seen_at'            => 'datetime',
            'banned_at'               => 'datetime',
            'banned_until'            => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /**
     * Full public URL to the user's avatar, or null if none is set.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar
            ? Storage::disk('public')->url($this->avatar)
            : null;
    }

    /**
     * Whether the user has been seen within the last 5 minutes.
     * Returns false if they have never made a request (last_seen_at is null).
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->diffInMinutes(now()) < 5;
    }

    /**
     * Whether the user is currently banned (and ban has not expired).
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null
            && ($this->banned_until === null || $this->banned_until->isFuture());
    }

    /**
     * If the timed ban has expired, clear the ban columns.
     * Returns true if a ban was lifted, false otherwise.
     */
    public function liftExpiredBan(): bool
    {
        if (
            $this->banned_at !== null
            && $this->banned_until !== null
            && $this->banned_until->isPast()
        ) {
            $this->update(['banned_at' => null, 'banned_until' => null, 'ban_reason' => null]);
            return true;
        }

        return false;
    }
}
