<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\Schedule;
use Illuminate\Support\Facades\Hash;

class RealSchoolScheduleSeeder extends Seeder
{
    /**
     * Seeder Jadwal dan Ruangan Nyata SMK NEGERI 1 BANTUL (Kelas XII RPL 2)
     * Berdasarkan Data Sistem Blok:
     * - Putaran 1 (Produktif Kejuruan RPL) di LAB 19
     * - Putaran 2 (Normatif & Adaptif) di R10 dan LAB 16
     */
    public function run(): void
    {
        // 1. Pastikan Kelas XII RPL 2 Tersedia
        $class = SchoolClass::where('name', 'XII RPL 2')
            ->orWhere('name', 'XII RPL2')
            ->first();

        if (!$class) {
            $class = SchoolClass::create([
                'name'                => 'XII RPL 2',
                'homeroom_teacher_id' => null,
            ]);
        }

        // 2. Buat / Pastikan Ruangan Sesuai Ketentuan Sekolah
        $rooms = [
            'LAB 19' => Room::firstOrCreate(['code' => 'LAB-19'], ['name' => 'LAB 19']),
            'LAB 16' => Room::firstOrCreate(['code' => 'LAB-16'], ['name' => 'LAB 16']),
            'R10'    => Room::firstOrCreate(['code' => 'R-10'],   ['name' => 'R10']),
        ];

        // 3. Daftar Guru Pengampu Sesuai Jadwal FET Sekolah
        $teachersData = [
            'reny' => [
                'name'       => 'RENY MURNI HIDAYATI, S.Pd',
                'username'   => 'reny_murni',
                'email'      => 'reny@sekolah.com',
                'identifier' => '77',
            ],
            'diah' => [
                'name'       => 'DIAH UTAMININGSIH, S.T',
                'username'   => 'diah_utaminingsih',
                'email'      => 'diah@sekolah.com',
                'identifier' => '45',
            ],
            'rosyida' => [
                'name'       => 'ROSYIDA RAMADHANI, S.Pd',
                'username'   => 'rosyida_ramadhani',
                'email'      => 'rosyida@sekolah.com',
                'identifier' => '51',
            ],
            'dinda' => [
                'name'       => 'DINDA AYU DEWANTI, S.Kom',
                'username'   => 'dinda_ayu',
                'email'      => 'dinda@sekolah.com',
                'identifier' => '81',
            ],
            'rusdiyanto' => [
                'name'       => 'RUSDIYANTO, S.Pd',
                'username'   => 'rusdiyanto',
                'email'      => 'rusdiyanto@sekolah.com',
                'identifier' => '57',
            ],
            'dwi' => [
                'name'       => 'DWI LESMIYATI, S.Pd.',
                'username'   => 'dwi_lesmiyati',
                'email'      => 'dwi@sekolah.com',
                'identifier' => '33',
            ],
            'sujar' => [
                'name'       => 'SUJAR HARTONO, S.Pd',
                'username'   => 'sujar_hartono',
                'email'      => 'sujar@sekolah.com',
                'identifier' => '9',
            ],
            'heru' => [
                'name'       => 'MUHAMMAD HERU PURNOMO, M.S.I',
                'username'   => 'heru_purnomo',
                'email'      => 'heru@sekolah.com',
                'identifier' => '7',
            ],
            'yulita' => [
                'name'       => 'YULITA EKA WINAJENG, S.Pd',
                'username'   => 'yulita_eka',
                'email'      => 'yulita@sekolah.com',
                'identifier' => '72',
            ],
            'novia' => [
                'name'       => 'NOVIA RAHMALINA, S.Pd',
                'username'   => 'novia_rahmalina',
                'email'      => 'novia@sekolah.com',
                'identifier' => '76',
            ],
            'rya' => [
                'name'       => 'RYAWIDYANINGTYAS, S.Pd',
                'username'   => 'ryawidyaningtyas',
                'email'      => 'rya@sekolah.com',
                'identifier' => '89',
            ],
            'widodo' => [
                'name'       => 'WIDODO, S.Pd.',
                'username'   => 'widodo',
                'email'      => 'widodo@sekolah.com',
                'identifier' => '22',
            ],
            'haris' => [
                'name'       => 'HARIS BUDIAWAN, S.Pd',
                'username'   => 'haris_budiawan',
                'email'      => 'haris@sekolah.com',
                'identifier' => '68',
            ],
        ];
        $teachers = [];
        foreach ($teachersData as $key => $t) {
            $user = User::where('email', $t['email'])
                ->orWhere('username', $t['username'])
                ->first();

            if ($user) {
                $user->update([
                    'name'       => $t['name'],
                    'identifier' => $t['identifier'],
                    'role'       => 'guru',
                ]);
            } else {
                $user = User::create([
                    'name'       => $t['name'],
                    'username'   => $t['username'],
                    'email'      => $t['email'],
                    'password'   => Hash::make('password123'),
                    'role'       => 'guru',
                    'identifier' => $t['identifier'],
                    'phone'      => '08' . rand(1111111111, 9999999999),
                ]);
            }
            $teachers[$key] = $user;
        }

        // 4. Pastikan ada Siswa di Kelas XII RPL 2 untuk login mobile
        $siswaRpl2 = User::where('class_id', $class->id)->where('role', 'siswa')->first();
        if (!$siswaRpl2) {
            $siswaRpl2 = User::where('email', 'siswa_rpl2@sekolah.com')
                ->orWhere('username', 'siswa_rpl2')
                ->first();

            if (!$siswaRpl2) {
                User::create([
                    'name'       => 'Muhammad Rizky Pratama',
                    'username'   => 'siswa_rpl2',
                    'email'      => 'siswa_rpl2@sekolah.com',
                    'password'   => Hash::make('password123'),
                    'role'       => 'siswa',
                    'class_id'   => $class->id,
                    'identifier' => '1202001',
                    'phone'      => '081234567890',
                ]);
            }
        }

        // Akun Siswa Budi Santoso (XII RPL 2)
        $budiSantoso = User::where('email', 'budi_santoso@sekolah.com')
            ->orWhere('username', 'budi_santoso')
            ->first();

        if (!$budiSantoso) {
            User::create([
                'name'       => 'Budi Santoso',
                'username'   => 'budi_santoso',
                'email'      => 'budi_santoso@sekolah.com',
                'password'   => Hash::make('password123'),
                'role'       => 'siswa',
                'class_id'   => $class->id,
                'identifier' => '1202002',
                'phone'      => '081234567891',
                'status'     => 'aktif',
            ]);
        }

        // Hapus jadwal kelas XII RPL 2 lama agar tidak duplikat
        Schedule::where('class_id', $class->id)->delete();

        // 5. JADWAL PUTARAN 1 (Seluruh KBM di LAB 19)
        $schedulesPutaran1 = [
            // SENIN
            [
                'day'        => 'Senin',
                'subject'    => 'KK 2 RPL Pemrograman GUI',
                'teacher'    => $teachers['reny'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '07:15:00',
                'end_time'   => '11:15:00',
            ],
            [
                'day'        => 'Senin',
                'subject'    => 'KK 1 RPL Basis Data',
                'teacher'    => $teachers['diah'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '12:30:00',
                'end_time'   => '15:30:00',
            ],
            // SELASA
            [
                'day'        => 'Selasa',
                'subject'    => 'KK 4 RPL Pemrograman Perangkat bergerak',
                'teacher'    => $teachers['rosyida'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '07:15:00',
                'end_time'   => '10:30:00',
            ],
            [
                'day'        => 'Selasa',
                'subject'    => 'KK 5 RPL Analisa dan Perancangan Sistem',
                'teacher'    => $teachers['rosyida'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '10:30:00',
                'end_time'   => '15:30:00',
            ],
            // RABU
            [
                'day'        => 'Rabu',
                'subject'    => 'KK 2 RPL Pemrograman GUI',
                'teacher'    => $teachers['reny'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '07:15:00',
                'end_time'   => '10:30:00',
            ],
            [
                'day'        => 'Rabu',
                'subject'    => 'KK 3 RPL Pemrograman Website',
                'teacher'    => $teachers['dinda'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '10:30:00',
                'end_time'   => '15:30:00',
            ],
            // KAMIS
            [
                'day'        => 'Kamis',
                'subject'    => 'KK 4 RPL Pemrograman Perangkat bergerak',
                'teacher'    => $teachers['rosyida'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '07:15:00',
                'end_time'   => '11:15:00',
            ],
            [
                'day'        => 'Kamis',
                'subject'    => 'KK 3 RPL Pemrograman Website',
                'teacher'    => $teachers['dinda'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '12:30:00',
                'end_time'   => '15:30:00',
            ],
            // JUMAT
            [
                'day'        => 'Jumat',
                'subject'    => 'Mapel Pilihan RPL Internet Of Things',
                'teacher'    => $teachers['rusdiyanto'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '07:15:00',
                'end_time'   => '10:30:00',
            ],
            [
                'day'        => 'Jumat',
                'subject'    => 'KK 3 RPL Pemrograman Website',
                'teacher'    => $teachers['dinda'],
                'room'       => $rooms['LAB 19'],
                'start_time' => '10:30:00',
                'end_time'   => '14:00:00',
            ],
        ];

        foreach ($schedulesPutaran1 as $item) {
            Schedule::create([
                'class_id'   => $class->id,
                'teacher_id' => $item['teacher']->id,
                'room_id'    => $item['room']->id,
                'subject'    => $item['subject'],
                'cycle'      => 'putaran_1',
                'day'        => $item['day'],
                'start_time' => $item['start_time'],
                'end_time'   => $item['end_time'],
            ]);
        }

        // 6. JADWAL PUTARAN 2 (Normada: R10 & LAB 16)
        $schedulesPutaran2 = [
            // SENIN (Semua di R10)
            [
                'day'        => 'Senin',
                'subject'    => 'Matematika',
                'teacher'    => $teachers['dwi'],
                'room'       => $rooms['R10'],
                'start_time' => '07:15:00',
                'end_time'   => '09:45:00',
            ],
            [
                'day'        => 'Senin',
                'subject'    => 'Bahasa Inggris',
                'teacher'    => $teachers['sujar'],
                'room'       => $rooms['R10'],
                'start_time' => '09:45:00',
                'end_time'   => '11:15:00',
            ],
            [
                'day'        => 'Senin',
                'subject'    => 'Bahasa Indonesia',
                'teacher'    => $teachers['heru'],
                'room'       => $rooms['R10'],
                'start_time' => '11:15:00',
                'end_time'   => '14:00:00',
            ],
            [
                'day'        => 'Senin',
                'subject'    => 'Bahasa Jawa',
                'teacher'    => $teachers['yulita'],
                'room'       => $rooms['R10'],
                'start_time' => '14:00:00',
                'end_time'   => '15:30:00',
            ],
            // SELASA (LAB 16 & R10)
            [
                'day'        => 'Selasa',
                'subject'    => 'Mapel Pilihan RPL Internet Of Things',
                'teacher'    => $teachers['rusdiyanto'],
                'room'       => $rooms['LAB 16'],
                'start_time' => '07:15:00',
                'end_time'   => '10:30:00',
            ],
            [
                'day'        => 'Selasa',
                'subject'    => 'Pendidikan Agama dan Budi Pekerti',
                'teacher'    => $teachers['novia'],
                'room'       => $rooms['R10'],
                'start_time' => '10:30:00',
                'end_time'   => '13:15:00',
            ],
            [
                'day'        => 'Selasa',
                'subject'    => 'Bahasa Inggris',
                'teacher'    => $teachers['sujar'],
                'room'       => $rooms['R10'],
                'start_time' => '13:15:00',
                'end_time'   => '15:30:00',
            ],
            // RABU (Semua di R10)
            [
                'day'        => 'Rabu',
                'subject'    => 'Bahasa Indonesia',
                'teacher'    => $teachers['heru'],
                'room'       => $rooms['R10'],
                'start_time' => '07:15:00',
                'end_time'   => '09:45:00',
            ],
            [
                'day'        => 'Rabu',
                'subject'    => 'Bimbingan dan Konseling',
                'teacher'    => $teachers['rya'],
                'room'       => $rooms['R10'],
                'start_time' => '09:45:00',
                'end_time'   => '11:15:00',
            ],
            [
                'day'        => 'Rabu',
                'subject'    => 'Matematika',
                'teacher'    => $teachers['dwi'],
                'room'       => $rooms['R10'],
                'start_time' => '11:15:00',
                'end_time'   => '14:00:00',
            ],
            [
                'day'        => 'Rabu',
                'subject'    => 'Pendidikan Pancasila',
                'teacher'    => $teachers['widodo'],
                'room'       => $rooms['R10'],
                'start_time' => '14:00:00',
                'end_time'   => '15:30:00',
            ],
            // KAMIS (R10 & LAB 16)
            [
                'day'        => 'Kamis',
                'subject'    => 'Bahasa Inggris',
                'teacher'    => $teachers['sujar'],
                'room'       => $rooms['R10'],
                'start_time' => '07:15:00',
                'end_time'   => '09:45:00',
            ],
            [
                'day'        => 'Kamis',
                'subject'    => 'Bahasa Jawa',
                'teacher'    => $teachers['yulita'],
                'room'       => $rooms['R10'],
                'start_time' => '09:45:00',
                'end_time'   => '11:15:00',
            ],
            [
                'day'        => 'Kamis',
                'subject'    => 'Kreatifitas, Inovasi dan kewirausahaan',
                'teacher'    => $teachers['haris'],
                'room'       => $rooms['LAB 16'],
                'start_time' => '11:15:00',
                'end_time'   => '15:30:00',
            ],
            // JUMAT (R10 & LAB 16)
            [
                'day'        => 'Jumat',
                'subject'    => 'Pendidikan Agama dan Budi Pekerti',
                'teacher'    => $teachers['novia'],
                'room'       => $rooms['R10'],
                'start_time' => '07:15:00',
                'end_time'   => '10:30:00',
            ],
            [
                'day'        => 'Jumat',
                'subject'    => 'Kreatifitas, Inovasi dan kewirausahaan',
                'teacher'    => $teachers['haris'],
                'room'       => $rooms['LAB 16'],
                'start_time' => '10:30:00',
                'end_time'   => '14:00:00',
            ],
        ];

        foreach ($schedulesPutaran2 as $item) {
            Schedule::create([
                'class_id'   => $class->id,
                'teacher_id' => $item['teacher']->id,
                'room_id'    => $item['room']->id,
                'subject'    => $item['subject'],
                'cycle'      => 'putaran_2',
                'day'        => $item['day'],
                'start_time' => $item['start_time'],
                'end_time'   => $item['end_time'],
            ]);
        }
    }
}

