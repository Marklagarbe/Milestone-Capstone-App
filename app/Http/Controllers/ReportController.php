<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $rows = $this->rows($request);

        return view('reports.index', [
            'rows' => $rows,
            'summary' => [
                'projects' => $rows->count(),
                'completed' => $rows->where('project.status', 'completed')->count(),
                'avg' => $rows->isEmpty() ? 0 : (int) round($rows->avg('progress')),
                'overdue' => $rows->sum('overdue'),
            ],
        ]);
    }

    public function show(Project $project)
    {
        $this->access($project);
        $project->load(['group.members', 'group.instructor', 'group.adviser', 'phases.milestones', 'tasks.assignee', 'documents.latestVersion', 'feedback.author']);

        $members = $project->group->members->map(function ($m) use ($project) {
            $mine = $project->tasks->where('assigned_to', $m->id);

            return (object) [
                'user' => $m,
                'assigned' => $mine->count(),
                'done' => $mine->where('status', 'completed')->count(),
                'avg' => $mine->isEmpty() ? 0 : (int) round($mine->avg('progress')),
                'files' => $project->documents->where('uploaded_by', $m->id)->count(),
            ];
        });

        return view('reports.show', compact('project', 'members'));
    }

    public function export(Request $request)
    {
        $rows = $this->rows($request);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Project', 'Type', 'Group', 'Status', 'Progress %', 'Milestones approved', 'Milestones total', 'Tasks completed', 'Tasks total', 'Overdue items', 'Files', 'Target date'], ',', '"', '\\');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->project->title, ucfirst($r->project->type), $r->project->group->name,
                    str_replace('_', ' ', $r->project->status), $r->progress,
                    $r->approved, $r->milestones, $r->tasksDone, $r->tasks, $r->overdue, $r->files,
                    optional($r->project->target_date)->format('Y-m-d'),
                ], ',', '"', '\\');
            }
            fclose($out);
        }, 'thesiscap-progress-report-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function rows(Request $request)
    {
        return Project::visibleTo($request->user())
            ->with(['group', 'milestones', 'tasks', 'documents'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('title')->get()
            ->map(fn ($p) => (object) [
                'project' => $p,
                'progress' => $p->progress,
                'milestones' => $p->milestones->count(),
                'approved' => $p->milestones->where('status', 'approved')->count(),
                'tasks' => $p->tasks->count(),
                'tasksDone' => $p->tasks->where('status', 'completed')->count(),
                'overdue' => $p->milestones->filter(fn ($m) => $m->isOverdue())->count()
                    + $p->tasks->filter(fn ($t) => $t->isOverdue())->count(),
                'files' => $p->documents->count(),
            ]);
    }
}
