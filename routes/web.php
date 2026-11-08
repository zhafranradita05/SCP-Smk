<?php

use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\ReminderController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\MasterDataController;
use App\Http\Controllers\Web\ForgotPasswordController;
use App\Http\Controllers\Web\PromotionController;

// 1. Rute Publik (Landing Page, Login & Unduh Aplikasi)
Route::get('/', function () {
    return view('welcome');
})->name('landing');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Rute Unduh APK Mobile SCP (Android)
Route::get('/download/app', function () {
    // 1. Cek jika Waka mengonfigurasi URL download eksternal (misal: Google Drive / Cloud Storage)
    $setting = \App\Models\Setting::first();
    if ($setting && !empty($setting->apk_download_url)) {
        return redirect()->away($setting->apk_download_url);
    }

    // 2. Cek file APK fisik di server hosting (public/downloads/)
    $apkPath = public_path('downloads/SCP-Presensi.apk');
    if (file_exists($apkPath)) {
        return response()->download($apkPath, 'SCP-Presensi-Mobile.apk', [
            'Content-Type' => 'application/vnd.android.package-archive',
        ]);
    }

    // 3. Fallback jika testing di localhost / dev environment
    $localReleaseArm64 = base_path('../scp_mobile/build/app/outputs/flutter-apk/app-arm64-v8a-release.apk');
    if (file_exists($localReleaseArm64)) {
        return response()->download($localReleaseArm64, 'SCP-Presensi-Mobile.apk');
    }

    $localRelease = base_path('../scp_mobile/build/app/outputs/flutter-apk/app-release.apk');
    if (file_exists($localRelease)) {
        return response()->download($localRelease, 'SCP-Presensi-Mobile.apk');
    }

    $localDebug = base_path('../scp_mobile/build/app/outputs/flutter-apk/app-debug.apk');
    if (file_exists($localDebug)) {
        return response()->download($localDebug, 'SCP-Presensi-Mobile.apk');
    }

    return redirect('/#download-app');
})->name('app.download');

// Lupa Password (Belum Login)
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])->name('password.send_otp');
Route::post('/forgot-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');

// 2. Rute Cetak Laporan Rekapitulasi Izin Siswa (Wali Kelas & Guru BK: mendukung Web Session & Token Mobile)
Route::get('/leaves/recap/print', [\App\Http\Controllers\Web\RecapController::class, 'printLeaveRecap'])->name('leaves.recap.print');

// 2b. Rute Halaman Jadwal Mengajar Guru & Status Jurnal (Guru, Wali Kelas & Guru BK)
Route::middleware(['auth', 'role:guru,wali_kelas,guru_bk'])->group(function () {
    Route::get('/teacher/schedule', [\App\Http\Controllers\Web\TeacherScheduleController::class, 'index'])->name('teacher.schedule');
    Route::get('/guru/jadwal', fn() => redirect()->route('teacher.schedule'));
});

