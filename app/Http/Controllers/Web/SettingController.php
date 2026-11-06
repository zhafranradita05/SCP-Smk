<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    // Tampilkan form setting
    public function index()
    {
        $setting = Setting::first();
        return view('waka.settings', compact('setting'));
    }

    // Simpan perubahan setting
    public function update(Request $request)
    {
        $setting = Setting::first();
        
        $updateData = [
            'active_cycle'       => $request->active_cycle ?? $setting->active_cycle,
            'is_schedule_locked' => $request->has('is_schedule_locked') ? true : false,
        ];

        if ($request->filled('academic_year')) {
            $updateData['academic_year'] = $request->academic_year;
        }

        if ($request->filled('active_semester')) {
            $updateData['active_semester'] = $request->active_semester;
        }

        $setting->update($updateData);

        return redirect()->back()->with('success', 'Pengaturan Tahun Ajaran, Semester & Siklus berhasil diperbarui!');
    }
}
