<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'project_groups';

    protected $fillable = ['name', 'description', 'instructor_id', 'adviser_id', 'created_by'];

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_user', 'group_id', 'user_id')
            ->withPivot('role')->withTimestamps();
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function adviser()
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'group_id');
    }

    public function leader(): ?User
    {
        return $this->members->first(fn ($u) => $u->pivot->role === 'leader');
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->exists();
    }

    public function isLeader(User $user): bool
    {
        return $this->members()->where('users.id', $user->id)->wherePivot('role', 'leader')->exists();
    }

    public function isFacultyFor(User $user): bool
    {
        return $user->isCoordinator()
            || $user->id === $this->instructor_id
            || $user->id === $this->adviser_id;
    }

    public function canManage(User $user): bool
    {
        return $this->isFacultyFor($user) || $this->isLeader($user);
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isCoordinator()) {
            return $query;
        }
        if ($user->isStudent()) {
            return $query->whereHas('members', fn ($m) => $m->where('users.id', $user->id));
        }

        return $query->where(fn ($q) => $q->where('instructor_id', $user->id)->orWhere('adviser_id', $user->id));
    }
}
