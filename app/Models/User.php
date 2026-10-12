<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ROLES = [
        'student' => 'Student',
        'instructor' => 'Instructor',
        'adviser' => 'Adviser',
        'coordinator' => 'Project Coordinator',
    ];

    protected $fillable = [
        'first_name', 'last_name', 'email', 'role', 'approved', 'id_number', 'department',
        'program', 'contact', 'bio', 'avatar', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(mb_substr($this->first_name, 0, 1).mb_substr((string) $this->last_name, 0, 1));
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    /** Instructor, adviser or coordinator. */
    public function isFaculty(): bool
    {
        return in_array($this->role, ['instructor', 'adviser', 'coordinator'], true);
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_user', 'user_id', 'group_id')
            ->withPivot('role')->withTimestamps();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class)->latest();
    }

    public function unreadAlertCount(): int
    {
        return Alert::where('user_id', $this->id)->whereNull('read_at')->count();
    }
}
