<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReminderController extends Controller
{
    // API untuk aplikasi Mobile: Mengambil reminder sekaligus AUTO-CHECK 30 Menit
    public function index()
    {
        $teacherId = Auth::id();

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $daysMap = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        $currentDay = $daysMap[$now->format('l')] ?? $now->format('l');
        $activeCycle = Setting::first()->active_cycle ?? 'putaran_1';

        // Ambil jadwal guru hari ini
        $schedulesToday = Schedule::with(['schoolClass'])
            ->where('teacher_id', $teacherId)
            ->where('day', $currentDay)
            ->where('cycle', $activeCycle)
            ->get();

        foreach ($schedulesToday as $sched) {
            $startPlus30 = Carbon::parse($sched->start_time)->addMinutes(30)->format('H:i:s');

            // Cek jika jam sekarang sudah lewat (jam mulai + 30 menit)
            if ($currentTime >= $startPlus30) {
                $hasJournal = Journal::where('schedule_id', $sched->id)
                    ->where('date', $today)
                    ->exists();

                if (!$hasJournal) {
                    $className = $sched->schoolClass->name ?? 'Kelas';
                    $subject = $sched->subject;
                    $message = "Peringatan: Jadwal {$subject} di kelas {$className} telah berjalan lebih dari 30 menit. Anda belum mengisi jurnal mengajar hari ini!";

                    $alreadyReminded = Reminder::where('teacher_id', $teacherId)
                        ->where('message', $message)
                        ->whereDate('created_at', $today)
                        ->exists();

                    if (!$alreadyReminded) {
                        $wakaId = User::where('role', 'waka_kurikulum')->value('id') ?? 1;

                        Reminder::create([
                            'waka_id'    => $wakaId,
                            'teacher_id' => $teacherId,
                            'message'    => $message,
                            'is_read'    => false,
                        ]);
                    }
                }
            }
        }

        // Jika hari ini tidak ada jadwal mengajar, matikan notifikasi pengingat jurnal
        if ($schedulesToday->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }

        $reminders = Reminder::where('teacher_id', $teacherId)
                             ->where('is_read', false)
                             ->whereDate('created_at', $today)
                             ->latest()
                             ->get();

        return response()->json([
            'status' => 'success',
            'data' => $reminders
        ]);
    }

    public function markAsRead($id)
    {
        $reminder = Reminder::where('id', $id)->where('teacher_id', Auth::id())->first();
        
        if (!$reminder) {
            return response()->json(['status' => 'error', 'message' => 'Notifikasi tidak ditemukan'], 404);
        }

        $reminder->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi berhasil ditandai sebagai dibaca'
        ]);
    }
}
