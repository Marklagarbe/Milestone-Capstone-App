<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['project_id', 'user_id', 'type', 'description'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(?Project $project, string $description, string $type = 'general'): self
    {
        return static::create([
            'project_id' => $project?->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
        ]);
    }
}
