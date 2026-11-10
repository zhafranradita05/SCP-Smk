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
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'waka_kurikulum') {
                return redirect('/waka/dashboard');
            }

            // Pengguna selain Waka Kurikulum otomatis dilogout dari web
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
        }

        return view('auth.login');
    }

    // 2. Proses Login (Khusus Waka Kurikulum)
    public function login(Request $request)
    {
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        $input = trim($request->input('username'));
        $password = $request->input('password');

        // Dukung pencarian user via: username asli, username dengan underscore, email, atau identifier
        $user = \App\Models\User::where('username', $input)
            ->orWhere('username', str_replace(' ', '_', $input))
            ->orWhere('email', $input)
            ->orWhere('identifier', $input)
            ->first();

        if ($user && \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            // Kebijakan Khusus: Akses portal website HANYA diizinkan untuk Waka Kurikulum
            if ($user->role !== 'waka_kurikulum') {
                return back()->withErrors([
                    'username' => 'Akses portal website hanya untuk Waka Kurikulum. Untuk Guru, Wali Kelas, Guru BK, Siswa, dan Orang Tua silakan gunakan Aplikasi Mobile SCP.',
                ])->onlyInput('username');
            }

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('waka_last_activity_time', time());
            return redirect()->intended('/waka/dashboard');
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