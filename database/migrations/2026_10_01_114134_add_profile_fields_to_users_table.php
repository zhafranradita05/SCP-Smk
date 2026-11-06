<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan username
            $table->string('username')->unique()->after('name');
            
            $table->enum('role', [
                'siswa',
                'guru',
                'wali_kelas',
                'guru_bk',
                'waka_kurikulum',
            ])->default('siswa')->after('email');

            $table->string('identifier')->nullable()->after('role');
            $table->string('phone')->nullable();
            $table->string('photo')->nullable();
        });
    }


public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn(['role', 'identifier', 'phone', 'photo']);
    });
}
};
