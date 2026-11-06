<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    // Tambahkan baris ini agar semua kolom (kecuali id) boleh diisi
    protected $guarded = ['id'];
}
