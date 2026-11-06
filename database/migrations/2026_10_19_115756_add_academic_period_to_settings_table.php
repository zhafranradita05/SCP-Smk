<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('academic_year')->default('2024/2025')->after('active_cycle');
            $table->enum('active_semester', ['ganjil', 'genap'])->default('ganjil')->after('academic_year');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['academic_year', 'active_semester']);
        });
    }
};
