<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Document;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $projects = Project::visibleTo($user)->with('group')->latest()->get();
        $ids = $projects->pluck('id');

        $milestones = Milestone::whereIn('project_id', $ids)->get();
        $tasks = Task::with('project')->whereIn('project_id', $ids)->get();
        $overall = $projects->isEmpty() ? 0 : (int) round($projects->avg(fn ($p) => $p->progress));

        // Donut: milestone status breakdown across visible projects.
        $segments = [
            ['Approved', $milestones->where('status', 'approved')->count(), '#2f7fe0'],
            ['Submitted', $milestones->where('status', 'submitted')->count(), '#f7c548'],
            ['In Progress', $milestones->whereIn('status', ['in_progress', 'revision'])->count(), '#f2845c'],
            ['Pending', $milestones->where('status', 'pending')->count(), '#3cc4a0'],
        ];

        $activities = Activity::whereIn('project_id', $ids)->with(['user', 'project'])->latest()->limit(6)->get();
        $feedback = Feedback::whereIn('project_id', $ids)->with(['author', 'project'])->latest()->limit(4)->get();
        $upcoming = Milestone::whereIn('project_id', $ids)
            ->where('status', '!=', 'approved')->whereNotNull('due_date')
            ->with('project')->orderBy('due_date')->limit(5)->get();

        // One row per project: progress, overdue items, next milestone.
        $rows = $projects->map(function ($p) use ($milestones, $tasks) {
            $ms = $milestones->where('project_id', $p->id);
            $ts = $tasks->where('project_id', $p->id);

            return (object) [
                'project' => $p,
                'progress' => $p->progress,
                'overdue' => $ms->filter(fn ($m) => $m->isOverdue())->count() + $ts->filter(fn ($t) => $t->isOverdue())->count(),
                'submitted' => $ms->where('status', 'submitted')->count(),
                'next' => $ms->where('status', '!=', 'approved')->sortBy('position')->first(),
            ];
        });

        $data = compact('projects', 'overall', 'segments', 'activities', 'feedback', 'upcoming', 'rows')
            + ['approvedCount' => $milestones->where('status', 'approved')->count(), 'milestoneCount' => $milestones->count()];

        if ($user->isStudent()) {
            $mine = $tasks->where('assigned_to', $user->id);
            $primary = $projects->first(fn ($p) => $p->status !== 'completed') ?? $projects->first();

            return view('dashboard.student', $data + [
                'pendingTasks' => $mine->where('status', 'pending')->count(),
                'activeTasks' => $mine->where('status', 'in_progress')->count(),
                'doneTasks' => $mine->where('status', 'completed')->count(),
                'myTasks' => $mine->where('status', '!=', 'completed')
                    ->sortBy(fn ($t) => $t->due_date?->timestamp ?? PHP_INT_MAX)->take(4),
                'primary' => $primary,
                'phaseSteps' => $primary
                    ? $primary->phases()->with('milestones')->get()->map(fn ($ph) => (object) [
                        'name' => $ph->name,
                        'total' => $ph->milestones->count(),
                        'done' => $ph->milestones->where('status', 'approved')->count(),
                    ])
                    : collect(),
                'nextStep' => $primary ? $primary->milestones()->where('status', '!=', 'approved')->orderBy('position')->first() : null,
            ]);
        }

        $overdue = $rows->sum('overdue');
        $toReview = Milestone::whereIn('project_id', $ids)->where('status', 'submitted')->with('project')->orderBy('submitted_at')->limit(6)->get();
        $docsToReview = Document::whereIn('project_id', $ids)->where('status', 'submitted')->with(['project', 'uploader'])->latest()->limit(5)->get();
        $pendingReviews = $milestones->where('status', 'submitted')->count() + Document::whereIn('project_id', $ids)->where('status', 'submitted')->count();

        if ($user->isCoordinator()) {
            $groups = Group::with('projects')->get();

            $faculty = User::whereIn('role', ['instructor', 'adviser'])->where('approved', true)->orderBy('first_name')->get()
                ->map(function ($u) use ($groups, $milestones) {
                    $mine = $groups->filter(fn ($g) => $g->instructor_id === $u->id || $g->adviser_id === $u->id);
                    $pids = $mine->flatMap->projects->pluck('id')->all();

                    return (object) [
                        'user' => $u,
                        'groups' => $mine->count(),
                        'projects' => count($pids),
                        'pending' => $milestones->whereIn('project_id', $pids)->where('status', 'submitted')->count(),
                    ];
                });

            return view('dashboard.coordinator', $data + [
                'groupCount' => $groups->count(),
                'studentCount' => User::where('role', 'student')->count(),
                'facultyCount' => $faculty->count(),
                'pendingUsers' => User::where('approved', false)->latest()->limit(5)->get(),
                'pendingUserCount' => User::where('approved', false)->count(),
                'statusCounts' => $projects->groupBy('status')->map->count(),
                'faculty' => $faculty,
                'atRisk' => $rows->where('overdue', '>', 0)->sortByDesc('overdue')->take(5),
                'pendingReviews' => $pendingReviews,
                'overdue' => $overdue,
            ]);
        }

        return view('dashboard.faculty', $data + [
            'groupCount' => Group::visibleTo($user)->count(),
            'pendingReviews' => $pendingReviews,
            'overdue' => $overdue,
            'toReview' => $toReview,
            'docsToReview' => $docsToReview,
        ]);
    }
}
