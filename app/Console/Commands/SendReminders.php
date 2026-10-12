<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\Milestone;
use App\Models\Task;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'thesiscap:send-reminders {--days=3 : Remind about items due within this many days}';

    protected $description = 'Send in-app reminders for tasks and milestones that are due soon or overdue';

    public function handle(): int
    {
        $limit = today()->addDays((int) $this->option('days'));
        $sent = 0;

        Task::where('status', '!=', 'completed')->whereNotNull('assigned_to')
            ->whereNotNull('due_date')->whereDate('due_date', '<=', $limit)->get()
            ->each(function (Task $task) use (&$sent) {
                $title = $task->due_date->lt(today()) ? 'Task overdue' : 'Task due soon';
                $sent += $this->remind($task->assigned_to, $title, $task->title.' (due '.$task->due_date->format('M d').')', route('tasks.show', $task));
            });

        Milestone::with('project.group.members', 'project.group.instructor', 'project.group.adviser')
            ->where('status', '!=', 'approved')->whereNotNull('due_date')->whereDate('due_date', '<=', $limit)->get()
            ->each(function (Milestone $m) use (&$sent) {
                $title = $m->due_date->lt(today()) ? 'Milestone overdue' : 'Milestone due soon';
                $body = $m->project->title.': '.$m->title.' (due '.$m->due_date->format('M d').')';
                foreach ($m->project->participants()->unique('id') as $user) {
                    $sent += $this->remind($user->id, $title, $body, route('projects.show', $m->project));
                }
            });

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }

    private function remind(int $userId, string $title, string $body, string $url): int
    {
        $exists = Alert::where('user_id', $userId)->where('kind', 'reminder')
            ->where('title', $title)->where('body', $body)->whereDate('created_at', today())->exists();

        if ($exists) {
            return 0;
        }

        Alert::create(['user_id' => $userId, 'kind' => 'reminder', 'title' => $title, 'body' => $body, 'url' => $url]);

        return 1;
    }
}