// 3. Rute Terotentikasi Umum (Semua Pengguna yang Berhasil Login Web)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// 4. RUTE KHUSUS WAKA KURIKULUM (DIPROTEKSI KETAT: Siswa & Role Lain Dilarang Akses, Timeout 10 Menit Tidak Aktif)
Route::middleware(['auth', 'role:waka_kurikulum', 'active.session'])->group(function () {

    // Keepalive Sesi Aktif Waka (Deteksi Aktivitas Interaktif Pengguna)
    Route::post('/waka/session-keepalive', function () {
        session(['waka_last_activity_time' => time()]);
        return response()->json([
            'status' => 'ok',
            'expires_in' => 600,
            'time' => time(),
        ]);
    })->name('waka.session.keepalive');

    // Dashboard & Rekapan Jurnal Hari Ini
    Route::get('/waka/dashboard', function () {
        $now = \Carbon\Carbon::now('Asia/Jakarta');
        $today = $now->toDateString();
        $todayFormatted = $now->format('d M Y');

        $daysMap = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
        ];
        $currentDay = $daysMap[$now->format('l')] ?? $now->format('l');

        $setting = \App\Models\Setting::first();
        if (!$setting) {
            $setting = \App\Models\Setting::create(['active_cycle' => 'putaran_1', 'cycle_mode' => 'auto']);
        }
        $rawCycle = $setting->active_cycle ?? 'putaran_1';
        $activeCycle = ($rawCycle === 'putaran_2') ? 'Putaran 2' : 'Putaran 1';
        $rotationInfo = $setting->getNextRotationDetails();

        $totalGuru = \App\Models\User::whereIn('role', ['guru', 'wali_kelas'])->count();
        $totalKelas = \App\Models\SchoolClass::count();

        $schedules = \App\Models\Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('day', $currentDay)
            ->where('cycle', $rawCycle)
            ->orderBy('start_time', 'asc')
            ->get();

        $sudahIsiCount = 0;
        $belumIsiCount = 0;

        foreach ($schedules as $sched) {
            $journal = \App\Models\Journal::where('schedule_id', $sched->id)
                ->where('date', $today)
                ->first();

            $sched->journal = $journal;
            $sched->is_filled = !is_null($journal);

            if ($sched->is_filled) {
                $sudahIsiCount++;
            } else {
                $belumIsiCount++;
            }
        }

        return view('waka.dashboard', compact(
            'totalGuru',
            'totalKelas',
            'setting',
            'rotationInfo',
            'rawCycle',
            'activeCycle',
            'currentDay',
            'todayFormatted',
            'schedules',
            'sudahIsiCount',
            'belumIsiCount'
        ));
    });

    // Rekapitulasi Semester & Cetak
    Route::get('/waka/recap', [\App\Http\Controllers\Web\RecapController::class, 'index'])->name('waka.recap.index');
    Route::get('/waka/recap/print', [\App\Http\Controllers\Web\RecapController::class, 'print'])->name('waka.recap.print');

    // Profil & Edit Akun Waka Kurikulum
    Route::get('/waka/profile', [UserController::class, 'editProfile'])->name('waka.profile.edit');
    Route::put('/waka/profile', [UserController::class, 'updateProfile'])->name('waka.profile.update');

    // Import & Template Data Siswa (Excel/CSV)
    Route::get('/waka/users/template/siswa', [UserController::class, 'downloadTemplate'])->name('waka.users.template');
    Route::post('/waka/users/import/siswa', [UserController::class, 'importStudents'])->name('waka.users.import');

    // Import & Template Data Guru Pegawai (Multi-File: Excel, CSV, TSV, PDF, TXT, JSON)
    Route::get('/waka/users/template/guru', [UserController::class, 'downloadTeacherTemplate'])->name('waka.users.template.guru');
    Route::post('/waka/users/import/guru', [UserController::class, 'importTeachers'])->name('waka.users.import.guru');

    // Kenaikan Kelas & Kelulusan Siswa
    Route::get('/waka/promotions', [PromotionController::class, 'index'])->name('waka.promotions.index');
    Route::get('/waka/promotions/export-alumni', [PromotionController::class, 'exportAlumni'])->name('waka.promotions.export_alumni');
    Route::post('/waka/promotions/process', [PromotionController::class, 'process'])->name('waka.promotions.process');
    Route::post('/waka/promotions/bulk', [PromotionController::class, 'bulkProcess'])->name('waka.promotions.bulk');
    Route::post('/waka/promotions/{id}/restore', [PromotionController::class, 'restoreAlumni'])->name('waka.promotions.restore');

    // Kelola Data Pengguna (CRUD)
    Route::resource('/waka/users', UserController::class)->names([
        'index'   => 'waka.users.index',
        'create'  => 'waka.users.create',
        'store'   => 'waka.users.store',
        'edit'    => 'waka.users.edit',
        'update'  => 'waka.users.update',
        'destroy' => 'waka.users.destroy',
    ]);

    // Jadwal Pelajaran (Import Excel, CRUD, Setting Putaran)
    Route::get('/waka/schedules/template', [\App\Http\Controllers\Web\ScheduleController::class, 'downloadTemplate'])->name('waka.schedules.template');
    Route::post('/waka/schedules/import', [\App\Http\Controllers\Web\ScheduleController::class, 'importSchedules'])->name('waka.schedules.import');
    Route::get('/waka/schedules', [\App\Http\Controllers\Web\ScheduleController::class, 'index'])->name('waka.schedules.index');
    Route::post('/waka/schedules', [\App\Http\Controllers\Web\ScheduleController::class, 'store'])->name('waka.schedules.store');
    Route::delete('/waka/schedules/{id}', [\App\Http\Controllers\Web\ScheduleController::class, 'destroy'])->name('waka.schedules.destroy');

    // Pengaturan Putaran & Lock Schedule
    Route::post('/waka/settings', [SettingController::class, 'update'])->name('waka.settings.update');
    Route::get('/waka/settings', fn() => redirect('/waka/schedules'));

    // Master Data (Ruangan & Kelas & Multi-File Import)
    Route::get('/waka/master', [MasterDataController::class, 'index'])->name('master.index');
    Route::get('/waka/master/template', [MasterDataController::class, 'downloadTemplate'])->name('master.template');
    Route::post('/waka/master/import', [MasterDataController::class, 'importMaster'])->name('master.import');
    Route::post('/waka/master/room', [MasterDataController::class, 'storeRoom'])->name('room.store');
    Route::post('/waka/master/class', [MasterDataController::class, 'storeClass'])->name('class.store');
    Route::put('/waka/master/class/{class}', [MasterDataController::class, 'updateClass'])->name('class.update');

    // Notifikasi / Reminder Waka ke Guru
    Route::post('/waka/reminders/send', [ReminderController::class, 'store'])->name('reminders.store');
});
