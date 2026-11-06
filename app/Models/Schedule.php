<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'teacher_id',
        'class_id',
        'room_id',
        'subject',
        'cycle',
        'day',
        'start_time',
        'end_time',
    ];

    // Relasi ke Guru
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Relasi ke Kelas
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    // Relasi ke Ruangan
    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
