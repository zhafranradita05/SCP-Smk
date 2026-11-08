<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->enum('cycle_mode', ['auto', 'manual'])->default('auto')->after('active_cycle');
            $table->date('cycle_start_date')->nullable()->after('cycle_mode');
            $table->enum('cycle_start_cycle', ['putaran_1', 'putaran_2'])->default('putaran_1')->after('cycle_start_date');
            $table->unsignedTinyInteger('cycle_interval_weeks')->default(2)->after('cycle_start_cycle');
        });

        // Set default acuan tanggal awal: Senin terdekat
        $defaultMonday = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->toDateString();
        DB::table('settings')->update([
            'cycle_mode'           => 'auto',
            'cycle_start_date'     => $defaultMonday,
            'cycle_start_cycle'    => 'putaran_1',
            'cycle_interval_weeks' => 2,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'cycle_mode',
                'cycle_start_date',
                'cycle_start_cycle',
                'cycle_interval_weeks'
            ]);
        });
    }
};
