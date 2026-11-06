<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $guarded = ['id'];

    // Relasi murid yang mengajukan izin
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Relasi Wali Kelas/BK yang memverifikasi izin
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
