<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. Tampilkan Halaman Login
    public function showLogin()
    {
        return view('auth.login');
    }

    // 2. Proses Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            
            // 1. Larang keras akun Siswa login ke portal web
            if ($user->role === 'siswa') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'Akun Siswa hanya dapat digunakan di aplikasi mobile SCP, tidak diizinkan mengakses portal web!',
                ])->onlyInput('username');
            }

            // 2. Akses Waka Kurikulum
            if ($user->role === 'waka_kurikulum') {
                return redirect()->intended('/waka/dashboard');
            }

            // 3. Akses Guru Pengajar, Wali Kelas & Guru BK (Halaman Jadwal Mengajar Guru & Status Jurnal)
            if (in_array($user->role, ['guru', 'wali_kelas', 'guru_bk'])) {
                return redirect()->route('teacher.schedule');
            }
            
            // Role lainnya tidak memiliki akses ke portal web
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'username' => 'Akun Anda tidak memiliki hak akses ke portal web ini.',
            ])->onlyInput('username');
        }

        return back()->withErrors([
            'username' => 'Username atau Password salah!',
        ])->onlyInput('username');
    }

    // 3. Proses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}