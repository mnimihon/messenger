<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPhoto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'path',
        'is_approved',
        'is_main',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_main' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
