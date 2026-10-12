<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    protected $fillable = ['project_id', 'milestone_id', 'document_id', 'user_id', 'body', 'decision'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class)->withTrashed();
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
