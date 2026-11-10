<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\Schedule;

class CompleteWeeklyScheduleSeeder extends Seeder
{
    /**
     * Memastikan SELURUH GURU di sistem memiliki jadwal mengajar lengkap (Senin - Jumat)
     * pada Putaran 1 dan Putaran 2 sesuai dengan putaran yang ditetapkan oleh Waka Kurikulum.
     */
    public function run(): void
    {
        $teachers = User::whereIn('role', ['guru', 'wali_kelas', 'guru_bk'])->get();
        if ($teachers->isEmpty()) {
            return;
        }

        $classes = SchoolClass::all();
        if ($classes->isEmpty()) {
            $classes = collect([
                SchoolClass::create(['name' => 'X RPL 1']),
                SchoolClass::create(['name' => 'XI RPL 1']),
                SchoolClass::create(['name' => 'XII RPL 2']),
            ]);
        }

        $rooms = Room::all();
        if ($rooms->isEmpty()) {
            $rooms = collect([
                Room::create(['code' => 'LAB-19', 'name' => 'LAB 19']),
                Room::create(['code' => 'LAB-16', 'name' => 'LAB 16']),
                Room::create(['code' => 'R-10',   'name' => 'R10']),
                Room::create(['code' => 'R-12',   'name' => 'Ruang 12']),
            ]);
        }

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $cycles = ['putaran_1', 'putaran_2'];

        $timeSlots = [
            ['start' => '07:15:00', 'end' => '09:45:00'],
            ['start' => '10:00:00', 'end' => '11:45:00'],
            ['start' => '12:30:00', 'end' => '15:15:00'],
        ];

        $defaultSubjects = [
            'Konsentrasi Keahlian RPL (Pemrograman)',
            'Basis Data & Pemrograman SQL',
            'Pemrograman Web & Mobile',
            'Analisis & Perancangan Perangkat Lunak',
            'Projek Kreatif dan Kewirausahaan (PKK)',
            'Informatika & Literasi Digital',
            'Pendidikan Karakter & Bimbingan Konseling'
        ];

        $classCount = $classes->count();
        $roomCount = $rooms->count();
        $createdCount = 0;

        foreach ($teachers as $tIdx => $teacher) {
            foreach ($cycles as $cycle) {
                foreach ($days as $dIdx => $day) {
                    // Cek apakah guru ini sudah punya jadwal di hari dan putaran ini
                    $exists = Schedule::where('teacher_id', $teacher->id)
                        ->where('day', $day)
                        ->where(function ($q) use ($cycle) {
                            $q->where('cycle', $cycle)
                              ->orWhere('cycle', ($cycle === 'putaran_2' ? '2' : '1'));
                        })
                        ->exists();

                    if (!$exists) {
                        // Tentukan kelas, ruangan, slot jam dan mapel secara deterministik
                        $cls = $classes[($tIdx + $dIdx) % $classCount];
                        $rm = $rooms[($tIdx + $dIdx + 1) % $roomCount];
                        $slot = $timeSlots[($tIdx + $dIdx) % count($timeSlots)];
                        $subject = $defaultSubjects[($tIdx + $dIdx) % count($defaultSubjects)];

                        Schedule::create([
                            'teacher_id' => $teacher->id,
                            'class_id'   => $cls->id,
                            'room_id'    => $rm->id,
                            'subject'    => $subject,
                            'cycle'      => $cycle,
                            'day'        => $day,
                            'start_time' => $slot['start'],
                            'end_time'   => $slot['end'],
                        ]);
                        $createdCount++;
                    }
                }
            }
        }

        echo "CompleteWeeklyScheduleSeeder: {$createdCount} jadwal baru berhasil ditambahkan untuk memastikan semua guru memiliki jadwal penuh 1 minggu.\n";
    }
}

