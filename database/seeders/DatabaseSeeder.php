<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Alert;
use App\Models\Document;
use App\Models\Feedback;
use App\Models\Group;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /** Demo data. Every account uses the password "password". */
    public function run(): void
    {
        Model::unguard(); // lets the demo data set back-dated created_at values

        $pw = 'password';

        $coordinator = User::create(['first_name' => 'Elena', 'last_name' => 'Cruz', 'email' => 'coordinator@thesiscap.test', 'role' => 'coordinator', 'department' => 'College of Computer Studies', 'password' => $pw]);
        $instructor = User::create(['first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'instructor@thesiscap.test', 'role' => 'instructor', 'department' => 'College of Computer Studies', 'password' => $pw]);
        $adviser = User::create(['first_name' => 'Ramon', 'last_name' => 'Reyes', 'email' => 'adviser@thesiscap.test', 'role' => 'adviser', 'department' => 'College of Computer Studies', 'password' => $pw]);

        $juan = User::create(['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'student@thesiscap.test', 'role' => 'student', 'id_number' => '2022-00123', 'program' => 'BS Information Technology', 'password' => $pw]);
        $ana = User::create(['first_name' => 'Ana', 'last_name' => 'Lopez', 'email' => 'ana@thesiscap.test', 'role' => 'student', 'id_number' => '2022-00124', 'program' => 'BS Information Technology', 'password' => $pw]);
        $mark = User::create(['first_name' => 'Mark', 'last_name' => 'Villanueva', 'email' => 'mark@thesiscap.test', 'role' => 'student', 'id_number' => '2022-00125', 'program' => 'BS Information Technology', 'password' => $pw]);
        $carla = User::create(['first_name' => 'Carla', 'last_name' => 'Mendoza', 'email' => 'carla@thesiscap.test', 'role' => 'student', 'id_number' => '2022-00131', 'program' => 'BS Computer Science', 'password' => $pw]);
        $paolo = User::create(['first_name' => 'Paolo', 'last_name' => 'Garcia', 'email' => 'paolo@thesiscap.test', 'role' => 'student', 'id_number' => '2022-00132', 'program' => 'BS Computer Science', 'password' => $pw]);

        // ---- Group 1 / Project 1 -------------------------------------------------
        $g1 = Group::create(['name' => 'Group 1 - Inventory Team', 'description' => 'BSIT capstone group', 'instructor_id' => $instructor->id, 'adviser_id' => $adviser->id, 'created_by' => $juan->id]);
        $g1->members()->attach([$juan->id => ['role' => 'leader'], $ana->id => ['role' => 'member'], $mark->id => ['role' => 'member']]);

        $p1 = Project::create([
            'group_id' => $g1->id, 'title' => 'Web-Based Inventory System', 'type' => 'capstone',
            'description' => 'An inventory and stock monitoring system for small retail stores.',
            'start_date' => now()->subMonths(3)->startOfDay(), 'target_date' => now()->addMonths(5)->startOfDay(),
            'created_by' => $juan->id,
        ]);
        $p1->seedTemplate();
        $this->setMilestones($p1, $instructor, [0, 1, 2, 3], 4, 5);

        $m = $p1->milestones()->get();
        Feedback::create(['project_id' => $p1->id, 'milestone_id' => $m[1]->id, 'user_id' => $instructor->id, 'body' => 'Chapter 1 is clear. Please tighten the scope and limitations.', 'decision' => 'revision', 'created_at' => now()->subDays(40)]);
        Feedback::create(['project_id' => $p1->id, 'milestone_id' => $m[1]->id, 'user_id' => $instructor->id, 'body' => 'Revisions look good. Approved.', 'decision' => 'approved', 'created_at' => now()->subDays(33)]);
        Feedback::create(['project_id' => $p1->id, 'milestone_id' => $m[3]->id, 'user_id' => $adviser->id, 'body' => 'Good literature coverage. Add two more local studies before the defense.', 'decision' => 'comment', 'created_at' => now()->subDays(6)]);

        $this->task($p1, $m[4], 'Revise Chapter 3 methodology', $juan, $ana, 'high', 100, now()->subDays(5), 'Updated the research design and sampling.');
        $this->task($p1, $m[4], 'Draft data flow diagrams', $juan, $mark, 'medium', 60, now()->addDays(4), 'Level 0 and Level 1 are done.');
        $this->task($p1, $m[5], 'Design the database schema', $juan, $juan, 'high', 30, now()->addDays(2), 'ERD draft is ready for review.');
        $this->task($p1, $m[5], 'Prepare defense slides', $juan, $ana, 'low', 0, now()->addDays(12), null);
        $this->task($p1, $m[4], 'Collect user requirements survey', $juan, $mark, 'medium', 100, now()->subDays(12), 'Collected 32 responses.');
        $this->task($p1, null, 'Format manuscript to the school template', $juan, $juan, 'low', 0, now()->subDays(1), null);

        $this->document($p1, $m[0], 'Title Proposal', 'proposal', $juan, 'approved', ['title-proposal-v1.txt' => 'Title proposal for the Web-Based Inventory System.']);
        $this->document($p1, $m[1], 'Chapter 1: Introduction', 'chapter', $ana, 'approved', ['chapter-1-v1.txt' => 'Draft 1 of Chapter 1.', 'chapter-1-v2.txt' => 'Draft 2 of Chapter 1 with revised scope and limitations.']);
        $this->document($p1, $m[4], 'Chapter 3: Methodology', 'chapter', $juan, 'submitted', ['chapter-3-v1.txt' => 'Chapter 3 draft.']);
        $this->document($p1, $m[2], 'Proposal Defense Slides', 'presentation', $mark, 'draft', ['proposal-slides-v1.txt' => 'Slide outline for the proposal defense.']);

        foreach ([
            [$juan, 'created the project "Web-Based Inventory System"', 'project', 90],
            [$instructor, 'approved "Chapter 1: Introduction"', 'milestone', 33],
            [$ana, 'completed the task "Revise Chapter 3 methodology"', 'task', 5],
            [$mark, 'updated "Draft data flow diagrams" to 60%', 'task', 2],
            [$juan, 'submitted "Chapter 3: Methodology" for review', 'milestone', 1],
        ] as [$who, $text, $type, $daysAgo]) {
            Activity::create(['project_id' => $p1->id, 'user_id' => $who->id, 'type' => $type, 'description' => $text, 'created_at' => now()->subDays($daysAgo)]);
        }

        // ---- Group 2 / Project 2 -------------------------------------------------
        $g2 = Group::create(['name' => 'Group 2 - Library Team', 'description' => 'BSCS thesis group', 'instructor_id' => $instructor->id, 'adviser_id' => $adviser->id, 'created_by' => $carla->id]);
        $g2->members()->attach([$carla->id => ['role' => 'leader'], $paolo->id => ['role' => 'member']]);

        $p2 = Project::create([
            'group_id' => $g2->id, 'title' => 'Library Management System', 'type' => 'thesis',
            'description' => 'A catalog and borrowing system with overdue prediction.',
            'start_date' => now()->subMonths(1)->startOfDay(), 'target_date' => now()->addMonths(7)->startOfDay(),
            'created_by' => $carla->id,
        ]);
        $p2->seedTemplate();
        $this->setMilestones($p2, $instructor, [0, 1], 2, 3);
        $m2 = $p2->milestones()->get();

        $this->task($p2, $m2[2], 'Interview the school librarian', $carla, $paolo, 'medium', 80, now()->addDays(3), 'Interview done, transcribing notes.');
        $this->task($p2, $m2[2], 'Draft Chapter 1 background', $carla, $carla, 'high', 40, now()->addDays(6), null);
        $this->task($p2, $m2[3], 'List related studies', $carla, $paolo, 'low', 0, now()->addDays(20), null);
        $this->document($p2, $m2[1], 'Chapter 1: Introduction', 'chapter', $carla, 'draft', ['library-chapter-1-v1.txt' => 'Chapter 1 draft for the Library Management System.']);
        Activity::create(['project_id' => $p2->id, 'user_id' => $carla->id, 'type' => 'project', 'description' => 'created the project "Library Management System"', 'created_at' => now()->subDays(30)]);
        Activity::create(['project_id' => $p2->id, 'user_id' => $paolo->id, 'type' => 'task', 'description' => 'updated "Interview the school librarian" to 80%', 'created_at' => now()->subDays(1)]);

        // ---- Notifications --------------------------------------------------------
        Alert::create(['user_id' => $juan->id, 'kind' => 'feedback', 'title' => 'New comment on Web-Based Inventory System', 'body' => 'Good literature coverage. Add two more local studies before the defense.', 'url' => route('projects.show', $p1)]);
        Alert::create(['user_id' => $juan->id, 'kind' => 'reminder', 'title' => 'Task due soon', 'body' => 'Design the database schema (due '.now()->addDays(2)->format('M d').')', 'url' => route('tasks.index')]);
        Alert::create(['user_id' => $instructor->id, 'kind' => 'milestone', 'title' => 'Milestone ready for review', 'body' => 'Web-Based Inventory System: Chapter 3: Methodology', 'url' => route('projects.show', $p1)]);

        Model::reguard();
    }

    /** Approve the given milestone indexes, submit one and start one. */
    private function setMilestones(Project $project, User $approver, array $approved, int $submitted, int $started): void
    {
        foreach ($project->milestones()->get() as $i => $milestone) {
            if (in_array($i, $approved, true)) {
                $milestone->update(['status' => 'approved', 'approved_by' => $approver->id, 'approved_at' => now()->subDays(max(1, 40 - $i * 8)), 'submitted_at' => now()->subDays(max(2, 42 - $i * 8))]);
            } elseif ($i === $submitted) {
                $milestone->update(['status' => 'submitted', 'submitted_at' => now()->subDay()]);
            } elseif ($i === $started) {
                $milestone->update(['status' => 'in_progress']);
            }
        }
        $project->syncStatus();
    }

    private function task(Project $project, $milestone, string $title, User $creator, User $assignee, string $priority, int $progress, $due, ?string $note): void
    {
        $status = $progress >= 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'pending');
        $task = Task::create([
            'project_id' => $project->id, 'milestone_id' => $milestone?->id, 'title' => $title,
            'assigned_to' => $assignee->id, 'created_by' => $creator->id, 'due_date' => $due,
            'priority' => $priority, 'status' => $status, 'progress' => $progress,
            'completed_at' => $status === 'completed' ? now()->subDays(3) : null,
        ]);
        TaskUpdate::create(['task_id' => $task->id, 'user_id' => $creator->id, 'type' => 'created', 'message' => 'Task created', 'progress' => 0, 'to_status' => 'pending', 'created_at' => now()->subDays(14)]);
        if ($progress > 0) {
            TaskUpdate::create(['task_id' => $task->id, 'user_id' => $assignee->id, 'type' => 'status', 'message' => $note, 'progress' => $progress, 'from_status' => 'pending', 'to_status' => $status, 'created_at' => now()->subDays(3)]);
        }
    }

    /** @param array<string,string> $files filename => contents, one entry per version */
    private function document(Project $project, $milestone, string $title, string $category, User $uploader, string $status, array $files): void
    {
        $doc = Document::create([
            'project_id' => $project->id, 'milestone_id' => $milestone?->id, 'title' => $title,
            'category' => $category, 'status' => $status, 'uploaded_by' => $uploader->id,
        ]);

        $v = 0;
        foreach ($files as $name => $contents) {
            $v++;
            $path = 'documents/'.$project->id.'/'.uniqid().'-'.$name;
            Storage::disk('local')->put($path, $contents);
            $doc->versions()->create([
                'version' => $v, 'file_path' => $path, 'original_name' => $name, 'mime' => 'text/plain',
                'size' => strlen($contents), 'notes' => $v === 1 ? 'First upload' : 'Revised after feedback',
                'uploaded_by' => $uploader->id,
            ]);
        }
    }
}
