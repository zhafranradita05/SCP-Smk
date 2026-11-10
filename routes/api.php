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
        Route::match(['get', 'put'], '/teacher/dashboard', [TeacherDashboardController::class, 'getDashboard']);
        Route::match(['get', 'put'], '/teacher/weekly-schedule', [TeacherDashboardController::class, 'getWeeklySchedule']);
        Route::match(['get', 'put'], '/teacher/schedules', [TeacherDashboardController::class, 'getWeeklySchedule']);
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
        
        $seedOutput = 'Seeding otomatis jadwal mingguan lengkap dijalankan.';
        try {
            Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\CompleteWeeklyScheduleSeeder', '--force' => true]);
            $seedOutput = trim(Artisan::output());
        } catch (\Throwable $se) {
            $seedOutput = 'Seeder error: ' . $se->getMessage();
        }
        
        try {
            Artisan::call('storage:link');
        } catch (\Throwable $th) {
            // Abaikan jika symlink sudah ada atau fungsi symlink dibatasi di shared hosting
        }
        Artisan::call('optimize:clear');

        $rosyidaUsers = \App\Models\User::where('name', 'like', '%ROSYIDA%')
            ->orWhere('identifier', 'like', '%199203252014022002%')
            ->orWhere('username', 'like', '%rosyida%')
            ->get();

        $debugRosyida = [];
        foreach ($rosyidaUsers as $u) {
            $homeroom = \App\Models\SchoolClass::where('homeroom_teacher_id', $u->id)->first();
            $scheds = \App\Models\Schedule::where('teacher_id', $u->id)->get();
            $homeroomScheds = $homeroom ? \App\Models\Schedule::where('class_id', $homeroom->id)->get() : [];
            
            \Illuminate\Support\Facades\Auth::login($u);
            $ctrl = new \App\Http\Controllers\Api\TeacherDashboardController();
            $dashResponse = $ctrl->getDashboard(request())->getData(true);

            $debugRosyida[] = [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'identifier' => $u->identifier,
                'role' => $u->role,
                'homeroom' => $homeroom ? ['id' => $homeroom->id, 'name' => $homeroom->name] : null,
                'my_schedules_count' => $scheds->count(),
                'my_schedules_summary' => $scheds->map(fn($s) => "ID:{$s->id}|{$s->day}|{$s->cycle}|{$s->subject}"),
                'dashboard_schedules_today' => $dashResponse['data']['schedules_today'] ?? [],
                'dashboard_weekly_selasa_p1' => $dashResponse['data']['weekly_schedules']['putaran_1']['Selasa'] ?? [],
            ];
        }

        $token = $rosyidaUsers->first() ? $rosyidaUsers->first()->createToken('test-audit')->plainTextToken : null;
        $testBearerResponse = null;
        if ($token) {
            $req = \Illuminate\Http\Request::create('/api/teacher/dashboard', 'GET');
            $req->headers->set('Authorization', 'Bearer ' . $token);
            $req->headers->set('Accept', 'application/json');
            $testBearerResponse = app()->handle($req)->getContent();
            $testBearerResponse = json_decode($testBearerResponse, true);
        }

        $stats = [
            'total_rooms' => \App\Models\Room::count(),
            'total_classes' => \App\Models\SchoolClass::count(),
            'total_schedules' => \App\Models\Schedule::count(),
            'total_teachers' => \App\Models\User::whereIn('role', ['guru', 'wali_kelas', 'guru_bk'])->count(),
            'active_cycle' => \App\Models\Setting::first()->active_cycle ?? null,
            'rosyida_tokens_count' => \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', 23)->count(),
            'test_bearer_status' => $testBearerResponse['status'] ?? null,
            'test_bearer_sessions_today' => $testBearerResponse['data']['total_sessions_today'] ?? null,
            'test_bearer_schedules_today_count' => isset($testBearerResponse['data']['schedules_today']) ? count($testBearerResponse['data']['schedules_today']) : null,
            'test_bearer_weekly_selasa_p1_count' => isset($testBearerResponse['data']['weekly_schedules']['putaran_1']['Selasa']) ? count($testBearerResponse['data']['weekly_schedules']['putaran_1']['Selasa']) : null,
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