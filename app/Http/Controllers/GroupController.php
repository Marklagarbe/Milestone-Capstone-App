<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Alert;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $groups = Group::visibleTo($user)
            ->with(['members', 'instructor', 'adviser', 'projects'])
            ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')->get();

        return view('groups.index', [
            'groups' => $groups,
            'activeProjects' => $groups->flatMap->projects->where('status', '!=', 'completed')->count(),
            'memberCount' => $groups->flatMap->members->unique('id')->count(),
        ]);
    }

    public function create()
    {
        return view('groups.create', $this->formData());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'instructor_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'instructor')->where('approved', true)],
            'adviser_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'adviser')->where('approved', true)],
        ]);

        if ($user->role === 'instructor' && empty($data['instructor_id'])) {
            $data['instructor_id'] = $user->id;
        }
        if ($user->role === 'adviser' && empty($data['adviser_id'])) {
            $data['adviser_id'] = $user->id;
        }

        $group = Group::create($data + ['created_by' => $user->id]);

        if ($user->isStudent()) {
            $group->members()->attach($user->id, ['role' => 'leader']);
        }

        Alert::notify([$group->instructor_id, $group->adviser_id], 'You were added to a group',
            "{$group->name} has been created.", route('groups.show', $group), 'info');

        return redirect()->route('groups.show', $group)->with('success', 'Group created.');
    }

    public function show(Group $group)
    {
        $this->authorizeGroup($group);
        $group->load(['members', 'instructor', 'adviser', 'projects']);

        return view('groups.show', ['group' => $group, 'canManage' => $group->canManage(auth()->user())] + $this->formData());
    }

    public function update(Request $request, Group $group)
    {
        abort_unless($group->canManage($request->user()), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'instructor_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'instructor')->where('approved', true)],
            'adviser_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'adviser')->where('approved', true)],
        ]);
        $group->update($data);

        return back()->with('success', 'Group updated.');
    }

    public function destroy(Group $group)
    {
        abort_unless($group->canManage(auth()->user()), 403);
        $group->delete();

        return redirect()->route('groups.index')->with('success', 'Group deleted.');
    }

    public function addMember(Request $request, Group $group)
    {
        abort_unless($group->canManage($request->user()), 403);
        $data = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'in:leader,member'],
        ]);

        $student = User::where('email', $data['email'])->where('role', 'student')->first();
        if (! $student) {
            return back()->withErrors(['email' => 'No student account was found with that email.'])->withInput();
        }
        if ($group->isMember($student)) {
            return back()->withErrors(['email' => "{$student->name} is already in this group."])->withInput();
        }

        $group->members()->attach($student->id, ['role' => $data['role']]);
        Alert::notify([$student], 'You were added to a group', "You are now a {$data['role']} of {$group->name}.", route('groups.show', $group), 'info');

        return back()->with('success', "{$student->name} was added to the group.");
    }

    public function removeMember(Group $group, User $user)
    {
        abort_unless($group->canManage(auth()->user()), 403);
        $group->members()->detach($user->id);

        return back()->with('success', 'Member removed.');
    }

    private function authorizeGroup(Group $group): void
    {
        abort_unless(Group::visibleTo(auth()->user())->whereKey($group->id)->exists(), 403);
    }

    private function formData(): array
    {
        return [
            'instructors' => User::where('role', 'instructor')->where('approved', true)->orderBy('first_name')->get(),
            'advisers' => User::where('role', 'adviser')->where('approved', true)->orderBy('first_name')->get(),
        ];
    }
}
