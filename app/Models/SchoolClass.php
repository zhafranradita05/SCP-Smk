<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    // Mengarahkan model ini ke tabel 'classes'
    protected $table = 'classes';
    
    protected $guarded = ['id'];

    // Relasi: Kelas ini punya Wali Kelas (User)
    public function homeroomTeacher()
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    // Relasi: Kelas ini punya Guru BK (User)
    public function bkTeacher()
    {
        return $this->belongsTo(User::class, 'bk_teacher_id');
    }
        // Relasi: Kelas memiliki banyak Siswa
    public function students()
    {
        return $this->hasMany(User::class, 'class_id')->where('role', 'siswa');
    }

    // Relasi: Kelas memiliki banyak Jadwal Pelajaran
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    /**
     * 8 Kode Jurusan Resmi di SMKN 1 Bantul
     */
    public const VALID_MAJORS = ['RPL', 'TKJ', 'DKV', 'AK', 'MP', 'BD', 'BR', 'LPS'];

    /**
     * 48 Daftar Nama Kelas Resmi Kanonikal SMKN 1 Bantul
     */
    public const OFFICIAL_CLASSES = [
        'X RPL 1', 'X RPL 2',
        'X TKJ 1', 'X TKJ 2',
        'X DKV 1', 'X DKV 2',
        'X AK 1', 'X AK 2', 'X AK 3',
        'X LPS 1', 'X LPS 2',
        'X MP 1', 'X MP 2',
        'X BR 1', 'X BR 2',
        'X BD',

        'XI RPL 1', 'XI RPL 2',
        'XI TKJ 1', 'XI TKJ 2',
        'XI DKV 1', 'XI DKV 2',
        'XI AK 1', 'XI AK 2', 'XI AK 3',
        'XI LPS 1', 'XI LPS 2',
        'XI MP 1', 'XI MP 2',
        'XI BR 1', 'XI BR 2',
        'XI BD',

        'XII RPL 1', 'XII RPL 2',
        'XII TKJ 1', 'XII TKJ 2',
        'XII DKV 1', 'XII DKV 2',
        'XII AK 1', 'XII AK 2', 'XII AK 3', 'XII AK 4',
        'XII LPS',
        'XII MP 1', 'XII MP 2',
        'XII BR 1', 'XII BR 2',
        'XII BD',
    ];

    /**
     * Resolusi Nama Kelas ke Format Kanonikal Resmi SMKN 1 Bantul
     * Contoh:
     * - "XI RPL1", "xi rpl 1", "11 RPL 1", "1..XI RPL 1" => "XI RPL 1"
     * - "XII AK4" => "XII AK 4"
     * - "XII LPS" => "XII LPS"
     * - "X BD" => "X BD"
     */
    public static function canonicalName(?string $name): ?string
    {
        if (empty($name)) return null;
        $clean = trim((string)$name, " \t\n\r\0\x0B'\"");
        // Hapus nomor urut FET (misal: "1..XII BR 1" atau "1. XII BR 1")
        $clean = preg_replace('/^[0-9]+(?:\.+|[\.\s]*\-+)\s*/', '', $clean);
        $clean = trim(str_ireplace(['kelas', 'kls'], '', $clean));

        // Normalisasi angka romawi
        $clean = preg_replace('/^12(?![0-9])/i', 'xii', $clean);
        $clean = preg_replace('/^11(?![0-9])/i', 'xi', $clean);
        $clean = preg_replace('/^10(?![0-9])/i', 'x', $clean);

        // Standarisasi alias Kurikulum Merdeka
        $aliasMap = [
            'pplg' => 'rpl',
            'tjkt' => 'tkj',
            'mm'   => 'dkv',
            'mplb' => 'mp',
            'otkp' => 'mp',
            'akl'  => 'ak',
            'bdp'  => 'bd',
        ];
        foreach ($aliasMap as $alias => $target) {
            $clean = preg_replace('/\b' . $alias . '\b/i', $target, $clean);
        }

        $norm = preg_replace('/[^a-z0-9]/', '', strtolower($clean));

        foreach (self::OFFICIAL_CLASSES as $official) {
            $officialNorm = preg_replace('/[^a-z0-9]/', '', strtolower($official));
            if ($norm === $officialNorm) {
                return $official;
            }
        }

        return null;
    }

    /**
     * Accessor untuk mendeteksi kode Jurusan dari nama kelas
     * Contoh: "XII RPL 2" => "RPL", "X TKJ 1" => "TKJ", "XI DKV 1" => "DKV", "X MP 1" => "MP"
     */
    public function getMajorAttribute()
    {
        $clean = trim($this->name);
        // Urutkan kode yang lebih panjang terlebih dahulu (contoh: PPLG sebelum RPL, AKL sebelum AK)
        // Gunakan (?![A-Za-z]) agar format tanpa spasi seperti "X AK1" atau "X RPL1" tetap terdeteksi jurusannya
        if (preg_match('/\b(PPLG|RPL|TJKT|TKJ|DKV|MM|MPLB|OTKP|MP|AKL|AK|BDP|BD|BR|LPS|TBG|TBS)(?![A-Za-z])/i', $clean, $matches)) {
            $raw = strtoupper($matches[1]);
            // Standarisasi alias Kurikulum Merdeka ke 8 kode jurusan resmi SMKN 1 Bantul
            $aliasMap = [
                'PPLG' => 'RPL',
                'TJKT' => 'TKJ',
                'MM'   => 'DKV',
                'MPLB' => 'MP',
                'OTKP' => 'MP',
                'AKL'  => 'AK',
                'BDP'  => 'BD',
            ];
            $canonical = $aliasMap[$raw] ?? $raw;
            if (in_array($canonical, self::VALID_MAJORS)) {
                return $canonical;
            }
        }

        $parts = preg_split('/\s+/', $clean);
        if (count($parts) >= 2) {
            // Bersihkan angka rombel jika menempel (contoh: "AK1" -> "AK")
            $candidate = strtoupper(preg_replace('/[0-9]+$/', '', $parts[1]));
            if (in_array($candidate, self::VALID_MAJORS)) {
                return $candidate;
            }
        }

        return 'UMUM';
    }

    /**
     * Nama Lengkap Jurusan untuk Tampilan UI yang Ramah Waka
     */
    public function getMajorFullNameAttribute()
    {
        $major = $this->major;
        $map = [
            'RPL'  => 'Rekayasa Perangkat Lunak',
            'PPLG' => 'Pengembangan Perangkat Lunak & Gim',
            'TKJ'  => 'Teknik Komputer & Jaringan',
            'TJKT' => 'Teknik Jaringan Komputer & Telekomunikasi',
            'DKV'  => 'Desain Komunikasi Visual',
            'MM'   => 'Multimedia',
            'MP'   => 'Manajemen Perkantoran',
            'MPLB' => 'Manajemen Perkantoran & Layanan Bisnis',
            'OTKP' => 'Otomatisasi & Tata Kelola Perkantoran',
            'AKL'  => 'Akuntansi & Keuangan Lembaga',
            'AK'   => 'Akuntansi',
            'BDP'  => 'Bisnis Daring & Pemasaran',
            'BD'   => 'Bisnis Digital',
            'BR'   => 'Bisnis Ritel',
            'LPS'  => 'Layanan Perbankan Syariah',
        ];
        return $map[$major] ?? 'Jurusan ' . $major;
    }

    /**
     * Skema Warna Badge Tailwind untuk Jurusan
     */
    public function getMajorBadgeColorAttribute()
    {
        $major = $this->major;
        return match($major) {
            'RPL', 'PPLG' => 'bg-blue-50 text-blue-700 border-blue-200',
            'TKJ', 'TJKT' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            'DKV', 'MM'   => 'bg-purple-50 text-purple-700 border-purple-200',
            'MP', 'MPLB', 'OTKP' => 'bg-amber-50 text-amber-700 border-amber-200',
            'AKL', 'AK'   => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'BR'          => 'bg-orange-50 text-orange-700 border-orange-200',
            'LPS'         => 'bg-teal-50 text-teal-700 border-teal-200',
            'BD'          => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default       => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }

}
