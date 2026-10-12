<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return view('notifications.index', [
            'alerts' => Alert::where('user_id', $request->user()->id)->latest()->paginate(15),
            'unread' => $request->user()->unreadAlertCount(),
        ]);
    }

    public function read(Request $request, Alert $alert)
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->update(['read_at' => $alert->read_at ?? now()]);

        return $alert->url ? redirect($alert->url) : back();
    }

    public function readAll(Request $request)
    {
        Alert::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
