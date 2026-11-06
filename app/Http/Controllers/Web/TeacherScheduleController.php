<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TeacherScheduleController extends Controller
{
    /**
     * Halaman Jadwal Mengajar Guru & Status Jurnal
     * Menampilkan jadwal mengajar milik guru yang telah dibuat oleh Waka Kurikulum,
     * status keaktifan jurnal (Aktif jika ada jadwal, Nonaktif jika tidak ada),
     * serta tampilan kisi jadwal mingguan (FET-style Timetable Grid).
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $setting = Setting::first();
        $systemCycle = $setting->active_cycle ?? 'putaran_1';
        $selectedCycle = $request->get('cycle', $systemCycle);

        $now = Carbon::now('Asia/Jakarta');
        $todayDate = $now->toDateString();

        $daysMap = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        $currentDay = $daysMap[$now->format('l')] ?? 'Senin';
        $todayDateFormatted = $currentDay . ', ' . $now->translatedFormat('d F Y');

        // 1. Jadwal Mengajar Guru Hari Ini (pada siklus aktif)
        $todaySchedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('day', $currentDay)
            ->where('cycle', $systemCycle)
            ->orderBy('start_time')
            ->get();

        // 2. Cek apakah ada jadwal mengajar hari ini -> Menentukan Jurnal AKTIF / NONAKTIF
        $isJournalActive = $todaySchedules->isNotEmpty();

        // Tambahkan informasi apakah jurnal hari ini sudah diisi untuk setiap jadwal
        foreach ($todaySchedules as $sched) {
            $sched->journal = Journal::where('schedule_id', $sched->id)
                ->where('date', $todayDate)
                ->first();
            $sched->is_filled = !is_null($sched->journal);
        }

        // 3. Ambil seluruh jadwal mingguan guru (Senin - Jumat) pada siklus yang dipilih
        $allSchedules = Schedule::with(['schoolClass', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle', $selectedCycle)
            ->orderBy('start_time')
            ->get();

        $weeklySchedules = $allSchedules->groupBy('day');

        // 4. Struktur Grid Matrix (FET-style Timetable)
        $gridTimeSlots = [
            '07:15', '08:00', '08:45', '09:45', '10:30',
            '11:15', '12:30', '13:15', '14:00', '14:45'
        ];
        $daysList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        // Pemetaan sel matriks [day][slot] -> Schedule object
        $gridMatrix = [];
        foreach ($daysList as $d) {
            foreach ($gridTimeSlots as $slot) {
                $gridMatrix[$d][$slot] = null;
            }
        }

        foreach ($allSchedules as $sched) {
            $sDay = $sched->day;
            if (!in_array($sDay, $daysList)) continue;

            $sStart = substr($sched->start_time, 0, 5);
            $sEnd = substr($sched->end_time, 0, 5);

            foreach ($gridTimeSlots as $slot) {
                // Selama jam slot berada dalam rentang [start_time, end_time)
                if ($slot >= $sStart && $slot < $sEnd) {
                    $gridMatrix[$sDay][$slot] = $sched;
                }
            }
        }

        // 5. Data Tambahan Peran (Wali Kelas / BK)
        $homeroomClass = SchoolClass::where('homeroom_teacher_id', $user->id)->first();
        $bkClasses = SchoolClass::where('bk_teacher_id', $user->id)->get();

        return view('teacher.schedule', compact(
            'user',
            'setting',
            'systemCycle',
            'selectedCycle',
            'currentDay',
            'todayDateFormatted',
            'todaySchedules',
            'isJournalActive',
            'allSchedules',
            'weeklySchedules',
            'gridTimeSlots',
            'daysList',
            'gridMatrix',
            'homeroomClass',
            'bkClasses'
        ));
    }
}

