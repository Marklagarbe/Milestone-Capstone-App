<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'group_id', 'title', 'description', 'type', 'status',
        'start_date', 'target_date', 'completed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function phases()
    {
        return $this->hasMany(Phase::class)->orderBy('position');
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class)->orderBy('position');
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

    public function activities()
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isCoordinator()) {
            return $query;
        }
        if ($user->isStudent()) {
            return $query->whereHas('group.members', fn ($m) => $m->where('users.id', $user->id));
        }

        return $query->whereHas('group', fn ($g) => $g->where('instructor_id', $user->id)->orWhere('adviser_id', $user->id));
    }

    public function isAccessibleBy(User $user): bool
    {
        return static::visibleTo($user)->whereKey($this->id)->exists();
    }

    public function isFacultyFor(User $user): bool
    {
        return $this->group->isFacultyFor($user);
    }

    public function canManage(User $user): bool
    {
        return $this->group->canManage($user);
    }

    /** Group members + instructor + adviser. */
    public function participants()
    {
        $group = $this->group->loadMissing('members', 'instructor', 'adviser');

        return $group->members->concat(array_filter([$group->instructor, $group->adviser]));
    }

    public function getProgressAttribute(): int
    {
        $total = $this->milestones()->count();
        if ($total > 0) {
            return (int) round($this->milestones()->where('status', 'approved')->count() / $total * 100);
        }

        return (int) round($this->tasks()->avg('progress') ?? 0);
    }

    public function getDaysLeftAttribute(): ?int
    {
        if (! $this->target_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->target_date, false);
    }

    /** Keep the project status in line with its milestones. */
    public function syncStatus(): void
    {
        $ms = $this->milestones()->get();
        if ($ms->isEmpty()) {
            return;
        }

        if ($ms->every(fn ($m) => $m->status === 'approved')) {
            $status = 'completed';
        } elseif ($ms->contains(fn ($m) => $m->status === 'submitted')) {
            $status = 'under_review';
        } elseif ($ms->contains(fn ($m) => in_array($m->status, ['in_progress', 'approved', 'revision'], true))) {
            $status = 'in_progress';
        } else {
            $status = 'proposal';
        }

        $this->update([
            'status' => $status,
            'completed_at' => $status === 'completed' ? ($this->completed_at ?? now()) : null,
        ]);
    }

    /** Create the default thesis/capstone phases and milestones. */
    public function seedTemplate(): void
    {
        $capstone = $this->type === 'capstone';

        $template = [
            'Proposal Stage' => ['Topic and Title Proposal', 'Chapter 1: Introduction', 'Proposal Defense'],
            'Chapters 2 and 3' => ['Chapter 2: Review of Related Literature', 'Chapter 3: Methodology'],
            'Development and Data Gathering' => [
                $capstone ? 'System Design and Prototype' : 'Data Gathering',
                $capstone ? 'System Development and Testing' : 'Data Analysis',
            ],
            'Chapters 4 and 5' => ['Chapter 4: Results and Discussion', 'Chapter 5: Summary, Conclusions and Recommendations'],
            'Final Defense and Submission' => ['Final Defense', 'Incorporate Panel Revisions', 'Final Manuscript Submission'],
        ];

        $count = array_sum(array_map('count', $template));
        $start = $this->start_date ?? now()->startOfDay();
        $end = $this->target_date;
        $step = $end ? max(1, (int) floor(abs($start->diffInDays($end)) / $count)) : null;

        $i = 0;
        foreach (array_keys($template) as $pos => $phaseName) {
            $phase = $this->phases()->create(['name' => $phaseName, 'position' => $pos + 1]);
            foreach ($template[$phaseName] as $title) {
                $i++;
                $due = null;
                if ($step) {
                    $due = $start->copy()->addDays($step * $i);
                    if ($end && $due->gt($end)) {
                        $due = $end->copy();
                    }
                }
                $this->milestones()->create([
                    'phase_id' => $phase->id,
                    'title' => $title,
                    'due_date' => $due,
                    'position' => $i,
                ]);
            }
        }
    }
}
