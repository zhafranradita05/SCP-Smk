<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordController extends Controller
{
    // Menampilkan halaman form lupa password
    public function showForm()
    {
        return view('auth.forgot_password');
    }

    // Memproses perubahan password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Jika email tidak ada, kembalikan ke halaman sebelumnya dengan pesan error
            return back()->with('error', 'Email tidak ditemukan!');
        }

        // Langsung update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Lempar kembali ke halaman login web dengan pesan sukses
        return redirect('/')->with('success', 'Password berhasil diubah, silakan login dengan password baru.');
    }
}
