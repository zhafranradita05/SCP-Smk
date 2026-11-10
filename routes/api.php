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

        $stats = [
            'total_rooms' => \App\Models\Room::count(),
            'total_classes' => \App\Models\SchoolClass::count(),
            'has_xi_class' => \App\Models\SchoolClass::where('name', 'X I')->exists(),
            'total_schedules' => \App\Models\Schedule::count(),
            'distinct_schedule_rooms' => \App\Models\Schedule::distinct('room_id')->count('room_id'),
            'total_wali_kelas_users' => \App\Models\User::where('role', 'wali_kelas')->count(),
            'rosyida_role' => \App\Models\User::where('username', 'rosyida')->value('role'),
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