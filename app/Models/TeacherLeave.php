<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherLeave extends Model
{
    protected $guarded = ['id'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

