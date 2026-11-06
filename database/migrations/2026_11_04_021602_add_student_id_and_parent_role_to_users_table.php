<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perbarui enum role untuk menyertakan role 'orang_tua'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('siswa', 'guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum', 'orang_tua') NOT NULL DEFAULT 'siswa'");

        // 2. Tambahkan kolom student_id untuk merelasikan akun orang tua ke siswa
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('class_id');
                $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'student_id')) {
                $table->dropForeign(['student_id']);
                $table->dropColumn('student_id');
            }
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('siswa', 'guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum') NOT NULL DEFAULT 'siswa'");
    }
};
