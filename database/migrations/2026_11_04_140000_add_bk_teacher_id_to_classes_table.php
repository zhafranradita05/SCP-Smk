<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'bk_teacher_id')) {
                $table->foreignId('bk_teacher_id')
                      ->nullable()
                      ->after('homeroom_teacher_id')
                      ->constrained('users')
                      ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'bk_teacher_id')) {
                $table->dropForeign(['bk_teacher_id']);
                $table->dropColumn('bk_teacher_id');
            }
        });
    }
};

