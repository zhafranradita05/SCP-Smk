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

        // Simpan foto kelas
        $photoPath = $request->file('photo')->store('journals', 'public');
        $today = date('Y-m-d');

        // 3. Simpan data Jurnal
        $journal = Journal::create([
            'schedule_id' => $request->schedule_id,
            'materi' => $request->materi,
            'photo' => $photoPath,
            'date' => $today
        ]);


        // ==========================================
        // 4. ALGORITMA AUTO-SYNC PRESENSI (UKK)
        // MENDUKUNG IZIN SEHARIAN & IZIN POTONG JAM
        // ==========================================
        $schedule = \App\Models\Schedule::find($request->schedule_id);
        $schedStart = $schedule ? $schedule->start_time : '00:00:00';
        $schedEnd   = $schedule ? $schedule->end_time   : '23:59:59';

        // Cari semua data izin yang statusnya 'approved' HARI INI
        $approvedLeaves = LeaveRequest::where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get();

        $syncedCount = 0;

        foreach ($approvedLeaves as $leave) {
            $isOverlapping = false;

            if ($leave->leave_type === 'partial' && $leave->start_time && $leave->end_time) {
                // Rumus Irisan Waktu:
                // Izin berlaku jika jam izin bersinggungan dengan jam jadwal pelajaran ini
                if ($leave->start_time < $schedEnd && $leave->end_time > $schedStart) {
                    $isOverlapping = true;
                }
            } else {
                // Izin Seharian Penuh: Berlaku untuk seluruh jam pelajaran hari ini
                $isOverlapping = true;
            }

            // Jika bersinggungan, masukkan otomatis ke Attendances
            if ($isOverlapping) {
                Attendance::create([
                    'journal_id' => $journal->id,
                    'student_id' => $leave->student_id,
                    // Jika alasan mengandung kata 'sakit', catat 'sakit', selain itu 'izin'
                    'status'     => stripos($leave->reason, 'sakit') !== false ? 'sakit' : 'izin' 
                ]);
                $syncedCount++;
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Jurnal berhasil disimpan. ' . $syncedCount . ' Siswa otomatis tercatat Izin/Sakit.',
            'data' => $journal
        ]);
    }
}
