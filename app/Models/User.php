<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'national_code',
        'phone_number',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'avatar_path',
        'notification_preferences',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'notification_preferences' => 'array',
    ];

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? route('profile.avatar.show', [$this, 'v' => sha1($this->avatar_path)], absolute: false) : null;
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    public function isGlobalAdmin(): bool
    {
        return $this->userRoles()->where('role', 'admin')->where('active', true)->exists();
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'manager_id');
    }

    public function monthlyCharges(): HasMany
    {
        return $this->hasMany(MonthlyCharge::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class, 'role_id');
    }

    public function membershipRequests(): HasMany
    {
        return $this->hasMany(MembershipRequest::class);
    }

    public function seenMessages(): HasMany
    {
        return $this->hasMany(SeenMessage::class);
    }
}
