<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\Setting;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    /**
     * Endpoint Terpadu untuk Dashboard Siswa Mobile
     * Menyediakan data profil, ringkasan izin, dan jadwal mingguan (Senin - Jumat)
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

        $currentDay = $daysMap[$now->format('l')] ?? 'Senin';
        $formattedDate = $currentDay . ', ' . $now->day . ' ' . ($monthsMap[$now->month] ?? $now->format('F')) . ' ' . $now->year;

        $setting = Setting::first();
        $activeCycle = $setting->active_cycle ?? 'putaran_1';
        $academicYear = $setting->academic_year ?? '2026/2027';
        $activeSemester = ucfirst($setting->active_semester ?? 'ganjil');

        // Tentukan siswa target (jika role Orang Tua, arahkan ke data anaknya)
        $targetStudent = ($user->role === 'orang_tua' && $user->student_id)
            ? ($user->student ?? \App\Models\User::with('schoolClass')->find($user->student_id))
            : $user;

        $targetClassId = $targetStudent ? $targetStudent->class_id : $user->class_id;
        $className = ($targetStudent && $targetStudent->schoolClass)
            ? $targetStudent->schoolClass->name
            : ($user->schoolClass->name ?? ($user->class_name ?? null));

        // Ambil data jadwal HANYA untuk kelas siswa yang bersangkutan
        if (!$targetClassId) {
            $schedules = collect();
        } else {
            $schedules = Schedule::with(['teacher', 'room', 'schoolClass'])
                ->where('class_id', $targetClassId)
                ->orderBy('start_time')
                ->get();
        }

        $daysList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $cyclesList = ['putaran_1', 'putaran_2'];

        $weeklySchedules = [
            'putaran_1' => [
                'Senin' => [],
                'Selasa' => [],
                'Rabu' => [],
                'Kamis' => [],
                'Jumat' => [],
            ],
            'putaran_2' => [
                'Senin' => [],
                'Selasa' => [],
                'Rabu' => [],
                'Kamis' => [],
                'Jumat' => [],
            ],
        ];

        $currentTimeStr = $now->format('H:i:s');
        $allFormattedSchedules = [];

        foreach ($schedules as $index => $sched) {
            $schedDay = ucfirst(strtolower($sched->day));
            $schedCycle = $sched->cycle ? strtolower($sched->cycle) : 'putaran_1';
            if ($schedCycle === '1') $schedCycle = 'putaran_1';
            if ($schedCycle === '2') $schedCycle = 'putaran_2';

            // Hitung status jika hari ini
            $status = 'Mendatang';
            if ($schedDay === $currentDay) {
                if ($currentTimeStr >= $sched->start_time && $currentTimeStr <= $sched->end_time) {
                    $status = 'Sedang Berlangsung';
                } elseif ($currentTimeStr > $sched->end_time) {
                    $status = 'Selesai';
                }
            }

            $startTimeFormatted = substr($sched->start_time, 0, 5);
            $endTimeFormatted = substr($sched->end_time, 0, 5);

            $item = [
                'id'           => $sched->id,
                'subject'      => $sched->subject,
                'teacher_name' => $sched->teacher->name ?? 'Guru Pengampu',
                'teacher_nip'  => $sched->teacher->identifier ?? '-',
                'room_name'    => $sched->room->name ?? 'Ruang Kelas',
                'class_name'   => $sched->schoolClass->name ?? ($className ?? 'Kelas Terdaftar'),
                'day'          => $schedDay,
                'cycle'        => $schedCycle,
                'start_time'   => $startTimeFormatted,
                'end_time'     => $endTimeFormatted,
                'time_range'   => "$startTimeFormatted - $endTimeFormatted",
                'status'       => $status,
            ];

            $allFormattedSchedules[] = $item;

            if (isset($weeklySchedules[$schedCycle][$schedDay])) {
                $weeklySchedules[$schedCycle][$schedDay][] = $item;
            }
        }

        // Ringkasan Izin Siswa (Gunakan student_id anak jika login sebagai Orang Tua)
        $targetStudentId = $targetStudent ? $targetStudent->id : $user->id;
        $approvedCount = LeaveRequest::where('student_id', $targetStudentId)->where('status', 'approved')->count();
        $pendingCount  = LeaveRequest::where('student_id', $targetStudentId)->where('status', 'pending')->count();
        $rejectedCount = LeaveRequest::where('student_id', $targetStudentId)->where('status', 'rejected')->count();

        // 5 data pengajuan izin terakhir siswa (untuk dipantau langsung oleh Siswa & Orang Tua)
        $recentLeaves = LeaveRequest::where('student_id', $targetStudentId)
            ->with('verifier:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($l) {
                return [
                    'id'            => $l->id,
                    'start_date'    => $l->start_date,
                    'end_date'      => $l->end_date,
                    'reason'        => $l->reason,
                    'status'        => $l->status,
                    'leave_type'    => $l->leave_type,
                    'start_time'    => $l->start_time ? substr($l->start_time, 0, 5) : null,
                    'end_time'      => $l->end_time ? substr($l->end_time, 0, 5) : null,
                    'proof_file'    => $l->proof_file,
                    'verifier_name' => $l->verifier->name ?? null,
                    'created_at'    => $l->created_at ? $l->created_at->format('d M Y H:i') : null,
                ];
            });

        // Status presensi harian siswa hari ini (hadir / sakit / izin / alfa)
        $todayAttendance = \App\Models\Attendance::where('student_id', $targetStudentId)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->first();

        $todayAttendanceData = [
            'status' => $todayAttendance ? $todayAttendance->status : 'belum_absen',
            'label'  => $todayAttendance ? ucfirst($todayAttendance->status) : 'Belum Ada Presensi Hari Ini',
            'time'   => $todayAttendance && $todayAttendance->created_at ? $todayAttendance->created_at->format('H:i') : null,
        ];

        $roleSubtitle = $user->role === 'orang_tua'
            ? ('Orang Tua dari ' . ($targetStudent->name ?? 'Siswa') . ' (' . ($className ?? 'Peserta Didik') . ')')
            : ($className ?? 'Siswa / Peserta Didik');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'identifier'    => $user->identifier ?? '-',
                    'class_name'    => $className ?? 'Siswa / Peserta Didik',
                    'role'          => $user->role,
                    'role_subtitle' => $roleSubtitle,
                    'student_name'  => $targetStudent ? $targetStudent->name : null,
                    'student_nis'   => $targetStudent ? $targetStudent->identifier : null,
                ],
                'active_cycle'           => $activeCycle,
                'cycle_label'            => $activeCycle === 'putaran_2' ? 'Putaran 2 (Siklus Aktif)' : 'Putaran 1 (Siklus Aktif)',
                'current_day'            => $currentDay,
                'current_date_formatted' => $formattedDate,
                'academic_year'          => $academicYear,
                'semester'               => $activeSemester,
                'today_attendance'       => $todayAttendanceData,
                'leave_summary'          => [
                    'approved' => $approvedCount,
                    'pending'  => $pendingCount,
                    'rejected' => $rejectedCount,
                    'total'    => $approvedCount + $pendingCount + $rejectedCount,
                ],
                'recent_leaves'          => $recentLeaves,
                'days'                   => $daysList,
                'schedules'              => $allFormattedSchedules,
                'weekly_schedules'       => $weeklySchedules,
            ]
        ]);
    }
}

