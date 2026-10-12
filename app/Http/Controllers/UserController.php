<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $this->coordinatorOnly();

        return view('users.index', [
            'pending' => User::where('approved', false)->latest()->get(),
            'users' => User::where('approved', true)->orderBy('role')->orderBy('first_name')->get(),
        ]);
    }

    public function approve(User $user)
    {
        $this->coordinatorOnly();
        $user->update(['approved' => true]);
        Alert::notify([$user], 'Account approved', 'You can now use ThesisCap as '.$user->role_label.'.', route('dashboard'), 'info');

        return back()->with('success', $user->name.' was approved.');
    }

    public function reject(User $user)
    {
        $this->coordinatorOnly();
        abort_if($user->approved, 422, 'Only accounts that are waiting for approval can be rejected.');
        $name = $user->name;
        $user->delete();

        return back()->with('success', $name.' was rejected and the registration was removed.');
    }

    private function coordinatorOnly(): void
    {
        abort_unless(auth()->user()->isCoordinator(), 403, 'Only the project coordinator can manage accounts.');
    }
}
