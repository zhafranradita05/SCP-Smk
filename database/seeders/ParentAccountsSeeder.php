<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ParentAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Membuat akun Orang Tua untuk seluruh siswa terdaftar
     * Format Login:
     * - Username: 'P' + NIS (contoh: P19413)
     * - Identifier: 'P' + NIS
     * - Email: 'p' + NIS + '@gmail.com'
     * - Password default: password123
     * - Role: orang_tua
     * - student_id: ID siswa (berelasi langsung)
     */
    public function run(): void
    {
        $students = User::where('role', 'siswa')->get();
        $this->command->info("Menemukan {$students->count()} data siswa untuk sinkronisasi akun Orang Tua...");

        $defaultPasswordHash = Hash::make('password123');
        $now = now();
        $batch = [];
        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($students as $student) {
            $nis = trim($student->identifier ?: $student->username);
            $parentUsername = 'P' . $nis;
            $parentIdentifier = 'P' . $nis;
            $parentEmail = 'p' . strtolower($nis) . '@gmail.com';
            $parentName = 'Orang Tua ' . $student->name;

            $existing = User::where('role', 'orang_tua')
                ->where(function ($q) use ($student, $parentUsername) {
                    $q->where('student_id', $student->id)
                      ->orWhere('username', $parentUsername);
                })->first();

            if (!$existing) {
                $batch[] = [
                    'name'       => $parentName,
                    'username'   => $parentUsername,
                    'identifier' => $parentIdentifier,
                    'email'      => $parentEmail,
                    'password'   => $defaultPasswordHash,
                    'role'       => 'orang_tua',
                    'student_id' => $student->id,
                    'class_id'   => $student->class_id,
                    'phone'      => $student->phone,
                    'status'     => 'aktif',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $totalCreated++;
            } else {
                $existing->update([
                    'name'       => $parentName,
                    'username'   => $parentUsername,
                    'identifier' => $parentIdentifier,
                    'email'      => $parentEmail,
                    'student_id' => $student->id,
                    'class_id'   => $student->class_id,
                    'status'     => 'aktif',
                ]);
                $totalUpdated++;
            }
        }

        if (!empty($batch)) {
            foreach (array_chunk($batch, 200) as $chunk) {
                User::insert($chunk);
            }
        }

        $this->command->info("Selesai! {$totalCreated} akun Orang Tua baru dibuat, {$totalUpdated} akun Orang Tua diperbarui.");
    }
}
