<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;

class RecapController extends Controller
{
    public function index(Request $request)
    {
        $setting = Setting::first();
        
        // Deteksi tahun ajaran dan semester otomatis sesuai tanggal sistem saat ini
        $now = Carbon::now('Asia/Jakarta');
        $currentYear = $now->year;
        $currentMonth = $now->month;
        $autoAcademicYear = ($currentMonth >= 7) 
            ? $currentYear . '/' . ($currentYear + 1)
            : ($currentYear - 1) . '/' . $currentYear;
        $autoSemester = ($currentMonth >= 7) ? 'ganjil' : 'genap';

        $defaultYear = $setting->academic_year ?? $autoAcademicYear;
        // Jika setting DB masih bernilai default lama '2024/2025' padahal sistem berjalan di tahun 2026, selaraskan otomatis
        if ($defaultYear === '2024/2025' && $currentYear >= 2026) {
            $defaultYear = $autoAcademicYear;
        }

        // 1. Ambil Filter Tahun Ajaran & Semester
        $selectedYear = $request->get('academic_year', $defaultYear);
        $selectedSemester = $request->get('semester', $setting->active_semester ?? $autoSemester);

        // 2. Tentukan Rentang Tanggal Semester
        // Contoh '2024/2025' -> $startYear = 2024, $endYear = 2025
        $years = explode('/', $selectedYear);
        $startYear = isset($years[0]) ? (int)$years[0] : (int)date('Y');
        $endYear = isset($years[1]) ? (int)$years[1] : $startYear + 1;

        if ($selectedSemester === 'ganjil') {
            $startDate = Carbon::create($startYear, 7, 1)->startOfDay();
            $endDate = Carbon::create($startYear, 12, 31)->endOfDay();
        } else {
            $startDate = Carbon::create($endYear, 1, 1)->startOfDay();
            $endDate = Carbon::create($endYear, 6, 30)->endOfDay();
        }

        // 3. Rekap Jurnal Mengajar Guru pada Semester Ini
        $journals = Journal::with(['teacher', 'schedule.schoolClass', 'schedule.room'])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'desc')
            ->get();

        // Rekapitulasi per Guru (Total Jurnal yang terisi) - mencakup guru biasa & wali kelas yang mengajar
        $teachers = User::whereIn('role', ['guru', 'wali_kelas'])->orderBy('name', 'asc')->get();
        $teacherRecap = $teachers->map(function ($guru) use ($journals) {
            $count = $journals->where('teacher_id', $guru->id)->count();
            return [
                'name' => $guru->name,
                'nip' => $guru->identifier ?? '-',
                'total_journals' => $count,
            ];
        });

