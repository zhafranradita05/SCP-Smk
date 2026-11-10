<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\TeacherDashboardController;
use App\Http\Controllers\Api\StudentDashboardController;
use Illuminate\Support\Facades\Artisan; // <-- Tambahan untuk Artisan

// Rute Publik (Mobile)
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'SCP_project',
        'server_time' => now()->toIso8601String(),
    ]);
});
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [AuthController::class, 'resetPasswordWithOtp']);
// Kompatibilitas rute lama
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Rute yang butuh token (Harus login dulu)
Route::middleware('auth:sanctum')->group(function () {

    // --- API GURU (Guru Pengajar, Wali Kelas & Guru BK) ---
    Route::middleware('role:guru,wali_kelas,guru_bk')->group(function () {
        Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'getDashboard']);
        Route::post('/journals', [JournalController::class, 'store']);
        Route::get('/journals/history', [JournalController::class, 'history']);
        Route::get('/schedules/{id}/students-attendance', [JournalController::class, 'getStudentsForAttendance']);
        Route::get('/reminders', [ReminderController::class, 'index']);
        Route::post('/reminders/{id}/read', [ReminderController::class, 'markAsRead']);
    });

    // --- API SISWA & ORANG TUA (Monitoring & Dashboard Siswa) ---
    Route::middleware('role:siswa,orang_tua')->group(function () {
        Route::get('/student/dashboard', [StudentDashboardController::class, 'getDashboard']);
        Route::get('/leaves/my', [LeaveRequestController::class, 'myLeaves']);
        Route::post('/leaves', [LeaveRequestController::class, 'store']);
    });

    // --- API WALI KELAS / BK / WAKA (Verifikasi Izin & Rekap) ---
    Route::middleware('role:wali_kelas,guru_bk,waka_kurikulum')->group(function () {
        Route::get('/leaves/pending', [LeaveRequestController::class, 'indexPending']);
        Route::post('/leaves/{id}/approve', [LeaveRequestController::class, 'approve']);
        Route::post('/leaves/{id}/reject', [LeaveRequestController::class, 'reject']);
        Route::get('/leaves/recap-semester', [LeaveRequestController::class, 'recapSemester']);
        Route::get('/leaves/recap/semester', [LeaveRequestController::class, 'recapSemester']);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});

