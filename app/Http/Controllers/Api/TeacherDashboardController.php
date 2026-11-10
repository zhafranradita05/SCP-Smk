<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Reminder;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    /**
     * Endpoint terpadu untuk Dashboard Guru Mobile (Sesuai Desain Wireframe)
     */
    public function getDashboard(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now('Asia/Jakarta');

        $daysMap = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        $monthsMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $currentDay = $daysMap[$now->format('l')] ?? $now->format('l');
        $formattedDate = $currentDay . ', ' . $now->day . ' ' . ($monthsMap[$now->month] ?? $now->format('F')) . ' ' . $now->year;

        $setting = Setting::first();
        $requestedCycle = $request->input('cycle') ?? $request->input('putaran');
        if ($requestedCycle) {
            $activeCycle = (str_contains(strtolower($requestedCycle), '2')) ? 'putaran_2' : 'putaran_1';
        } else {
            $activeCycle = $setting->active_cycle ?? 'putaran_1';
        }
        $cycleLabel = ($activeCycle === 'putaran_2') ? 'Putaran 2 (Siklus Aktif)' : 'Putaran 1 (Siklus Aktif)';
        $cycleVariants = ($activeCycle === 'putaran_2') 
            ? ['putaran_2', '2', 'Putaran 2', 'PUTARAN 2', 'PUT 2']
            : ['putaran_1', '1', 'Putaran 1', 'PUTARAN 1', 'PUT 1'];

        // Cek status Wali Kelas jika ada
        $homeroomClass = SchoolClass::where('homeroom_teacher_id', $user->id)->first();
        $roleSubtitle = $homeroomClass 
            ? "Guru Pengajar & Wali Kelas {$homeroomClass->name}"
            : ($user->role === 'wali_kelas' ? "Guru Pengajar & Wali Kelas" : "Guru Pengajar");

        // Ambil jadwal mengajar HANYA milik guru ini HARI INI pada siklus aktif
        // Sesuai aturan: Setiap guru HANYA bisa melihat jadwal mata pelajarannya sendiri (selain mapelnya tidak ditampilkan)
        $myActiveSchedulesToday = Schedule::with(['schoolClass', 'room', 'teacher'])
            ->where('day', $currentDay)
            ->whereIn('cycle', $cycleVariants)
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhereHas('teacher', function ($t) use ($user) {
                      $t->where('id', $user->id)
                        ->orWhere('name', $user->name)
                        ->orWhere('username', $user->username);
                  });
            })
            ->orderBy('start_time')
            ->get();

        // Cek apakah guru sendiri memiliki sesi mengajar hari ini untuk status Jurnal
        $myOwnTeachingToday = $myActiveSchedulesToday->where('teacher_id', $user->id);
        $hasScheduleToday = $myActiveSchedulesToday->isNotEmpty();
        $isJournalActive = $myOwnTeachingToday->isNotEmpty();

        $totalSessions = $myActiveSchedulesToday->count();
        $totalHours = 0;
        $scheduleItems = [];

        if ($hasScheduleToday) {
            foreach ($myActiveSchedulesToday as $index => $sched) {
                $start = Carbon::parse($sched->start_time);
                $end = Carbon::parse($sched->end_time);
                $diffHours = max(1, $start->diffInHours($end));
                $totalHours += $diffHours;

                $status = 'Mendatang';
                $currentTimeStr = $now->format('H:i:s');
                if ($currentTimeStr >= $sched->start_time && $currentTimeStr <= $sched->end_time) {
                    $status = 'Sedang Berlangsung';
                } elseif ($currentTimeStr > $sched->end_time) {
                    $status = 'Selesai';
                }

                $timeRange = substr($sched->start_time, 0, 5) . ' - ' . substr($sched->end_time, 0, 5);
                $isMyTeaching = ($sched->teacher_id == $user->id);

                $todayDate = $now->toDateString();
                $hasJournal = \App\Models\Journal::where('schedule_id', $sched->id)
                    ->where('date', $todayDate)
                    ->exists();

                $scheduleItems[] = [
                    'id'             => $sched->id,
                    'subject'        => $sched->subject,
                    'class_name'     => $sched->schoolClass->name ?? '-',
                    'room_name'      => $sched->room->name ?? 'Ruang Kelas',
                    'teacher_name'   => $sched->teacher->name ?? 'Guru Pengajar',
                    'is_my_teaching' => true,
                    'type_badge'     => 'Mengajar',
                    'day'            => $sched->day,
                    'cycle'          => $sched->cycle,
                    'start_time'     => substr($sched->start_time, 0, 5),
                    'end_time'       => substr($sched->end_time, 0, 5),
                    'time_range'     => $timeRange,
                    'status'         => $hasJournal ? 'Jurnal Selesai' : $status,
                    'has_journal'    => $hasJournal,
                    'journal_status' => $hasJournal ? 'Selesai Diisi' : 'Belum Diisi',
                ];
            }
        }

        // Jadwal siklus lain dinonaktifkan: Guru hanya berhak melihat jadwal pada siklus aktif yang ditentukan Waka Kurikulum
        $otherCycleScheduleItems = [];

        if ($hasScheduleToday) {

            // Reminders: Auto-check HANYA untuk sesi mengajar milik guru itu sendiri
            $todayDate = $now->toDateString();
            foreach ($myOwnTeachingToday as $mySched) {
                $startPlus30 = Carbon::parse($mySched->start_time)->addMinutes(30)->format('H:i:s');
                $currentTimeStr = $now->format('H:i:s');

                if ($currentTimeStr >= $startPlus30) {
                    $hasJournal = \App\Models\Journal::where('schedule_id', $mySched->id)
                        ->where('date', $todayDate)
                        ->exists();

                    if (!$hasJournal) {
                        $className = $mySched->schoolClass->name ?? 'Kelas';
                        $subject = $mySched->subject;
                        $message = "Perhatian: Anda belum mengisi Jurnal Mengajar untuk {$className} ({$subject}).";

                        $alreadyReminded = Reminder::where('teacher_id', $user->id)
                            ->where('message', $message)
                            ->whereDate('created_at', $todayDate)
                            ->exists();

                        if (!$alreadyReminded) {
                            $wakaId = \App\Models\User::where('role', 'waka_kurikulum')->value('id') ?? 1;
                            Reminder::create([
                                'waka_id'    => $wakaId,
                                'teacher_id' => $user->id,
                                'message'    => $message,
                                'is_read'    => false,
                            ]);
                        }
                    }
                }
            }

            $reminders = Reminder::where('teacher_id', $user->id)
                ->where('is_read', false)
                ->whereDate('created_at', $todayDate)
                ->latest()
                ->get();
        } else {
            // JURNAL NONAKTIF & MEMATIKAN NOTIFIKASI
            // Jika hari ini tidak ada jadwal mengajar, notifikasi pengingat dinonaktifkan/dikosongkan
            $reminders = [];
        }

        // Ambil jadwal mingguan guru (Senin - Jumat) KHUSUS SIKLUS AKTIF yang ditentukan Waka Kurikulum
        // HANYA mata pelajaran milik guru tersebut (selain mapelnya tidak ditampilkan)
        $allTeacherSchedules = Schedule::with(['schoolClass', 'room', 'teacher'])
            ->whereIn('cycle', $cycleVariants)
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhereHas('teacher', function ($t) use ($user) {
                      $t->where('id', $user->id)
                        ->orWhere('name', $user->name)
                        ->orWhere('username', $user->username);
                  });
            })
            ->orderBy('start_time')
            ->get();

        $weeklySchedules = [
            'putaran_1' => [
                'Senin' => [], 'Selasa' => [], 'Rabu' => [], 'Kamis' => [], 'Jumat' => []
            ],
            'putaran_2' => [
                'Senin' => [], 'Selasa' => [], 'Rabu' => [], 'Kamis' => [], 'Jumat' => []
            ]
        ];

        $allTeacherSchedulesList = [];
        foreach ($allTeacherSchedules as $sched) {
            $sDay = ucfirst(strtolower(trim($sched->day)));
            $sCycle = (trim($sched->cycle) === 'putaran_2' || trim($sched->cycle) === '2') ? 'putaran_2' : 'putaran_1';

            $item = [
                'id'             => $sched->id,
                'subject'        => $sched->subject,
                'class_name'     => $sched->schoolClass->name ?? '-',
                'room_name'      => $sched->room->name ?? 'Ruang Kelas',
                'teacher_name'   => $sched->teacher->name ?? 'Guru Pengajar',
                'is_my_teaching' => true,
                'type_badge'     => 'Mengajar',
                'day'            => $sched->day,
                'cycle'          => $sched->cycle,
                'start_time'     => substr($sched->start_time, 0, 5),
                'end_time'       => substr($sched->end_time, 0, 5),
                'time_range'     => substr($sched->start_time, 0, 5) . ' - ' . substr($sched->end_time, 0, 5),
                'status'         => 'Terjadwal'
            ];

            $allTeacherSchedulesList[] = $item;

            if (isset($weeklySchedules[$sCycle][$sDay])) {
                $weeklySchedules[$sCycle][$sDay][] = $item;
            }
        }

        // Hitung pending leave requests
        $pendingLeavesCount = LeaveRequest::where('status', 'pending')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'identifier' => $user->identifier ?? '-',
                    'role_subtitle' => $roleSubtitle,
                    'homeroom_class' => $homeroomClass ? $homeroomClass->name : null,
                ],
                'cycle' => [
                    'key' => $activeCycle,
                    'label' => $cycleLabel,
                ],
                'date' => [
                    'day' => $currentDay,
                    'formatted' => $formattedDate,
                ],
                'is_journal_active' => $isJournalActive,
                'journal_status_message' => $isJournalActive 
                    ? "Jurnal Aktif: Anda memiliki {$totalSessions} sesi mengajar hari ini." 
                    : "Jurnal Nonaktif: Tidak ada jadwal mengajar pada hari {$currentDay}.",
                'pending_leaves_count' => $pendingLeavesCount,
                'total_sessions_today' => $totalSessions,
                'total_hours_today' => $totalHours,
                'schedules_today' => $scheduleItems,
                'schedules' => $allTeacherSchedulesList,
                'other_cycle' => [
                    'key' => null,
                    'label' => null,
                    'has_schedules' => false,
                    'count' => 0,
                    'schedules_today' => [],
                ],
                'weekly_schedules' => $weeklySchedules,
                'reminders' => $reminders
            ]
        ]);
    }

    /**
     * Endpoint terdedikasi untuk mengambil Jadwal Mengajar 1 Minggu Penuh (Senin - Jumat)
     * Mendukung pemanggilan via HTTP GET maupun PUT.
     * Menerima opsi parameter siklus: cycle=putaran_1 atau cycle=putaran_2
     */
    public function getWeeklySchedule(Request $request)
    {
        $user = Auth::user();
        $setting = Setting::first();

        $requestedCycle = $request->input('cycle') ?? $request->input('putaran');
        if ($requestedCycle) {
            $activeCycle = (str_contains(strtolower($requestedCycle), '2')) ? 'putaran_2' : 'putaran_1';
        } else {
            $activeCycle = $setting->active_cycle ?? 'putaran_1';
        }
        $cycleLabel = ($activeCycle === 'putaran_2') ? 'Putaran 2 (Siklus Aktif)' : 'Putaran 1 (Siklus Aktif)';
        $cycleVariants = ($activeCycle === 'putaran_2') 
            ? ['putaran_2', '2', 'Putaran 2', 'PUTARAN 2', 'PUT 2']
            : ['putaran_1', '1', 'Putaran 1', 'PUTARAN 1', 'PUT 1'];

        // Ambil jadwal mingguan HANYA mapel yang diampu oleh guru tersebut (selain mapelnya tidak ditampilkan)
        $allTeacherSchedules = Schedule::with(['schoolClass', 'room', 'teacher'])
            ->whereIn('cycle', $cycleVariants)
            ->where(function ($q) use ($user) {
                $q->where('teacher_id', $user->id)
                  ->orWhereHas('teacher', function ($t) use ($user) {
                      $t->where('id', $user->id)
                        ->orWhere('name', $user->name)
                        ->orWhere('username', $user->username);
                  });
            })
            ->orderBy('start_time')
            ->get();

        $weeklyMap = [
            'Senin' => [], 'Selasa' => [], 'Rabu' => [], 'Kamis' => [], 'Jumat' => []
        ];

        $scheduleList = [];
        foreach ($allTeacherSchedules as $sched) {
            $sDay = ucfirst(strtolower(trim($sched->day)));

            $item = [
                'id'             => $sched->id,
                'subject'        => $sched->subject,
                'class_name'     => $sched->schoolClass->name ?? '-',
                'room_name'      => $sched->room->name ?? 'Ruang Kelas',
                'teacher_name'   => $sched->teacher->name ?? 'Guru Pengajar',
                'is_my_teaching' => true,
                'type_badge'     => 'Mengajar',
                'day'            => $sched->day,
                'cycle'          => $sched->cycle,
                'start_time'     => substr($sched->start_time, 0, 5),
                'end_time'       => substr($sched->end_time, 0, 5),
                'time_range'     => substr($sched->start_time, 0, 5) . ' - ' . substr($sched->end_time, 0, 5),
                'status'         => 'Terjadwal'
            ];

            $scheduleList[] = $item;
            if (isset($weeklyMap[$sDay])) {
                $weeklyMap[$sDay][] = $item;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id'   => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                ],
                'cycle' => [
                    'key'   => $activeCycle,
                    'label' => $cycleLabel,
                ],
                'weekly_schedules' => [
                    $activeCycle => $weeklyMap,
                    'putaran_1' => ($activeCycle === 'putaran_1') ? $weeklyMap : [],
                    'putaran_2' => ($activeCycle === 'putaran_2') ? $weeklyMap : [],
                ],
                'days' => $weeklyMap,
                'schedules' => $scheduleList,
                'total_schedules' => count($scheduleList),
            ]
        ]);
    }
}
