<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompleteWeeklyScheduleSeeder extends Seeder
{
    /**
     * Seeder ini dinonaktifkan agar data jadwal guru dan ruangan asli yang diimpor
     * oleh Waka Kurikulum tidak terkontaminasi atau ditimpa data acak/dummy.
     */
    public function run(): void
    {
        // Dinonaktifkan: Jadwal wajib berasal dari file resmi impor Waka Kurikulum (PDF/Excel)
    }
}
