<?php

namespace App\Http\Controllers;

use App\Models\Project;

abstract class Controller
{
    /** Abort with 403 unless the signed-in user can see this project. */
    protected function access(Project $project): Project
    {
        abort_unless($project->isAccessibleBy(auth()->user()), 403, 'You do not have access to this project.');

        return $project;
    }

    /** Abort with 403 unless the signed-in user is faculty or group leader of the project. */
    protected function manage(Project $project): Project
    {
        abort_unless($project->canManage(auth()->user()), 403, 'Only the group leader or faculty can do this.');

        return $project;
    }

    /** Abort with 403 unless the signed-in user is instructor/adviser/coordinator of the project. */
    protected function faculty(Project $project): Project
    {
        abort_unless($project->isFacultyFor(auth()->user()), 403, 'Only the instructor or adviser can do this.');

        return $project;
    }
}