        // 4. Rekap Izin Siswa pada Semester Ini
        $leavesQuery = LeaveRequest::with(['student'])
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()]);

        $leaves = $leavesQuery->orderBy('start_date', 'desc')->get();
        $totalIzin = $leaves->where('status', 'approved')->count();
        $totalPending = $leaves->where('status', 'pending')->count();

        // 5. Rekapitulasi Daftar Hadir Siswa (Tab 1: Rekapan Daftar Hadir Siswa)
        $selectedClassId = $request->get('class_id', 'all');
        $allClasses = SchoolClass::orderBy('name')->get();

        $studentsQuery = User::where('role', 'siswa')
            ->with(['schoolClass']);

        if ($selectedClassId && $selectedClassId !== 'all') {
            $studentsQuery->where('class_id', $selectedClassId);
        }

        $allStudents = $studentsQuery->orderBy('name', 'asc')->get();

        // Ambil ID semua jurnal dalam periode semester yang dipilih
        $journalIds = $journals->pluck('id');

        // Ambil semua data kehadiran siswa dari jurnal-jurnal tersebut
        $attendanceRecords = Attendance::whereIn('journal_id', $journalIds)
            ->get(['id', 'journal_id', 'student_id', 'status']);

        // Kelompokkan kehadiran per student_id
        $attendancesByStudent = $attendanceRecords->groupBy('student_id');

        // Hitung akumulasi kehadiran per siswa
        $studentAttendanceRecap = $allStudents->map(function ($student) use ($attendancesByStudent) {
            $records = $attendancesByStudent->get($student->id, collect());
            $hadir = $records->where('status', 'hadir')->count();
            $sakit = $records->where('status', 'sakit')->count();
            $izin = $records->where('status', 'izin')->count();
            $alpa = $records->where('status', 'alfa')->count();
            $totalPertemuan = $records->count();
            $persentase = $totalPertemuan > 0 ? round(($hadir / $totalPertemuan) * 100, 1) : 0;

            return [
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->identifier ?? '-',
                'class_name' => $student->schoolClass->name ?? '-',
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total_pertemuan' => $totalPertemuan,
                'persentase' => $persentase,
            ];
        });

        // Rekapitulasi per Kelas untuk Daftar Hadir
        $attendanceByClass = $allClasses->map(function ($cls) use ($allStudents, $attendancesByStudent) {
            $classStudents = $allStudents->where('class_id', $cls->id);
            $studentIds = $classStudents->pluck('id');

            $clsRecords = collect();
            foreach ($studentIds as $sid) {
                $clsRecords = $clsRecords->merge($attendancesByStudent->get($sid, collect()));
            }

            $hadir = $clsRecords->where('status', 'hadir')->count();
            $sakit = $clsRecords->where('status', 'sakit')->count();
            $izin = $clsRecords->where('status', 'izin')->count();
            $alpa = $clsRecords->where('status', 'alfa')->count();
            $total = $clsRecords->count();
            $pct = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

            return [
                'id' => $cls->id,
                'name' => $cls->name,
                'total_students' => $classStudents->count(),
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total_pertemuan' => $total,
                'persentase' => $pct,
            ];
        });

        // Total metrik keseluruhan kehadiran siswa
        $totalHadirAll = $attendanceRecords->where('status', 'hadir')->count();
        $totalSakitAll = $attendanceRecords->where('status', 'sakit')->count();
        $totalIzinAll = $attendanceRecords->where('status', 'izin')->count();
        $totalAlpaAll = $attendanceRecords->where('status', 'alfa')->count();
        $totalSemuaKehadiran = $attendanceRecords->count();
        $avgPersentaseHadir = $totalSemuaKehadiran > 0 
            ? round(($totalHadirAll / $totalSemuaKehadiran) * 100, 1) 
            : 0;

        return view('waka.recap.index', compact(
            'selectedYear',
            'selectedSemester',
            'startDate',
            'endDate',
            'journals',
            'teacherRecap',
            'leaves',
            'totalIzin',
            'totalPending',
            'allClasses',
            'selectedClassId',
            'studentAttendanceRecap',
            'attendanceByClass',
            'totalHadirAll',
            'totalSakitAll',
            'totalIzinAll',
            'totalAlpaAll',
            'totalSemuaKehadiran',
            'avgPersentaseHadir'
        ));
    }

    // Tampilan Siap Cetak (Print Format)
    public function print(Request $request)
    {
        $setting = Setting::first();
        $now = Carbon::now('Asia/Jakarta');
        $autoAcademicYear = ($now->month >= 7) ? $now->year . '/' . ($now->year + 1) : ($now->year - 1) . '/' . $now->year;
        $defaultYear = $setting->academic_year ?? $autoAcademicYear;
        if ($defaultYear === '2024/2025' && $now->year >= 2026) {
            $defaultYear = $autoAcademicYear;
        }

        $selectedYear = $request->get('academic_year', $defaultYear);
        $selectedSemester = $request->get('semester', $setting->active_semester ?? (($now->month >= 7) ? 'ganjil' : 'genap'));

        $years = explode('/', $selectedYear);
        $startYear = isset($years[0]) ? (int)$years[0] : (int)date('Y');
        $endYear = isset($years[1]) ? (int)$years[1] : $startYear + 1;

        if ($selectedSemester === 'ganjil') {
            $startDate = Carbon::create($startYear, 7, 1)->startOfDay();
            $endDate = Carbon::create($startYear, 12, 31)->endOfDay();
        } else {
            $startDate = Carbon::create($endYear, 1, 1)->startOfDay();
            $endDate = Carbon::create($endYear, 6, 30)->endOfDay();
        }

        $journals = Journal::with(['teacher', 'schedule.schoolClass'])
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date', 'asc')
            ->get();

        $leaves = LeaveRequest::with('student')
            ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', 'approved')
            ->get();

        return view('waka.recap.print', compact(
            'selectedYear',
            'selectedSemester',
            'startDate',
            'endDate',
            'journals',
            'leaves'
        ));
    }

    /**
     * Tampilan Siap Cetak Rekapitulasi Daftar Hadir Siswa
     */
    public function printAttendanceRecap(Request $request)
    {
        $setting = Setting::first();
        $now = Carbon::now('Asia/Jakarta');
        $autoAcademicYear = ($now->month >= 7) ? $now->year . '/' . ($now->year + 1) : ($now->year - 1) . '/' . $now->year;
        $defaultYear = $setting->academic_year ?? $autoAcademicYear;
        if ($defaultYear === '2024/2025' && $now->year >= 2026) {
            $defaultYear = $autoAcademicYear;
        }

        $selectedYear = $request->get('academic_year', $defaultYear);
        $selectedSemester = $request->get('semester', $setting->active_semester ?? (($now->month >= 7) ? 'ganjil' : 'genap'));

        $years = explode('/', $selectedYear);
        $startYear = isset($years[0]) ? (int)$years[0] : (int)date('Y');
        $endYear = isset($years[1]) ? (int)$years[1] : $startYear + 1;

        if ($selectedSemester === 'ganjil') {
            $startDate = Carbon::create($startYear, 7, 1)->startOfDay();
            $endDate = Carbon::create($startYear, 12, 31)->endOfDay();
        } else {
            $startDate = Carbon::create($endYear, 1, 1)->startOfDay();
            $endDate = Carbon::create($endYear, 6, 30)->endOfDay();
        }

        $selectedClassId = $request->get('class_id', 'all');
        $allClasses = SchoolClass::orderBy('name')->get();
        $selectedClass = ($selectedClassId && $selectedClassId !== 'all') ? SchoolClass::find($selectedClassId) : null;

        $journals = Journal::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->get();
        $journalIds = $journals->pluck('id');

        $studentsQuery = User::where('role', 'siswa')->with('schoolClass');
        if ($selectedClassId && $selectedClassId !== 'all') {
            $studentsQuery->where('class_id', $selectedClassId);
        }
        $students = $studentsQuery->orderBy('name', 'asc')->get();

        $attendanceRecords = Attendance::whereIn('journal_id', $journalIds)->get();
        $attendancesByStudent = $attendanceRecords->groupBy('student_id');

        $studentAttendanceRecap = $students->map(function ($student) use ($attendancesByStudent) {
            $records = $attendancesByStudent->get($student->id, collect());
            $hadir = $records->where('status', 'hadir')->count();
            $sakit = $records->where('status', 'sakit')->count();
            $izin = $records->where('status', 'izin')->count();
            $alpa = $records->where('status', 'alfa')->count();
            $totalPertemuan = $records->count();
            $persentase = $totalPertemuan > 0 ? round(($hadir / $totalPertemuan) * 100, 1) : 0;

            return [
                'id' => $student->id,
                'name' => $student->name,
                'nisn' => $student->identifier ?? '-',
                'class_name' => $student->schoolClass->name ?? '-',
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total_pertemuan' => $totalPertemuan,
                'persentase' => $persentase,
            ];
        });

        return view('waka.recap.print_attendance', compact(
            'selectedYear',
            'selectedSemester',
            'startDate',
            'endDate',
            'selectedClass',
            'studentAttendanceRecap'
        ));
    }

    /**
     * Cetak Laporan Rekapitulasi Izin Siswa
     * - Wali Kelas: HANYA bisa mencetak kelas binaannya sendiri
     * - Guru BK: Bisa mencetak semua kelas, dipisahkan per kelas (page-break) atau per kelas yang dipilih
     */
    public function printLeaveRecap(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Dukungan otentikasi via token Sanctum (untuk akses langsung dari mobile app)
        if (!$user && $request->has('token')) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($tokenModel) {
                $user = $tokenModel->tokenable;
            }
        }

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu untuk mengakses laporan ini.');
        }

        $setting = Setting::first();
        $academicYear = $setting->academic_year ?? '2026/2027';
        $semester = ucfirst($setting->active_semester ?? 'ganjil');

        $isWali = $user->role === 'wali_kelas';
        $isBK = $user->role === 'guru_bk';
        $isWaka = $user->role === 'waka_kurikulum';

        $allClasses = SchoolClass::with('homeroomTeacher')->orderBy('name')->get();
        $targetClasses = collect();

        if ($isWali) {
            $homeroomClass = SchoolClass::with('homeroomTeacher')->where('homeroom_teacher_id', $user->id)->first();
            if (!$homeroomClass) {
                abort(403, 'Anda belum ditugaskan sebagai Wali Kelas pada kelas manapun.');
            }
            // Wali kelas HANYA boleh mencetak kelas binaannya sendiri!
            $targetClasses = collect([$homeroomClass]);
        } else if ($isBK || $isWaka) {
            $selectedClassId = $request->query('class_id');
            if ($selectedClassId && $selectedClassId !== 'all') {
                $chosen = SchoolClass::with('homeroomTeacher')->find($selectedClassId);
                if ($chosen) {
                    $targetClasses = collect([$chosen]);
                } else {
                    $targetClasses = $allClasses;
                }
            } else {
                // Cetak semua kelas dibedakan per kelasnya
                $targetClasses = $allClasses;
            }
        } else {
            abort(403, 'Akses ditolak. Fitur ini hanya untuk Wali Kelas dan Guru BK.');
        }

        // Susun data per kelas
        $classReports = [];

        foreach ($targetClasses as $class) {
            $students = User::where('role', 'siswa')
                ->where('class_id', $class->id)
                ->orderBy('name')
                ->get();

            $studentRows = [];
            $totalSakit = 0;
            $totalIzin = 0;
            $needsAttentionCount = 0;

            foreach ($students as $student) {
                $leaves = LeaveRequest::where('student_id', $student->id)
                    ->where('status', 'approved')
                    ->orderBy('start_date', 'desc')
                    ->get();

                $sakitDays = 0;
                $izinDays = 0;
                $historyNotes = [];

                foreach ($leaves as $leave) {
                    $start = Carbon::parse($leave->start_date);
                    $end = Carbon::parse($leave->end_date);
                    $days = $start->diffInDays($end) + 1;

                    $isSakit = stripos($leave->reason, 'sakit') !== false;
                    if ($isSakit) {
                        $sakitDays += $days;
                        $totalSakit += $days;
                    } else {
                        $izinDays += $days;
                        $totalIzin += $days;
                    }

                    $isPartial = $leave->leave_type === 'partial';
                    $note = $isSakit ? 'Sakit' : 'Izin';
                    if ($isPartial && $leave->start_time && $leave->end_time) {
                        $note .= ' (' . substr($leave->start_time, 0, 5) . '-' . substr($leave->end_time, 0, 5) . ')';
                    } else {
                        $note .= " ({$days} hr)";
                    }
                    $note .= ": {$leave->reason}";
                    $historyNotes[] = $note;
                }

                $totalDays = $sakitDays + $izinDays;
                $needsAttention = $totalDays >= 3;
                if ($needsAttention) {
                    $needsAttentionCount++;
                }

                $studentRows[] = [
                    'identifier'      => $student->identifier ?? '-',
                    'name'            => $student->name,
                    'sakit_days'      => $sakitDays,
                    'izin_days'       => $izinDays,
                    'total_days'      => $totalDays,
                    'needs_attention' => $needsAttention,
                    'notes'           => count($historyNotes) > 0 ? implode('; ', array_slice($historyNotes, 0, 2)) : '-',
                ];
            }

            $homeroomTeacher = $class->homeroomTeacher;

            $classReports[] = [
                'class'                 => $class,
                'homeroom_teacher'      => $homeroomTeacher,
                'students'              => $studentRows,
                'total_students'        => count($studentRows),
                'total_sakit'           => $totalSakit,
                'total_izin'            => $totalIzin,
                'needs_attention_count' => $needsAttentionCount,
            ];
        }

        return view('reports.leave_recap_print', compact(
            'user',
            'isWali',
            'isBK',
            'isWaka',
            'academicYear',
            'semester',
            'classReports',
            'allClasses'
        ));
    }
}
