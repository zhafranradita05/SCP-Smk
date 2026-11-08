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
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        $input = trim($request->input('username'));
        $password = $request->input('password');

        // Dukung pencarian user via: username asli, username dengan underscore (jika user mengetik spasi), email, atau identifier
        $user = \App\Models\User::where('username', $input)
            ->orWhere('username', str_replace(' ', '_', $input))
            ->orWhere('email', $input)
            ->orWhere('identifier', $input)
            ->first();

        if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            Auth::login($user);
            $request->session()->regenerate();
            
            // 1. Larang keras akun Siswa login ke portal web
            if ($user->role === 'siswa') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'Akun Siswa hanya dapat digunakan di aplikasi mobile SCP, tidak diizinkan mengakses portal web!',
                ])->onlyInput('username');
            }

            // 2. Akses Waka Kurikulum (Set Inisialisasi Sesi Aktif 10 Menit)
            if ($user->role === 'waka_kurikulum') {
                $request->session()->put('waka_last_activity_time', time());
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