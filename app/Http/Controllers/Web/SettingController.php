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
        if (!$setting) {
            $setting = Setting::create(['active_cycle' => 'putaran_1']);
        }
        
        $updateData = [
            'is_schedule_locked' => $request->has('is_schedule_locked'),
        ];

        if ($request->filled('active_cycle')) {
            $updateData['active_cycle'] = $request->active_cycle;
        }

        if ($request->filled('cycle_mode')) {
            $updateData['cycle_mode'] = $request->cycle_mode;
        }

        if ($request->filled('cycle_start_date')) {
            $updateData['cycle_start_date'] = $request->cycle_start_date;
        }

        if ($request->filled('cycle_start_cycle')) {
            $updateData['cycle_start_cycle'] = $request->cycle_start_cycle;
        }

        if ($request->filled('cycle_interval_weeks')) {
            $updateData['cycle_interval_weeks'] = max(1, (int) $request->cycle_interval_weeks);
        }

        if ($request->filled('academic_year')) {
            $updateData['academic_year'] = $request->academic_year;
        }

        if ($request->filled('active_semester')) {
            $updateData['active_semester'] = $request->active_semester;
        }

        if ($request->has('apk_download_url')) {
            $updateData['apk_download_url'] = $request->apk_download_url;
        }

        $setting->update($updateData);

        return redirect()->back()->with('success', 'Pengaturan Siklus 2 Mingguan & Tahun Ajaran berhasil disimpan!');
    }
}
