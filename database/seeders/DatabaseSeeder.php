<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Schedule;
use App\Models\LeaveRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. User Waka Kurikulum
        User::firstOrCreate(
            ['username' => 'waka'],
            [
                'name'       => 'Waka Kurikulum',
                'email'      => 'waka@sekolah.com',
                'password'   => Hash::make('password123'), 
                'role'       => 'waka_kurikulum',
                'identifier' => '198001012005011001', 
                'phone'      => '08111111111',
            ]
        );

        // 2. User Guru Pengajar
        $guruPengajar = User::firstOrCreate(
            ['username' => 'guru'],
            [
                'name'       => 'Ibu Guru PBO',
                'email'      => 'guru@sekolah.com',
                'password'   => Hash::make('password123'),
                'role'       => 'guru',
                'identifier' => '198502022010022002',
                'phone'      => '08222222222',
            ]
        );

        // 3. User Guru BK
        User::firstOrCreate(
            ['username' => 'gurubk'],
            [
                'name'       => 'Ibu Guru BK',
                'email'      => 'bk@sekolah.com',
                'password'   => Hash::make('password123'),
                'role'       => 'guru_bk',
                'identifier' => '199504042020044004',
                'phone'      => '08555555555',
            ]
        );

        // 4. Data 14 Guru Wali Kelas Unik (1 Guru = 1 Kelas)
        $waliList = [
            'X RPL 1'   => User::firstOrCreate(['username' => 'walikelas'], ['name' => 'Budi Hartono, S.Kom',     'email' => 'hartono@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198501012010011001']),
            'X RPL 2'   => User::firstOrCreate(['username' => 'sitinur'],   ['name' => 'Siti Nurhaliza, S.Pd',     'email' => 'sitinur@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198702022011022002']),
            'X TKJ 1'   => User::firstOrCreate(['username' => 'joko'],      ['name' => 'Pak Joko Susilo, S.T',     'email' => 'joko@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198903032012011003']),
            'X DKV 1'   => User::firstOrCreate(['username' => 'aminah'],    ['name' => 'Ibu Siti Aminah, M.Ds',    'email' => 'aminah@sekolah.com',  'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199004042014022004']),
            'X MP 1'    => User::firstOrCreate(['username' => 'ratna'],     ['name' => 'Ibu Ratna Dewi, S.Pd',     'email' => 'ratna@sekolah.com',   'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199205052016022005']),

            'XI RPL 1'  => User::firstOrCreate(['username' => 'fauzi'],     ['name' => 'Ahmad Fauzi, M.Kom',       'email' => 'fauzi@sekolah.com',   'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198606062010011006']),
            'XI TKJ 1'  => User::firstOrCreate(['username' => 'dewi'],      ['name' => 'Dewi Lestari, S.T',        'email' => 'dewi@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198807072012022007']),
            'XI DKV 1'  => User::firstOrCreate(['username' => 'rian'],      ['name' => 'Rian Hidayat, M.Ds',       'email' => 'rian@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199108082015011008']),
            'XI MP 1'   => User::firstOrCreate(['username' => 'wahyuni'],   ['name' => 'Sri Wahyuni, S.Pd',        'email' => 'wahyuni@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199309092017022009']),

            'XII RPL 1' => User::firstOrCreate(['username' => 'hendra'],    ['name' => 'Hendra Setiawan, S.Kom',   'email' => 'hendra@sekolah.com',  'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198410102009011010']),
            'XII RPL 2' => User::firstOrCreate(['username' => 'bambang'],   ['name' => 'Bambang Pamungkas, S.Kom', 'email' => 'bambang@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198511112010011011']),
            'XII TKJ 1' => User::firstOrCreate(['username' => 'eko'],       ['name' => 'Eko Prasetyo, S.T',        'email' => 'eko@sekolah.com',     'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198912122013011012']),
            'XII DKV 1' => User::firstOrCreate(['username' => 'maya'],      ['name' => 'Maya Indah, M.Ds',         'email' => 'maya@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199201132016022013']),
            'XII MP 1'  => User::firstOrCreate(['username' => 'nurul'],     ['name' => 'Nurul Hidayah, S.Pd',      'email' => 'nurul@sekolah.com',   'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199402142018022014']),
        ];

        // 5. Data Ruangan (Master)
        $roomRpl = Room::firstOrCreate(['code' => 'LAB-RPL-1'], ['name' => 'Lab RPL 1']);
        Room::firstOrCreate(['code' => 'LAB-TKJ-1'], ['name' => 'Lab TKJ 1']);
        Room::firstOrCreate(['code' => 'LAB-DKV'],   ['name' => 'Lab Multimedia / DKV']);
        Room::firstOrCreate(['code' => 'R-01'],      ['name' => 'Ruang Teori 1']);
        Room::firstOrCreate(['code' => 'R-02'],      ['name' => 'Ruang Teori 2']);

        // 6. Buat Data Kelas
        $createdClasses = [];
        foreach ($waliList as $className => $teacher) {
            $createdClasses[$className] = SchoolClass::firstOrCreate(
                ['name' => $className],
                ['homeroom_teacher_id' => $teacher->id]
            );
        }

        // 7. Data Siswa per Kelas (Memiliki class_id masing-masing)
        $siswaBudi = User::firstOrCreate(
            ['username' => 'budi'],
            [
                'name'       => 'Budi Siswa',
                'email'      => 'budi@sekolah.com',
                'password'   => Hash::make('password123'),
                'role'       => 'siswa',
                'class_id'   => $createdClasses['X RPL 1']->id,
                'identifier' => '1001',
                'phone'      => '08333333331',
            ]
        );

        $siswaAndi = User::firstOrCreate(
            ['username' => 'andi'],
            [
                'name'       => 'Andi Saputra',
                'email'      => 'andi@sekolah.com',
                'password'   => Hash::make('password123'),
                'role'       => 'siswa',
                'class_id'   => $createdClasses['X RPL 1']->id,
                'identifier' => '1002',
                'phone'      => '08333333332',
            ]
        );

        // Siswa Kelas Lain (X TKJ 1)
        $siswaCitra = User::firstOrCreate(
            ['username' => 'citra'],
            [
                'name'       => 'Citra Lestari',
                'email'      => 'citra@sekolah.com',
                'password'   => Hash::make('password123'),
                'role'       => 'siswa',
                'class_id'   => $createdClasses['X TKJ 1']->id,
                'identifier' => '1003',
                'phone'      => '08333333333',
            ]
        );

        // 8. Data Contoh Izin (Untuk Testing Approval)
        if (LeaveRequest::where('student_id', $siswaBudi->id)->count() === 0) {
            LeaveRequest::create([
                'student_id' => $siswaBudi->id,
                'start_date' => date('Y-m-d'),
                'end_date'   => date('Y-m-d'),
                'reason'     => 'Demam tinggi dan flu',
                'proof_file' => 'leaves/dummy_surat.jpg',
                'status'     => 'pending',
                'leave_type' => 'full_day',
            ]);
        }

        if (LeaveRequest::where('student_id', $siswaCitra->id)->count() === 0) {
            LeaveRequest::create([
                'student_id' => $siswaCitra->id,
                'start_date' => date('Y-m-d'),
                'end_date'   => date('Y-m-d'),
                'reason'     => 'Acara keluarga mendesak',
                'proof_file' => 'leaves/dummy_surat.jpg',
                'status'     => 'pending',
                'leave_type' => 'full_day',
            ]);
        }

        // 9. Data Setting Awal
        Setting::firstOrCreate(
            ['id' => 1],
            [
                'active_cycle'       => 'putaran_1',
                'is_schedule_locked' => false,
                'academic_year'      => '2026/2027',
                'active_semester'    => 'ganjil',
            ]
        );

        // 10. Data Jadwal Contoh
        if (Schedule::where('teacher_id', $guruPengajar->id)->where('class_id', $createdClasses['X RPL 1']->id)->count() === 0) {
            Schedule::create([
                'teacher_id' => $guruPengajar->id,
                'class_id'   => $createdClasses['X RPL 1']->id,
                'room_id'    => $roomRpl->id,
                'subject'    => 'Pemrograman Berorientasi Objek',
                'cycle'      => 'putaran_1',
                'day'        => 'Senin',
                'start_time' => '07:30:00',
                'end_time'   => '10:00:00',
            ]);

            Schedule::create([
                'teacher_id' => $guruPengajar->id,
                'class_id'   => $createdClasses['X RPL 1']->id,
                'room_id'    => $roomRpl->id,
                'subject'    => 'Basis Data',
                'cycle'      => 'putaran_2',
                'day'        => 'Selasa',
                'start_time' => '10:15:00',
                'end_time'   => '12:45:00',
            ]);
        }

        // 11. Panggil Seeder Jadwal Nyata SMK Negeri 1 Bantul (XII RPL 2)
        $this->call(RealSchoolScheduleSeeder::class);

        // 12. Panggil Seeder Akun Orang Tua Siswa
        $this->call(ParentAccountsSeeder::class);
    }
}