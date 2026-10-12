<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = [
        'project_id', 'phase_id', 'title', 'description', 'due_date', 'status',
        'submitted_at', 'approved_at', 'approved_by', 'position',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function phase()
    {
        return $this->belongsTo(Phase::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class)->latest();
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'approved' && $this->due_date && $this->due_date->lt(today());
    }
}