// ==========================================
// WEBHOOK OTOMASI DEPLOY & MIGRASI DATABASE
// ==========================================
Route::get('deploy-migrate-{secret}', function (Request $request, $secret) {
    $expectedSecret = config('app.deploy_secret') ?: 'skansaba_super_secret_token_12345';
    if ($secret !== $expectedSecret) {
        return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
    }
    try {
        Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = trim(Artisan::output());
        
        $seedOutput = 'Seeding dilewati.';
        if ($request->has('seed')) {
            Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = trim(Artisan::output());
        }
        
        try {
            Artisan::call('storage:link');
        } catch (\Throwable $th) {
            // Abaikan jika symlink sudah ada atau fungsi symlink dibatasi di shared hosting
        }
        Artisan::call('optimize:clear');

        $allTeachers = \App\Models\User::whereIn('role', ['guru', 'wali_kelas', 'guru_bk'])->get();
        $teachersWithSchedules = [];
        $teachersWithoutSchedules = [];
        $teachersTeachingToday = [];
        $discrepancies = [];

        $ctrl = new \App\Http\Controllers\Api\TeacherDashboardController();
        $activeCycle = \App\Models\Setting::first()->active_cycle ?? 'putaran_1';

        foreach ($allTeachers as $t) {
            $homeroom = \App\Models\SchoolClass::where('homeroom_teacher_id', $t->id)->first();
            $myScheds = \App\Models\Schedule::where('teacher_id', $t->id)->get();
            $totalCount = $myScheds->count();

            // Sesi hari Selasa pada Siklus Aktif
            $tuesdayActiveCycleScheds = $myScheds->filter(function($s) use ($activeCycle) {
                $c = (trim($s->cycle) === 'putaran_2' || trim($s->cycle) === '2') ? 'putaran_2' : 'putaran_1';
                $d = ucfirst(strtolower(trim($s->day)));
                return $d === 'Selasa' && $c === $activeCycle;
            });

            // Simulasi panggilan API Dashboard
            \Illuminate\Support\Facades\Auth::login($t);
            $dashResponse = $ctrl->getDashboard(request())->getData(true);
            $dashData = $dashResponse['data'] ?? [];

            $apiSessionsToday = $dashData['total_sessions_today'] ?? 0;
            $apiWeeklySelasa = count($dashData['weekly_schedules'][$activeCycle]['Selasa'] ?? []);
            $apiTotalSchedules = count($dashData['schedules'] ?? []);

            $info = [
                'id' => $t->id,
                'name' => $t->name,
                'username' => $t->username,
                'role' => $t->role,
                'homeroom' => $homeroom ? $homeroom->name : null,
                'total_schedules_db' => $totalCount,
                'tuesday_active_db' => $tuesdayActiveCycleScheds->count(),
                'api_sessions_today' => $apiSessionsToday,
                'api_weekly_selasa' => $apiWeeklySelasa,
                'api_total_schedules' => $apiTotalSchedules,
            ];

            if ($totalCount > 0) {
                $teachersWithSchedules[] = $info;
                if ($tuesdayActiveCycleScheds->count() > 0) {
                    $teachersTeachingToday[] = [
                        'id' => $t->id,
                        'name' => $t->name,
                        'username' => $t->username,
                        'role' => $t->role,
                        'sessions_count' => $tuesdayActiveCycleScheds->count(),
                        'subjects' => $tuesdayActiveCycleScheds->pluck('subject')->toArray(),
                    ];
                }
            } else {
                $teachersWithoutSchedules[] = [
                    'id' => $t->id,
                    'name' => $t->name,
                    'username' => $t->username,
                    'role' => $t->role,
                    'homeroom' => $homeroom ? $homeroom->name : null,
                ];
            }

            // Deteksi diskrepansi
            if ($tuesdayActiveCycleScheds->count() > 0 && $apiWeeklySelasa === 0) {
                $discrepancies[] = "Guru {$t->name} (ID: {$t->id}): Memiliki {$tuesdayActiveCycleScheds->count()} jadwal Selasa di DB tapi 0 di API!";
            }
        }

        $auditTeachersReport = [
            'total_teachers' => $allTeachers->count(),
            'teachers_with_schedules_count' => count($teachersWithSchedules),
            'teachers_without_schedules_count' => count($teachersWithoutSchedules),
            'teachers_teaching_today_count' => count($teachersTeachingToday),
            'active_cycle' => $activeCycle,
            'today_day' => 'Selasa',
            'discrepancies' => $discrepancies,
            'sample_teachers_teaching_today' => array_slice($teachersTeachingToday, 0, 15),
            'teachers_without_schedules_sample' => array_slice($teachersWithoutSchedules, 0, 10),
        ];

        $stats = [
            'total_rooms' => \App\Models\Room::count(),
            'total_classes' => \App\Models\SchoolClass::count(),
            'total_schedules' => \App\Models\Schedule::count(),
            'active_cycle' => $activeCycle,
            'audit_teachers' => $auditTeachersReport,
            'carbon_now' => [
                'day' => \Carbon\Carbon::now('Asia/Jakarta')->format('l'),
                'date' => \Carbon\Carbon::now('Asia/Jakarta')->toDateString(),
                'time' => \Carbon\Carbon::now('Asia/Jakarta')->toTimeString(),
            ]
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Migrasi dan inisialisasi database berhasil!',
            'migrate_output' => $migrateOutput,
            'seed_output' => $seedOutput,
            'stats' => $stats,
        ]);
    } catch (\Throwable $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});