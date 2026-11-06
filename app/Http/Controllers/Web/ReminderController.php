<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReminderController extends Controller
{
    // Method untuk Waka di Web: Mengirimkan reminder manual
    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'message' => 'required|string'
        ]);

        Reminder::create([
            'waka_id' => Auth::id(),
            'teacher_id' => $request->teacher_id,
            'message' => $request->message,
        ]);

        return back()->with('success', 'Reminder berhasil dikirim ke guru bersangkutan.');
    }

    public function markAsRead($id)
    {
        $reminder = Reminder::where('id', $id)->where('teacher_id', Auth::id())->firstOrFail();
        $reminder->update(['is_read' => true]);

        return back()->with('success', 'Notifikasi telah ditandai sebagai dibaca.');
    }
}
