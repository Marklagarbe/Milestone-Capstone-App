<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $fillable = ['user_id', 'kind', 'title', 'body', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Send an in-app notification to many users (the current user is skipped).
     *
     * @param  iterable<User|int|null>  $users
     */
    public static function notify(iterable $users, string $title, ?string $body = null, ?string $url = null, string $kind = 'info'): void
    {
        $sent = [];
        foreach ($users as $user) {
            if (! $user) {
                continue;
            }
            $id = $user instanceof User ? $user->id : (int) $user;
            if ($id === auth()->id() || isset($sent[$id])) {
                continue;
            }
            $sent[$id] = true;
            static::create(['user_id' => $id, 'kind' => $kind, 'title' => $title, 'body' => $body, 'url' => $url]);
        }
    }
}
