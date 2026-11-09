<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\User;

return new class extends Migration
{
    /**
     * Clean up dummy 'X I' class generated from Roman numeral 'XI' parsing.
     */
    public function up(): void
    {
        // Cari kelas dummy X I atau ID 109
        $dummyClasses = SchoolClass::where('name', 'X I')
            ->orWhere('name', 'XI')
            ->orWhere('id', 109)
            ->get();

        foreach ($dummyClasses as $cls) {
            // Hapus jadwal terkait kelas dummy ini jika ada
            Schedule::where('class_id', $cls->id)->delete();

            // Lepaskan siswa jika ada yang terhubung ke kelas dummy ini
            User::where('class_id', $cls->id)->update(['class_id' => null]);

            // Hapus kelas dummy
            $cls->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible
    }
};

