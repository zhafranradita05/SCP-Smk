<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class JournalController extends Controller
{
    /**
     * Mengambil riwayat arsip jurnal yang pernah diisi oleh guru yang login
     * Mendukung filter kata kunci materi, filter kelas, dan filter bulan
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        $query = Journal::with(['schedule.schoolClass', 'schedule.room', 'attendances.student'])
            ->whereHas('schedule', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });

        // 1. Filter Pencarian Materi
        if ($request->filled('q')) {
            $query->where('materi', 'like', '%' . $request->q . '%');
        }

        // 2. Filter Kelas
        if ($request->filled('class_id') && $request->class_id !== 'all') {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }

        // 3. Filter Bulan (Format: YYYY-MM) atau Tanggal Spesifik
        if ($request->filled('date')) {
            $query->where('date', $request->date);
        } elseif ($request->filled('month')) {
            $query->where('date', 'like', $request->month . '%');
        }

        $journals = $query->orderBy('date', 'desc')->orderBy('id', 'desc')->get();

        $data = $journals->map(function ($j) {
            return [
                'id' => $j->id,
                'date' => $j->date,
                'materi' => $j->materi,
                'photo' => $j->photo,
                'class_name' => $j->schedule->schoolClass->name ?? '-',
                'class_id' => $j->schedule->class_id ?? null,
                'subject' => $j->schedule->subject ?? '-',
                'room_name' => $j->schedule->room->name ?? '-',
                'start_time' => substr($j->schedule->start_time ?? '07:00', 0, 5),
                'end_time' => substr($j->schedule->end_time ?? '10:00', 0, 5),
                'attendances' => $j->attendances->map(function ($a) {
                    return [
                        'student_name' => $a->student->name ?? 'Siswa',
                        'status' => $a->status,
                    ];
                }),
            ];
        });

        // Daftar kelas yang diajar guru ini untuk tombol filter
        $classes = \App\Models\SchoolClass::whereHas('schedules', function ($q) use ($user) {
            $q->where('teacher_id', $user->id);
        })->distinct()->get(['id', 'name']);

        return response()->json([
            'status' => 'success',
            'classes' => $classes,
            'data' => $data,
        ]);
    }

    /**
     * Mengambil daftar siswa untuk absensi pada suatu jadwal
     * Menghitung status default: semua siswa otomatis terdeteksi hadir (is_attended = true),
     * kecuali siswa yang memiliki izin/sakit yang sudah disetujui untuk hari ini
     * otomatis uncheck (is_attended = false) dengan status 'izin' atau 'sakit' serta catatan izinnya.
     */
    public function getStudentsForAttendance(Request $request, $scheduleId)
    {
        $user = Auth::user();
        $schedule = \App\Models\Schedule::with('schoolClass')->find($scheduleId);

        if (!$schedule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jadwal pelajaran tidak ditemukan!',
            ], 404);
        }

        // Ambil semua siswa di kelas jadwal ini
        $students = \App\Models\User::where('role', 'siswa')
            ->where('class_id', $schedule->class_id)
            ->orderBy('name', 'asc')
            ->get();

        // Cari izin yang sudah di-approve untuk hari ini
        $today = date('Y-m-d');
        $schedStart = $schedule->start_time ?? '00:00:00';
        $schedEnd   = $schedule->end_time   ?? '23:59:59';

        $approvedLeaves = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->whereIn('student_id', $students->pluck('id'))
            ->get()
            ->keyBy('student_id');

        $studentList = $students->map(function ($s) use ($approvedLeaves, $schedStart, $schedEnd) {
            $hasLeave = $approvedLeaves->get($s->id);
            $isOverlapping = false;
            $leaveInfo = null;
            $status = 'hadir';

            if ($hasLeave) {
                if ($hasLeave->leave_type === 'partial' && $hasLeave->start_time && $hasLeave->end_time) {
                    if ($hasLeave->start_time < $schedEnd && $hasLeave->end_time > $schedStart) {
                        $isOverlapping = true;
                        $leaveInfo = "Izin Jam (" . substr($hasLeave->start_time, 0, 5) . "-" . substr($hasLeave->end_time, 0, 5) . "): " . $hasLeave->reason;
                    }
                } else {
                    $isOverlapping = true;
                    $leaveInfo = "Izin dari Pagi: " . $hasLeave->reason;
                }

                if ($isOverlapping) {
                    $status = stripos($hasLeave->reason, 'sakit') !== false ? 'sakit' : 'izin';
                }
            }

            return [
                'id' => $s->id,
                'name' => $s->name,
                'identifier' => $s->identifier ?? '-',
                'status' => $status,
                'is_attended' => ($status === 'hadir'), // Otomatis tercentang hadir jika tidak ada izin
                'has_leave' => $isOverlapping,
                'leave_info' => $leaveInfo,
            ];
        });

        // Cek apakah jurnal sudah pernah diisi untuk jadwal ini hari ini
        $existingJournal = Journal::where('schedule_id', $schedule->id)
            ->where('date', $today)
            ->first();

        return response()->json([
            'status' => 'success',
            'is_already_filled' => ($existingJournal !== null),
            'existing_journal' => $existingJournal ? [
                'id' => $existingJournal->id,
                'materi' => $existingJournal->materi,
                'photo' => $existingJournal->photo,
                'time_submitted' => $existingJournal->created_at ? $existingJournal->created_at->format('H:i') : null,
            ] : null,
            'schedule' => [
                'id' => $schedule->id,
                'class_name' => $schedule->schoolClass->name ?? 'Kelas',
                'class_id' => $schedule->class_id,
                'subject' => $schedule->subject ?? '-',
                'time' => substr($schedule->start_time ?? '', 0, 5) . ' - ' . substr($schedule->end_time ?? '', 0, 5),
            ],
            'total_students' => $studentList->count(),
            'default_hadir_count' => $studentList->where('is_attended', true)->count(),
            'default_izin_count' => $studentList->where('is_attended', false)->count(),
            'students' => $studentList->values(),
        ]);
    }

    public function store(Request $request)
    {
        // 2. Validasi input Jurnal dari HP Guru
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'materi' => 'required|string',
            'photo' => 'required|image|max:2048' 
        ]);

        $schedule = \App\Models\Schedule::find($request->schedule_id);
        if (!$schedule) {
            return response()->json(['status' => 'error', 'message' => 'Jadwal tidak ditemukan!'], 404);
        }

        // Validasi hak akses guru mengajar
        if ($schedule->teacher_id !== Auth::id()) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses mengisi jurnal untuk jadwal ini!'], 403);
        }

        // Validasi keaktifan hari jadwal (Jurnal hanya aktif jika hari ini ada jadwal mengajar)
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $daysMap = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $currentDay = $daysMap[$now->format('l')] ?? 'Senin';

        if (strtolower($schedule->day) !== strtolower($currentDay)) {
            return response()->json([
                'status' => 'error',
                'message' => "Jurnal NONAKTIF: Pengisian jurnal hanya dapat dilakukan pada hari jadwal mengajar ({$schedule->day}). Hari ini adalah {$currentDay}."
            ], 422);
        }

        $today = date('Y-m-d');

        // Validasi Mutlak: Jika jurnal untuk jadwal ini sudah diisi hari ini, tidak boleh diisi lagi
        $alreadyFilled = Journal::where('schedule_id', $request->schedule_id)
            ->where('date', $today)
            ->exists();

        if ($alreadyFilled) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Jurnal untuk sesi jadwal mengajar ini sudah berhasil diisi hari ini dan tidak dapat diisi ulang!'
            ], 422);
        }

        // Simpan foto kelas
        $photoPath = $request->file('photo')->store('journals', 'public');

        // 3. Simpan data Jurnal
        $journal = Journal::create([
            'schedule_id' => $request->schedule_id,
            'materi' => $request->materi,
            'photo' => $photoPath,
            'date' => $today
        ]);

        // ==========================================
        // 4. SIMPAN PRESENSI SISWA & AUTO-SYNC IZIN
        // ==========================================
        $schedStart = $schedule->start_time ?? '00:00:00';
        $schedEnd   = $schedule->end_time   ?? '23:59:59';

        $attendancesInput = [];
        if ($request->filled('attendances')) {
            $rawAttendances = $request->input('attendances');
            if (is_string($rawAttendances)) {
                $attendancesInput = json_decode($rawAttendances, true) ?? [];
            } elseif (is_array($rawAttendances)) {
                $attendancesInput = $rawAttendances;
            }
        }

        $summary = [
            'total' => 0,
            'hadir' => 0,
            'izin'  => 0,
            'sakit' => 0,
            'alfa'  => 0,
        ];

        if (!empty($attendancesInput)) {
            // Guru mengonfirmasi absensi siswa dari aplikasi mobile
            foreach ($attendancesInput as $att) {
                $studentId = $att['student_id'] ?? $att['id'] ?? null;
                $status = strtolower($att['status'] ?? 'hadir');
                if (!in_array($status, ['hadir', 'sakit', 'izin', 'alfa'])) {
                    $status = 'hadir';
                }

                if ($studentId) {
                    Attendance::updateOrCreate(
                        [
                            'journal_id' => $journal->id,
                            'student_id' => $studentId,
                        ],
                        [
                            'status' => $status,
                        ]
                    );
                    $summary['total']++;
                    $summary[$status] = ($summary[$status] ?? 0) + 1;
                }
            }
        } else {
            // Fallback otomatis: Simpan semua siswa di kelas sebagai Hadir,
            // kecuali yang memiliki izin approved dari pagi/jam ini sebagai Izin/Sakit
            $classStudents = \App\Models\User::where('role', 'siswa')
                ->where('class_id', $schedule->class_id)
                ->get();

            $approvedLeaves = LeaveRequest::where('status', 'approved')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->whereIn('student_id', $classStudents->pluck('id'))
                ->get()
                ->keyBy('student_id');

            foreach ($classStudents as $student) {
                $hasLeave = $approvedLeaves->get($student->id);
                $isOverlapping = false;
                $status = 'hadir';

                if ($hasLeave) {
                    if ($hasLeave->leave_type === 'partial' && $hasLeave->start_time && $hasLeave->end_time) {
                        if ($hasLeave->start_time < $schedEnd && $hasLeave->end_time > $schedStart) {
                            $isOverlapping = true;
                        }
                    } else {
                        $isOverlapping = true;
                    }

                    if ($isOverlapping) {
                        $status = stripos($hasLeave->reason, 'sakit') !== false ? 'sakit' : 'izin';
                    }
                }

                Attendance::create([
                    'journal_id' => $journal->id,
                    'student_id' => $student->id,
                    'status'     => $status,
                ]);

                $summary['total']++;
                $summary[$status] = ($summary[$status] ?? 0) + 1;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Jurnal & Presensi berhasil disimpan ({$summary['hadir']} Hadir, {$summary['izin']} Izin, {$summary['sakit']} Sakit, {$summary['alfa']} Alpa).",
            'attendance_summary' => $summary,
            'data' => $journal
        ]);
    }
}
