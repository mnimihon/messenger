<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    const TOKEN_LIFETIME = 30;
    const VERIFICATION_CODE_EXPIRES_AT = 10 * 60;
    const RESET_PASSWORD_EXPIRES_AT = 10;
    const LOGIN_LOCKED_UNTIL = 15;
    const RESET_PASSWORD_LOCKED_MINUTES = 15;

    const SEND_CODE_LATER_MINUTES = 1;
    const LOGIN_ATTEMPTS = 5;
    const RESET_PASSWORD_ATTEMPTS = 5;
    const RESET_PASSWORD_MINUTES = 1;
    const CAN_RESEND_AFTER = 60;

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
        'verification_code_sent_expires_at',
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
            'verification_code_sent_expires_at' => 'datetime',
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

    public function hasAccessToConversation($conversationID): bool
    {
        return $this->conversations()
            ->where('id', $conversationID)
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

    public function setVerifiedEmail(): bool
    {
        $this->email_verified_at = now();
        $this->verification_code = null;
        $this->verification_code_expires_at = null;
        $this->verification_code_sent_expires_at = null;
        return $this->save();
    }

    public function setVerificationCode(int $verificationCode): bool
    {
        $this->verification_code = $verificationCode;
        $this->verification_code_expires_at = now()->addSeconds(self::VERIFICATION_CODE_EXPIRES_AT);
        $this->verification_code_sent_expires_at = now()->addSeconds(self::CAN_RESEND_AFTER);
        return $this->save();
    }

    public function setResetPasswordCode(int $code): bool
    {
        $this->reset_password_code = $code;
        $this->reset_password_code_expires_at = now()->addMinutes(self::RESET_PASSWORD_EXPIRES_AT);
        $this->reset_password_code_sent_at = now();

        $this->reset_password_attempts = 0;
        $this->reset_password_locked_until = null;

        return $this->save();
    }

    public function lockLogin(): bool
    {
        $this->login_locked_until = now()->addMinutes(self::LOGIN_LOCKED_UNTIL);
        $this->login_attempts = 0;
        return $this->save();
    }

    public function lockResetPassword(): bool
    {
        $this->reset_password_locked_until = now()->addMinutes(self::RESET_PASSWORD_LOCKED_MINUTES);
        return $this->save();
    }

    public function updatePassword(string $password): bool
    {
        $this->password = Hash::make($password);
        return $this->save();
    }

    public function updateName(string $name): bool
    {
        $this->name = $name;
        return $this->save();
    }

    public function resetLoginAttempts(): bool
    {
        $this->login_attempts = 0;
        return $this->save();
    }

    public function unlockResetPassword() :bool
    {
        $this->reset_password_code = null;
        $this->reset_password_code_expires_at = null;
        return $this->save();
    }

    public function checkUnVerificationCode(int $code): bool
    {
        return $this->verification_code != $code ||
            $this->verification_code_expires_at < now();
    }

    public function isNotAllowResendCode(): bool
    {
        return $this->verification_code_sent_expires_at &&
            $this->verification_code_sent_expires_at > now();
    }

    public function isTooManyFailedLoginAttempts(): bool
    {
        return $this->login_locked_until && $this->login_locked_until > now();
    }

    public function isTooManyFailedLoginAttemptsAccessBlocked(): bool
    {
        return $this->login_attempts >= self::LOGIN_ATTEMPTS;
    }

    public function isTooManyFailedResetAttemptsAccessBlocked(): bool
    {
        return $this->reset_password_attempts >= self::RESET_PASSWORD_ATTEMPTS;
    }

    public function isCodeAlreadyBeenSent(): bool
    {
        return $this->reset_password_code_sent_at &&
            $this->reset_password_code_sent_at->diffInMinutes(now()) < self::RESET_PASSWORD_MINUTES;
    }

    public function isTooManyFailedResetAttempts(): bool
    {
        return $this->reset_password_locked_until && $this->reset_password_locked_until > now();
    }

    public function isCodeExpired(): bool
    {
        return $this->reset_password_code_expires_at < now();
    }

    public function isCodeEqual(int $code): bool
    {
        return $this->reset_password_code == $code;
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
