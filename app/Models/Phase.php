<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Phase extends Model
{
    protected $fillable = ['project_id', 'name', 'position'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function milestones()
    {
        return $this->hasMany(Milestone::class)->orderBy('position');
    }

    public function getProgressAttribute(): int
    {
        $total = $this->milestones->count();

        return $total ? (int) round($this->milestones->where('status', 'approved')->count() / $total * 100) : 0;
    }
}
