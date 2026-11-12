<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    // 1. (SISWA) Mengajukan Izin/Sakit (Mendukung Seharian Penuh & Memotong Jam Tertentu)
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string',
            'proof_file' => 'required|image|max:2048', // Maksimal 2MB, wajib gambar
            'leave_type' => 'nullable|in:full_day,partial',
            'start_time' => 'required_if:leave_type,partial|nullable',
            'end_time'   => 'required_if:leave_type,partial|nullable',
        ]);

        $user = Auth::user();
        $targetStudentId = ($user->role === 'orang_tua' && $user->student_id) ? $user->student_id : $user->id;

        $now = Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();

        // 1. Batas tanggal minimal: Tidak boleh mengajukan izin untuk tanggal yang sudah lewat (backdate)
        if (Carbon::parse($request->start_date, 'Asia/Jakarta')->startOfDay()->lt(Carbon::today('Asia/Jakarta'))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Pengajuan izin tidak dapat dilakukan untuk tanggal yang sudah lewat. Izin harus diajukan untuk hari ini atau tanggal mendatang.'
            ], 422);
        }

        // 2. Batas waktu pengisian izin di hari H
        $leaveType = $request->leave_type ?? 'full_day';
        if ($request->start_date === $today) {
            if ($leaveType === 'full_day') {
                // Batas pengisian izin seharian penuh maksimal pukul 12:00 WIB
                if ($now->format('H:i') > '12:00') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Batas waktu pengisian izin seharian penuh untuk hari ini telah berakhir (maksimal pukul 12:00 WIB). Silakan hubungi Wali Kelas atau Guru BK untuk konfirmasi.'
                    ], 422);
                }
            } elseif ($leaveType === 'partial') {
                $endTimeFormatted = strlen($request->end_time) === 5 ? $request->end_time . ':00' : $request->end_time;
                if ($endTimeFormatted <= $now->format('H:i:s')) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Jam selesai izin yang Anda pilih (' . substr($request->end_time, 0, 5) . ') sudah terlewat untuk hari ini.'
                    ], 422);
                }
            }
        }

        // 3. Batasi pengajuan maksimal 1 kali dalam 1 hari per siswa (membatasi jumlah antrean approval di wali kelas)
        $alreadySubmittedToday = LeaveRequest::where('student_id', $targetStudentId)
            ->whereDate('created_at', $today)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($alreadySubmittedToday) {
            $statusLabel = $alreadySubmittedToday->status === 'approved' ? 'sudah disetujui' : 'sedang menunggu verifikasi';
            return response()->json([
                'status'  => 'error',
                'message' => "Pengajuan izin dibatasi maksimal 1 kali dalam 1 hari untuk setiap siswa. Pengajuan izin Anda hari ini {$statusLabel}."
            ], 422);
        }

        // 4. Cegah pengajuan ganda pada rentang tanggal yang saling bertumpuk (overlapping)
        $overlappingLeave = LeaveRequest::where('student_id', $targetStudentId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) use ($request) {
                $query->whereBetween('start_date', [$request->start_date, $request->end_date])
                    ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                    ->orWhere(function ($sub) use ($request) {
                        $sub->where('start_date', '<=', $request->start_date)
                            ->where('end_date', '>=', $request->end_date);
                    });
            })
            ->first();

        if ($overlappingLeave) {
            $statusLabel = $overlappingLeave->status === 'approved' ? 'sudah disetujui' : 'sedang menunggu verifikasi';
            $rangeText = $overlappingLeave->start_date === $overlappingLeave->end_date 
                ? $overlappingLeave->start_date 
                : "{$overlappingLeave->start_date} s/d {$overlappingLeave->end_date}";
            return response()->json([
                'status'  => 'error',
                'message' => "Siswa sudah memiliki pengajuan izin pada tanggal {$rangeText} yang {$statusLabel}."
            ], 422);
        }

        // Simpan file foto/surat ke folder public/storage/leaves
        $path = $request->file('proof_file')->store('leaves', 'public');

        $reason = trim($request->reason);
        if ($user->role === 'orang_tua') {
            $reason = '[Diajukan Orang Tua] ' . $reason;
        }

        // Simpan data izin ke database (Otomatis statusnya 'pending' dan terkait langsung dengan siswa)
        $leave = LeaveRequest::create([
            'student_id' => $targetStudentId,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'reason'     => $reason,
            'proof_file' => $path,
            'status'     => 'pending',
            'leave_type' => $leaveType,
            'start_time' => $leaveType === 'partial' ? $request->start_time : null,
            'end_time'   => $leaveType === 'partial' ? $request->end_time : null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Pengajuan izin berhasil dikirim! Menunggu verifikasi.',
            'data'    => $leave
        ]);
    }

    // 2. (WALI KELAS / BK) Melihat daftar izin yang masih 'pending'
    public function indexPending()
    {
        $user = Auth::user();

        // Ambil data izin pending beserta data siswa dan kelasnya
        $query = LeaveRequest::with(['student.schoolClass'])->where('status', 'pending');

        // Cari kelas binaan (jika ada) secara fleksibel
        $homeroomClass = \App\Models\SchoolClass::where('homeroom_teacher_id', $user->id)->first();
        if (!$homeroomClass) {
            $homeroomClass = \App\Models\SchoolClass::whereHas('homeroomTeacher', function($t) use ($user) {
                $t->where('name', $user->name)->orWhere('username', $user->username);
            })->first();
        }

        // Jika login sebagai WALI KELAS atau memiliki kelas binaan: Hanya ambil izin murid di kelas binaannya saja
        if ($user->role === 'wali_kelas' || $homeroomClass) {
            if (!$homeroomClass) {
                return response()->json(['status' => 'success', 'data' => []]);
            }

            $query->whereHas('student', function ($q) use ($homeroomClass) {
                $q->where('class_id', $homeroomClass->id);
            });
        } elseif ($user->role === 'guru_bk') {
            // Guru BK: Jika memiliki kelas binaan khusus, filter hanya ke kelas-kelas binaannya
            $assignedClassIds = \App\Models\SchoolClass::where('bk_teacher_id', $user->id)->pluck('id')->toArray();
            if (!empty($assignedClassIds)) {
                $query->whereHas('student', function ($q) use ($assignedClassIds) {
                    $q->whereIn('class_id', $assignedClassIds);
                });
            }
        } elseif ($user->role === 'guru') {
            // Guru biasa tanpa kelas binaan tidak memverifikasi izin
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'status' => 'success',
            'data'   => $leaves
        ]);
    }

    // 3. (KHUSUS WALI KELAS) Memverifikasi/Approve Izin Siswa Kelas Binaan
    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        // Cari kelas yang dibina wali kelas ini secara fleksibel
        $homeroomClass = \App\Models\SchoolClass::where('homeroom_teacher_id', $user->id)->first();
        if (!$homeroomClass) {
            $homeroomClass = \App\Models\SchoolClass::whereHas('homeroomTeacher', function($t) use ($user) {
                $t->where('name', $user->name)->orWhere('username', $user->username);
            })->first();
        }

        // Aturan: Hanya Wali Kelas yang memiliki hak akses untuk menyetujui / menolak izin
        if ($user->role !== 'wali_kelas' && !$homeroomClass) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya Wali Kelas yang berhak menyetujui izin siswa. Guru BK hanya bertugas merekap dan memantau kehadiran.'
            ], 403);
        }

        $leave = LeaveRequest::with('student')->find($id);

        if (!$leave) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan!'], 404);
        }

        if (!$homeroomClass || !$leave->student || $leave->student->class_id !== $homeroomClass->id) {
            $namaKelasBinaan = $homeroomClass ? $homeroomClass->name : 'Tidak ada kelas binaan';
            return response()->json([
                'status'  => 'error',
                'message' => "Anda hanya berhak menyetujui permohonan izin untuk siswa di kelas binaan Anda ($namaKelasBinaan)!"
            ], 403);
        }

        $status = in_array($request->status, ['approved', 'rejected']) ? $request->status : 'approved';

        $leave->update([
            'status'      => $status,
            'verified_by' => $user->id
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Izin berhasil ' . ($status === 'approved' ? 'disetujui' : 'ditolak') . ' oleh Wali Kelas.'
        ]);
    }

    // 3B. (KHUSUS WALI KELAS) Menolak Izin Siswa
    public function reject(Request $request, $id)
    {
        $request->merge(['status' => 'rejected']);
        return $this->approve($request, $id);
    }

    // 4. (SISWA) Mengambil riwayat & rekapan izin milik siswa yang sedang login
    public function myLeaves()
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // Tentukan siswa target (jika Orang Tua, ambil data riwayat izin anaknya)
        $targetStudent = ($user->role === 'orang_tua' && $user->student_id)
            ? ($user->student ?? \App\Models\User::with('schoolClass')->find($user->student_id))
            : $user;
        $targetStudentId = $targetStudent ? $targetStudent->id : $user->id;

        $user->load('schoolClass');
        $className = ($targetStudent && $targetStudent->schoolClass)
            ? $targetStudent->schoolClass->name
            : ($user->schoolClass->name ?? null);

        // Ambil data izin milik siswa yang bersangkutan
        $leaves = LeaveRequest::where('student_id', $targetStudentId)
            ->with('verifier:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        // Hitung rekapan status izin
        $summary = [
            'total'    => $leaves->count(),
            'approved' => $leaves->where('status', 'approved')->count(),
            'pending'  => $leaves->where('status', 'pending')->count(),
            'rejected' => $leaves->where('status', 'rejected')->count(),
        ];

        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $submittedToday = $leaves->first(function ($item) use ($today) {
            return Carbon::parse($item->created_at)->toDateString() === $today
                && in_array($item->status, ['pending', 'approved']);
        });

        $roleSubtitle = $user->role === 'orang_tua'
            ? ('Orang Tua dari ' . ($targetStudent->name ?? 'Siswa'))
            : ($className ?? 'Siswa');

        return response()->json([
            'status'  => 'success',
            'user'    => [
                'id'            => $user->id,
                'name'          => $user->name,
                'identifier'    => $user->identifier,
                'class_name'    => $className,
                'role'          => $user->role,
                'role_subtitle' => $roleSubtitle,
                'student_name'  => $targetStudent ? $targetStudent->name : null,
                'student_nis'   => $targetStudent ? $targetStudent->identifier : null,
            ],
            'can_submit_today' => ($submittedToday === null),
            'submitted_today'  => $submittedToday ? [
                'id'         => $submittedToday->id,
                'status'     => $submittedToday->status,
                'start_date' => $submittedToday->start_date,
                'end_date'   => $submittedToday->end_date,
            ] : null,
            'summary' => $summary,
            'data'    => $leaves
        ]);
    }
        // 5. (WALI KELAS) Mengambil Rekapitulasi Izin Semester untuk Kelas Binaan
    public function recapSemester(Request $request)
    {
        $user = Auth::user();
        $isBK = $user->role === 'guru_bk';

        $allClasses = \App\Models\SchoolClass::orderBy('name')->get(['id', 'name']);
        $availableClasses = collect();

        // 1. Cek kelas binaan wali kelas
        $homeroomClass = \App\Models\SchoolClass::where('homeroom_teacher_id', $user->id)->first();
        if (!$homeroomClass) {
            $homeroomClass = \App\Models\SchoolClass::whereHas('homeroomTeacher', function ($t) use ($user) {
                $t->where('id', $user->id)
                  ->orWhere('name', 'like', '%' . $user->name . '%')
                  ->orWhere('username', $user->username);
            })->first();
        }
        $isWaliKelas = ($user->role === 'wali_kelas' || $homeroomClass !== null);

        // 2. Cek kelas binaan BK
        $bkClasses = \App\Models\SchoolClass::where('bk_teacher_id', $user->id)->orderBy('name')->get(['id', 'name']);
        if ($bkClasses->isEmpty()) {
            $bkClasses = \App\Models\SchoolClass::whereHas('bkTeacher', function ($t) use ($user) {
                $t->where('id', $user->id)
                  ->orWhere('name', 'like', '%' . $user->name . '%')
                  ->orWhere('username', $user->username);
            })->get(['id', 'name']);
        }

        // 3. Cek kelas yang diajar oleh guru ini (sebagai guru pengampu/mapel)
        $teachingClassIds = \App\Models\Schedule::where('teacher_id', $user->id)
            ->pluck('class_id')
            ->filter()
            ->unique();
        $teachingClasses = \App\Models\SchoolClass::whereIn('id', $teachingClassIds)->orderBy('name')->get(['id', 'name']);

        // Kumpulkan kelas yang relevan
        if ($homeroomClass) {
            $availableClasses->push($homeroomClass);
        }
        foreach ($bkClasses as $bc) {
            if (!$availableClasses->contains('id', $bc->id)) {
                $availableClasses->push($bc);
            }
        }
        foreach ($teachingClasses as $tc) {
            if (!$availableClasses->contains('id', $tc->id)) {
                $availableClasses->push($tc);
            }
        }

        // Jika Guru BK, Waka Kurikulum, atau belum ada jadwal terhubung, berikan opsi semua kelas
        if ($isBK || $user->role === 'waka_kurikulum' || $availableClasses->isEmpty()) {
            $availableClasses = $allClasses;
        }

        // Tentukan kelas yang dipilih
        $selectedClassId = $request->query('class_id');
        if ($selectedClassId) {
            $schoolClass = \App\Models\SchoolClass::find($selectedClassId);
        } else {
            // Prioritas default: kelas binaan wali kelas -> kelas pertama yang diajar -> kelas pertama yang ada
            $schoolClass = $homeroomClass ?: $availableClasses->first();
        }

        if (!$schoolClass) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Belum ada data kelas yang dapat diakses.',
                'data'    => null
            ], 404);
        }

        // 2. Ambil semester & tahun ajaran aktif dari setting
        $setting = \App\Models\Setting::first();
        $academicYear = $setting->academic_year ?? '2026/2027';
        $semester = $setting->active_semester ?? 'ganjil';

        // 3. Ambil data sesi mengajar jurnal guru ini pada kelas terpilih (jika ada)
        $teacherJournalIds = \App\Models\Journal::whereHas('schedule', function ($q) use ($user, $schoolClass) {
            $q->where('teacher_id', $user->id)
              ->where('class_id', $schoolClass->id);
        })->pluck('id');
        $hasTeacherSubjectData = $teacherJournalIds->isNotEmpty();
        $allSubjectAttendances = $hasTeacherSubjectData
            ? \App\Models\Attendance::whereIn('journal_id', $teacherJournalIds)->get()->groupBy('student_id')
            : collect();

        // 4. Ambil data siswa pada kelas terpilih
        $students = \App\Models\User::where('role', 'siswa')
            ->where('class_id', $schoolClass->id)
            ->orderBy('name', 'asc')
            ->get();
        $studentRecaps = [];
        $totalSakit = 0;
        $totalIzin = 0;
        $needsAttentionCount = 0;

        foreach ($students as $student) {
            // Ambil semua izin yang sudah disetujui (approved)
            $leaves = LeaveRequest::where('student_id', $student->id)
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc')
                ->get();

            $sakitDays = 0;
            $izinDays = 0;
            $history = [];

            foreach ($leaves as $leave) {
                // Hitung jumlah hari
                $start = \Carbon\Carbon::parse($leave->start_date);
                $end   = \Carbon\Carbon::parse($leave->end_date);
                $days  = $start->diffInDays($end) + 1;

                $isSakit = stripos($leave->reason, 'sakit') !== false;
                if ($isSakit) {
                    $sakitDays += $days;
                    $totalSakit += $days;
                } else {
                    $izinDays += $days;
                    $totalIzin += $days;
                }

                $category = $isSakit ? 'Sakit' : 'Izin';
                $isPartial = $leave->leave_type === 'partial';
                if ($isPartial && $leave->start_time && $leave->end_time) {
                    $category .= ' (Potong Jam)';
                }

                $startTimeStr = $leave->start_time ? substr($leave->start_time, 0, 5) : null;
                $endTimeStr   = $leave->end_time ? substr($leave->end_time, 0, 5) : null;
                $timeDesc     = ($isPartial && $startTimeStr && $endTimeStr) 
                    ? "Izin dari jam {$startTimeStr} s/d {$endTimeStr}" 
                    : "Total {$days} Hari Izin";

                $history[] = [
                    'id'         => $leave->id,
                    'category'   => $category,
                    'leave_type' => $leave->leave_type ?? 'full_day',
                    'start_time' => $startTimeStr,
                    'end_time'   => $endTimeStr,
                    'time_desc'  => $timeDesc,
                    'days'       => $days,
                    'reason'     => $leave->reason,
                    'start_date' => $leave->start_date,
                    'end_date'   => $leave->end_date,
                    'proof_url'  => $leave->proof_file ? asset('storage/' . $leave->proof_file) : null,
                ];
            }

            $totalDays = $sakitDays + $izinDays;
            // Jika sakit/izin >= 3 hari, ditandai perlu perhatian Wali Kelas & BK
            $needsAttention = $totalDays >= 3; 
            if ($needsAttention) {
                $needsAttentionCount++;
            }

            // Susun deskripsi ringkas yang ramah tampilan mobile
            $latestLeave = count($history) > 0 ? $history[0] : null;
            $recapDescription = 'Belum ada catatan izin';
            if ($latestLeave) {
                if ($latestLeave['leave_type'] === 'partial' && $latestLeave['start_time'] && $latestLeave['end_time']) {
                    $recapDescription = "Alasan: {$latestLeave['reason']} • Izin dari jam {$latestLeave['start_time']} s/d {$latestLeave['end_time']}";
                } else {
                    $recapDescription = "Alasan: {$latestLeave['reason']} • Total {$totalDays} Hari Izin";
                }
            }

            // Kehadiran khusus di jam mata pelajaran guru ini
            $subjectAttendance = [
                'has_data'       => $hasTeacherSubjectData,
                'total_sessions' => $hasTeacherSubjectData ? $teacherJournalIds->count() : 0,
                'hadir'          => 0,
                'izin'           => 0,
                'sakit'          => 0,
                'alfa'           => 0,
            ];
            if ($hasTeacherSubjectData && isset($allSubjectAttendances[$student->id])) {
                $sAtts = $allSubjectAttendances[$student->id];
                $subjectAttendance['hadir'] = $sAtts->where('status', 'hadir')->count();
                $subjectAttendance['izin']  = $sAtts->where('status', 'izin')->count();
                $subjectAttendance['sakit'] = $sAtts->where('status', 'sakit')->count();
                $subjectAttendance['alfa']  = $sAtts->where('status', 'alfa')->count();
            }

            $studentRecaps[] = [
                'id'                 => $student->id,
                'name'               => $student->name,
                'identifier'         => $student->identifier ?? '-',
                'sakit_days'         => $sakitDays,
                'izin_days'          => $izinDays,
                'total_days'         => $totalDays,
                'needs_attention'    => $needsAttention,
                'recap_description'  => $recapDescription,
                'latest_leave'       => $latestLeave,
                'subject_attendance' => $subjectAttendance,
                'history'            => $history,
            ];
        }

        // 5. Susun Rekapitulasi Izin Siswa Per Harian (Daily Breakdown)
        $studentIds = $students->pluck('id');
        $allClassLeaves = LeaveRequest::whereIn('student_id', $studentIds)
            ->where('status', 'approved')
            ->with('student:id,name,identifier')
            ->orderBy('start_date', 'desc')
            ->get();

        $dailyMap = [];
        foreach ($allClassLeaves as $leave) {
            $cur = \Carbon\Carbon::parse($leave->start_date);
            $end = \Carbon\Carbon::parse($leave->end_date);
            $diff = $cur->diffInDays($end);
            $maxDays = min($diff, 30);

            for ($i = 0; $i <= $maxDays; $i++) {
                $checkDate = $cur->copy()->addDays($i);
                if ($checkDate->isSunday()) {
                    continue;
                }
                $dateKey = $checkDate->format('Y-m-d');
                if (!isset($dailyMap[$dateKey])) {
                    $dailyMap[$dateKey] = [
                        'date'           => $dateKey,
                        'date_formatted' => $checkDate->translatedFormat('l, d F Y'),
                        'day_name'       => $checkDate->translatedFormat('l'),
                        'total_students' => 0,
                        'total_sakit'    => 0,
                        'total_izin'     => 0,
                        'records'        => [],
                    ];
                }

                $isSakit = stripos($leave->reason, 'sakit') !== false;
                if ($isSakit) {
                    $dailyMap[$dateKey]['total_sakit']++;
                } else {
                    $dailyMap[$dateKey]['total_izin']++;
                }
                $dailyMap[$dateKey]['total_students']++;

                $startTimeStr = $leave->start_time ? substr($leave->start_time, 0, 5) : null;
                $endTimeStr   = $leave->end_time ? substr($leave->end_time, 0, 5) : null;
                $isPartial    = $leave->leave_type === 'partial';

                $dailyMap[$dateKey]['records'][] = [
                    'id'           => $leave->id,
                    'student_id'   => $leave->student_id,
                    'student_name' => $leave->student ? $leave->student->name : 'Siswa',
                    'identifier'   => $leave->student ? ($leave->student->identifier ?? '-') : '-',
                    'category'     => $isSakit ? 'Sakit' : 'Izin',
                    'leave_type'   => $leave->leave_type ?? 'full_day',
                    'start_time'   => $startTimeStr,
                    'end_time'     => $endTimeStr,
                    'start_period' => $leave->start_period,
                    'end_period'   => $leave->end_period,
                    'reason'       => $leave->reason,
                    'start_date'   => $leave->start_date,
                    'end_date'     => $leave->end_date,
                    'is_partial'   => $isPartial,
                    'proof_url'    => $leave->proof_file ? asset('storage/' . $leave->proof_file) : null,
                ];
            }
        }
        krsort($dailyMap);
        $dailyRecaps = array_values($dailyMap);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'class_id'               => $schoolClass->id,
                'class_name'             => $schoolClass->name,
                'is_bk'                  => $isBK,
                'is_wali_kelas'          => $isWaliKelas,
                'is_homeroom_of_class'   => ($homeroomClass && $homeroomClass->id === $schoolClass->id),
                'has_subject_data'       => $hasTeacherSubjectData,
                'subject_sessions_count' => $teacherJournalIds->count(),
                'all_classes'            => $availableClasses->values(),
                'semester'               => 'Semester ' . ucfirst($semester),
                'academic_year'          => $academicYear,
                'total_sakit'            => $totalSakit,
                'total_izin'             => $totalIzin,
                'needs_attention_count'  => $needsAttentionCount,
                'students'               => $studentRecaps,
                'daily_recaps'           => $dailyRecaps,
            ]
        ]);
    }
}

