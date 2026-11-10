<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sinkronisasi role seluruh guru yang ditugaskan sebagai Wali Kelas atau Guru BK
     * agar mendapatkan hak akses dan menu verifikasi izin di aplikasi mobile & web.
     */
    public function up(): void
    {
        // 1. Sinkronkan semua guru yang ditugaskan di classes.homeroom_teacher_id agar memiliki role 'wali_kelas'
        $homeroomTeacherIds = DB::table('classes')
            ->whereNotNull('homeroom_teacher_id')
            ->pluck('homeroom_teacher_id')
            ->unique()
            ->toArray();

        if (!empty($homeroomTeacherIds)) {
            DB::table('users')
                ->whereIn('id', $homeroomTeacherIds)
                ->where('role', 'guru')
                ->update(['role' => 'wali_kelas']);
        }

        // 2. Sinkronkan semua guru yang ditugaskan di classes.bk_teacher_id agar memiliki role 'guru_bk'
        $bkTeacherIds = DB::table('classes')
            ->whereNotNull('bk_teacher_id')
            ->pluck('bk_teacher_id')
            ->unique()
            ->toArray();

        if (!empty($bkTeacherIds)) {
            DB::table('users')
                ->whereIn('id', $bkTeacherIds)
                ->where('role', 'guru')
                ->update(['role' => 'guru_bk']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: Data sinkronisasi role tidak perlu dibatalkan
    }
};
