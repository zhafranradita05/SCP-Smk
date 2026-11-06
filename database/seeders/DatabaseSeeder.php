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
        User::create([
            'name'       => 'Waka Kurikulum',
            'username'   => 'waka',
            'email'      => 'waka@sekolah.com',
            'password'   => Hash::make('password123'), 
            'role'       => 'waka_kurikulum',
            'identifier' => '198001012005011001', 
            'phone'      => '08111111111',
        ]);

        // 2. User Guru Pengajar
        $guruPengajar = User::create([
            'name'       => 'Ibu Guru PBO',
            'username'   => 'guru',
            'email'      => 'guru@sekolah.com',
            'password'   => Hash::make('password123'),
            'role'       => 'guru',
            'identifier' => '198502022010022002',
            'phone'      => '08222222222',
        ]);

        // 3. User Guru BK
        User::create([
            'name'       => 'Ibu Guru BK',
            'username'   => 'gurubk',
            'email'      => 'bk@sekolah.com',
            'password'   => Hash::make('password123'),
            'role'       => 'guru_bk',
            'identifier' => '199504042020044004',
            'phone'      => '08555555555',
        ]);

        // 4. Data 14 Guru Wali Kelas Unik (1 Guru = 1 Kelas)
        $waliList = [
            'X RPL 1'   => User::create(['name' => 'Budi Hartono, S.Kom',     'username' => 'walikelas', 'email' => 'hartono@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198501012010011001']),
            'X RPL 2'   => User::create(['name' => 'Siti Nurhaliza, S.Pd',     'username' => 'sitinur',   'email' => 'sitinur@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198702022011022002']),
            'X TKJ 1'   => User::create(['name' => 'Pak Joko Susilo, S.T',     'username' => 'joko',      'email' => 'joko@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198903032012011003']),
            'X DKV 1'   => User::create(['name' => 'Ibu Siti Aminah, M.Ds',    'username' => 'aminah',    'email' => 'aminah@sekolah.com',  'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199004042014022004']),
            'X MP 1'    => User::create(['name' => 'Ibu Ratna Dewi, S.Pd',     'username' => 'ratna',     'email' => 'ratna@sekolah.com',   'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199205052016022005']),

            'XI RPL 1'  => User::create(['name' => 'Ahmad Fauzi, M.Kom',       'username' => 'fauzi',     'email' => 'fauzi@sekolah.com',   'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198606062010011006']),
            'XI TKJ 1'  => User::create(['name' => 'Dewi Lestari, S.T',        'username' => 'dewi',      'email' => 'dewi@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198807072012022007']),
            'XI DKV 1'  => User::create(['name' => 'Rian Hidayat, M.Ds',       'username' => 'rian',      'email' => 'rian@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199108082015011008']),
            'XI MP 1'   => User::create(['name' => 'Sri Wahyuni, S.Pd',        'username' => 'wahyuni',   'email' => 'wahyuni@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199309092017022009']),

            'XII RPL 1' => User::create(['name' => 'Hendra Setiawan, S.Kom',   'username' => 'hendra',    'email' => 'hendra@sekolah.com',  'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198410102009011010']),
            'XII RPL 2' => User::create(['name' => 'Bambang Pamungkas, S.Kom', 'username' => 'bambang',   'email' => 'bambang@sekolah.com', 'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198511112010011011']),
            'XII TKJ 1' => User::create(['name' => 'Eko Prasetyo, S.T',        'username' => 'eko',       'email' => 'eko@sekolah.com',     'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '198912122013011012']),
            'XII DKV 1' => User::create(['name' => 'Maya Indah, M.Ds',         'username' => 'maya',      'email' => 'maya@sekolah.com',    'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199201132016022013']),
            'XII MP 1'  => User::create(['name' => 'Nurul Hidayah, S.Pd',      'username' => 'nurul',     'email' => 'nurul@sekolah.com',   'password' => Hash::make('password123'), 'role' => 'wali_kelas', 'identifier' => '199402142018022014']),
        ];

        // 5. Data Ruangan (Master)
        $roomRpl = Room::create(['name' => 'Lab RPL 1', 'code' => 'LAB-RPL-1']);
        Room::create(['name' => 'Lab TKJ 1', 'code' => 'LAB-TKJ-1']);
        Room::create(['name' => 'Lab Multimedia / DKV', 'code' => 'LAB-DKV']);
        Room::create(['name' => 'Ruang Teori 1', 'code' => 'R-01']);
        Room::create(['name' => 'Ruang Teori 2', 'code' => 'R-02']);

        // 6. Buat Data Kelas
        $createdClasses = [];
        foreach ($waliList as $className => $teacher) {
            $createdClasses[$className] = SchoolClass::create([
                'name'                => $className,
                'homeroom_teacher_id' => $teacher->id,
            ]);
        }

        // 7. Data Siswa per Kelas (Memiliki class_id masing-masing)
        // Siswa Kelas X RPL 1 (Wali Kelas: Budi Hartono / username: walikelas)
        $siswaBudi = User::create([
            'name'       => 'Budi Siswa',
            'username'   => 'budi',
            'email'      => 'budi@sekolah.com',
            'password'   => Hash::make('password123'),
            'role'       => 'siswa',
            'class_id'   => $createdClasses['X RPL 1']->id,
            'identifier' => '1001',
            'phone'      => '08333333331',
        ]);

        $siswaAndi = User::create([
            'name'       => 'Andi Saputra',
            'username'   => 'andi',
            'email'      => 'andi@sekolah.com',
            'password'   => Hash::make('password123'),
            'role'       => 'siswa',
            'class_id'   => $createdClasses['X RPL 1']->id,
            'identifier' => '1002',
            'phone'      => '08333333332',
        ]);

        // Siswa Kelas Lain (X TKJ 1)
        $siswaCitra = User::create([
            'name'       => 'Citra Lestari',
            'username'   => 'citra',
            'email'      => 'citra@sekolah.com',
            'password'   => Hash::make('password123'),
            'role'       => 'siswa',
            'class_id'   => $createdClasses['X TKJ 1']->id,
            'identifier' => '1003',
            'phone'      => '08333333333',
        ]);

        // 8. Data Contoh Izin (Untuk Testing Approval)
        // Izin Siswa Budi (X RPL 1) -> Wajib muncul di Wali Kelas X RPL 1 (username: walikelas)
        LeaveRequest::create([
            'student_id' => $siswaBudi->id,
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d'),
            'reason'     => 'Demam tinggi dan flu',
            'proof_file' => 'leaves/dummy_surat.jpg',
            'status'     => 'pending',
            'leave_type' => 'full_day',
        ]);

        // Izin Siswa Citra (X TKJ 1) -> Hanya muncul di Wali Kelas X TKJ 1 (username: joko) & BK
        LeaveRequest::create([
            'student_id' => $siswaCitra->id,
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d'),
            'reason'     => 'Acara keluarga mendesak',
            'proof_file' => 'leaves/dummy_surat.jpg',
            'status'     => 'pending',
            'leave_type' => 'full_day',
        ]);

        // 9. Data Setting Awal
        Setting::create([
            'active_cycle'       => 'putaran_1',
            'is_schedule_locked' => false,
            'academic_year'      => '2026/2027',
            'active_semester'    => 'ganjil',
        ]);

        // 10. Data Jadwal Contoh (Agar data jadwal tetap ada jika migrate:fresh dijalankan)
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

        // 11. Panggil Seeder Jadwal Nyata SMK Negeri 1 Bantul (XII RPL 2)
        $this->call(RealSchoolScheduleSeeder::class);

        // 12. Panggil Seeder Akun Orang Tua Siswa
        $this->call(ParentAccountsSeeder::class);
    }
}