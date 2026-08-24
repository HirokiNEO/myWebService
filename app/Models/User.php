<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'share_token',
        'is_calendar_shared',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_calendar_shared' => 'boolean',
    ];

    /**
     * カレンダー共有URLアクセサ (常に有効なURLを安全に生成)
     */
    public function getShareUrlAttribute(): string
    {
        if (empty($this->share_token)) {
            $this->share_token = Str::random(32);
            $this->saveQuietly();
        }

        return route('calendar.share', ['token' => $this->share_token]);
    }

    /**
     * ユーザーが所有するタスク・スケジュール一覧
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
