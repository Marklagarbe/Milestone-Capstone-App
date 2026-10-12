<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    public const CATEGORIES = [
        'proposal' => 'Proposal',
        'chapter' => 'Chapters',
        'manuscript' => 'Manuscript',
        'presentation' => 'Presentations',
        'other' => 'Other Files',
    ];

    protected $fillable = [
        'project_id', 'milestone_id', 'title', 'category', 'description', 'status', 'uploaded_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version');
    }

    public function latestVersion()
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany('version');
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class)->latest();
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'document_favorites')->withTimestamps();
    }

    public function isFavoriteFor(User $user): bool
    {
        return $this->favoritedBy()->where('users.id', $user->id)->exists();
    }
}
