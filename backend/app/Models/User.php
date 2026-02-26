<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'verification_code',
        'verification_code_expires_at',
        'reset_password_code_expires_at',
        'reset_password_code_sent_at',
        'reset_password_attempts',
        'reset_password_locked_until',
        'login_attempts',
        'login_locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'reset_password_code_expires_at' => 'datetime',
            'reset_password_code_sent_at' => 'datetime',
            'reset_password_locked_until' => 'datetime',
            'login_locked_until' => 'datetime',
            'password' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class, 'user1_id')
            ->orWhere('user2_id', $this->id);
    }

    public function hasAccessToConversation($conversationId): bool
    {
        return $this->conversations()
            ->where('id', $conversationId)
            ->exists();
    }

    public function photos()
    {
        return $this->hasMany(UserPhoto::class);
    }

    public function mainPhoto()
    {
        return $this->hasOne(UserPhoto::class)->where('is_main', true);
    }

    public function getAvatarUrlAttribute($value)
    {
        $mainPhoto = $this->mainPhoto;
        if ($mainPhoto) {
            return $mainPhoto->url;
        }

        /**
         * @TODO добавить дефолтный аватар
         */
        return '';
    }

}
