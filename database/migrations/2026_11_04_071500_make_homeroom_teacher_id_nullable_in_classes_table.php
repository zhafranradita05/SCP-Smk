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
        // 1. Drop foreign key lama
        DB::statement('ALTER TABLE `classes` DROP FOREIGN KEY `classes_homeroom_teacher_id_foreign`');

        // 2. Ubah kolom homeroom_teacher_id agar nullable
        DB::statement('ALTER TABLE `classes` MODIFY `homeroom_teacher_id` BIGINT UNSIGNED NULL DEFAULT NULL');

        // 3. Pasang foreign key baru dengan ON DELETE SET NULL
        DB::statement('ALTER TABLE `classes` ADD CONSTRAINT `classes_homeroom_teacher_id_foreign` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `classes` DROP FOREIGN KEY `classes_homeroom_teacher_id_foreign`');
        DB::statement('ALTER TABLE `classes` MODIFY `homeroom_teacher_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `classes` ADD CONSTRAINT `classes_homeroom_teacher_id_foreign` FOREIGN KEY (`homeroom_teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE');
    }
};

