<?php

namespace App\Models;

// Tambahkan baris HasApiTokens di bawah ini
use Laravel\Sanctum\HasApiTokens; 
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    // Tambahkan HasApiTokens di dalam trait
    use HasApiTokens, HasFactory, Notifiable;

    // Izinkan semua kolom untuk diisi
    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // Accessor: Bersihkan nomor/ID di awal nama secara otomatis (misal "22. WIDODO, S.Pd." -> "WIDODO, S.Pd.")
    public function getNameAttribute($value)
    {
        if (!$value) return $value;
        return trim(preg_replace('/^[0-9]+[\.\s]+/', '', $value));
    }

    // Mutator: Pastikan saat simpan nama baru/update, nomor di depan nama juga dibersihkan
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value ? trim(preg_replace('/^[0-9]+[\.\s]+/', '', $value)) : $value;
    }

    // Relasi: Siswa memiliki 1 Kelas
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    // Relasi: Guru sebagai Wali Kelas memiliki 1 Kelas
    public function homeroomClass()
    {
        return $this->hasOne(SchoolClass::class, 'homeroom_teacher_id');
    }

    // Relasi: Guru BK membina banyak Kelas (1 Guru BK -> Banyak Kelas)
    public function bkClasses()
    {
        return $this->hasMany(SchoolClass::class, 'bk_teacher_id');
    }

    // Relasi: Akun Orang Tua merujuk ke Siswa (Anaknya)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Relasi: Siswa memiliki 1 Akun Orang Tua
    public function parent()
    {
        return $this->hasOne(User::class, 'student_id');
    }

    // Scope: Ambil Pengguna Aktif (Bukan Alumni/Lulus)
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'aktif')->orWhereNull('status');
        });
    }

    // Helper: Cek apakah siswa berstatus alumni/lulus
    public function isGraduated(): bool
    {
        return ($this->status ?? 'aktif') === 'lulus';
    }

    // Helper: Cek peran Orang Tua
    public function isParent(): bool
    {
        return $this->role === 'orang_tua';
    }

    // Helper: Cek peran Siswa
    public function isStudent(): bool
    {
        return $this->role === 'siswa';
    }
}
