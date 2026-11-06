<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bersihkan prefix nomor/ID di kolom 'name' pada tabel users
        $users = DB::table('users')->select('id', 'name')->get();

        foreach ($users as $user) {
            $cleanName = trim(preg_replace('/^[0-9]+[\.\s]+/', '', $user->name));
            if ($cleanName !== $user->name) {
                DB::table('users')->where('id', $user->id)->update([
                    'name' => $cleanName
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
