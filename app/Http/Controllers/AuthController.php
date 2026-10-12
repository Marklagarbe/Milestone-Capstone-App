<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials + ['approved' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        // Correct password but the account is still waiting for the coordinator.
        $pending = User::where('email', $credentials['email'])->where('approved', false)->first();
        if ($pending && Hash::check($credentials['password'], $pending->password)) {
            return back()->withErrors(['email' => 'Your account is waiting for approval by the project coordinator. You will be able to log in once it is approved.'])->onlyInput('email');
        }

        return back()->withErrors(['email' => 'These credentials do not match our records.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:student,instructor,adviser'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please agree to the Terms of Service and Privacy Policy.',
        ]);

        // Students are active right away. Instructors and advisers need the coordinator's approval.
        $approved = $data['role'] === 'student';

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'approved' => $approved,
            'password' => $data['password'],
        ]);

        if (! $approved) {
            Alert::notify(User::where('role', 'coordinator')->pluck('id'), 'Account waiting for approval',
                $user->name.' registered as '.$user->role_label.'.', route('users.index'), 'info');

            return redirect()->route('login')->with('success', 'Account created. The project coordinator must approve it before you can log in.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome to ThesisCap! Your account is ready.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
