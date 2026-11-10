<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        // Simpan file foto/surat ke folder public/storage/leaves
        $path = $request->file('proof_file')->store('leaves', 'public');

        $leaveType = $request->leave_type ?? 'full_day';

        $user = Auth::user();
        $targetStudentId = ($user->role === 'orang_tua' && $user->student_id) ? $user->student_id : $user->id;

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

        // Cari kelas binaan (jika ada)
        $homeroomClass = \App\Models\SchoolClass::where('homeroom_teacher_id', $user->id)->first();

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

        // Cari kelas yang dibina wali kelas ini
        $homeroomClass = \App\Models\SchoolClass::where('homeroom_teacher_id', $user->id)->first();

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
        $availableClasses = $allClasses;

        if ($isBK) {
            // Ambil daftar kelas binaan khusus Guru BK ini jika ada
            $bkClasses = \App\Models\SchoolClass::where('bk_teacher_id', $user->id)->orderBy('name')->get(['id', 'name']);
            if ($bkClasses->isNotEmpty()) {
                $availableClasses = $bkClasses;
            }

            // Guru BK dapat memilih kelas via query ?class_id=... atau default ke kelas binaan pertamanya
            $selectedClassId = $request->query('class_id');
            if ($selectedClassId) {
                $schoolClass = \App\Models\SchoolClass::find($selectedClassId);
            } else {
                $schoolClass = $availableClasses->first();
            }

            if (!$schoolClass) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Belum ada data kelas yang ditugaskan kepada Anda.',
                    'data'    => null
                ], 404);
            }
        } else {
            // Wali Kelas: Khusus kelas binaannya sendiri
            $schoolClass = \App\Models\SchoolClass::where('homeroom_teacher_id', $user->id)->first();

            if (!$schoolClass) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Anda belum ditugaskan sebagai Wali Kelas pada kelas manapun.',
                    'data'    => null
                ], 404);
            }
        }

        // 2. Ambil semester & tahun ajaran aktif dari setting
        $setting = \App\Models\Setting::first();
        $academicYear = $setting->academic_year ?? '2026/2027';
        $semester = $setting->active_semester ?? 'ganjil';

        // 3. Ambil data siswa pada kelas terpilih
        $students = \App\Models\User::where('role', 'siswa')
            ->where('class_id', $schoolClass->id)
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

            $studentRecaps[] = [
                'id'                => $student->id,
                'name'              => $student->name,
                'identifier'        => $student->identifier ?? '-',
                'sakit_days'        => $sakitDays,
                'izin_days'         => $izinDays,
                'total_days'        => $totalDays,
                'needs_attention'   => $needsAttention,
                'recap_description' => $recapDescription,
                'latest_leave'      => $latestLeave,
                'history'           => $history,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'class_id'              => $schoolClass->id,
                'class_name'            => $schoolClass->name,
                'is_bk'                 => $isBK,
                'all_classes'           => $isBK ? $availableClasses : $allClasses,
                'semester'              => 'Semester ' . ucfirst($semester),
                'academic_year'         => $academicYear,
                'total_sakit'           => $totalSakit,
                'total_izin'            => $totalIzin,
                'needs_attention_count' => $needsAttentionCount,
                'students'              => $studentRecaps,
            ]
        ]);
    }
}

