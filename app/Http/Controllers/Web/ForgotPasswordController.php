<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    /**
     * Menampilkan halaman form lupa password (Step 1: Input Email atau Step 2: Input OTP)
     */
    public function showForm(Request $request)
    {
        $email = $request->query('email', session('otp_email'));
        $step = ($email && (session('otp_sent') || $request->has('email'))) ? 2 : 1;

        return view('auth.forgot_password', compact('email', 'step'));
    }

    /**
     * Mengirimkan kode OTP 6-digit ke email Gmail terdaftar
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $email = trim(strtolower($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->with('error', 'Email tidak terdaftar dalam sistem akademik!')->withInput();
        }

        // Generate 6 digit angka acak
        $otp = sprintf("%06d", mt_rand(100000, 999999));

        // Simpan atau perbarui kode OTP di tabel password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => $otp,
                'created_at' => Carbon::now(),
            ]
        );

        // Kirim email OTP menggunakan template email resmi SCP
        $mailSent = false;
        try {
            Mail::send('emails.otp', ['name' => $user->name, 'otp' => $otp], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Kode OTP Reset Password - SCP Presensi');
            });
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim email OTP ke {$user->email}: " . $e->getMessage());
        }

        $successMessage = "Kode OTP 6-digit telah dikirim ke <strong>{$user->email}</strong>. Silakan periksa kotak masuk atau folder spam Gmail Anda.";
        
        // Jika mail driver masih 'log' (mode lokal belum ada SMTP)
        if (config('mail.default') === 'log') {
            $successMessage .= " <br><span class='text-[11px] text-amber-700 font-normal'>(Mode Dev/Log: Kode OTP Anda adalah <strong>{$otp}</strong>)</span>";
        }

        return redirect()->route('password.request', ['email' => $user->email])
            ->with('otp_email', $user->email)
            ->with('otp_sent', true)
            ->with('success', $successMessage);
    }

    /**
     * Memverifikasi kode OTP dan memperbarui password baru
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'otp'                   => 'required|string|size:6',
            'new_password'          => 'required|min:6|confirmed',
        ], [
            'email.required'        => 'Email tidak boleh kosong.',
            'otp.required'          => 'Kode OTP wajib diisi.',
            'otp.size'              => 'Kode OTP harus berupa 6 digit angka.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min'      => 'Password baru minimal 6 karakter.',
            'new_password.confirmed'=> 'Konfirmasi password baru tidak cocok.',
        ]);

        $email = trim(strtolower($request->email));
        $otp = trim($request->otp);

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || $record->token !== $otp) {
            return back()->with('error', 'Kode OTP yang Anda masukkan salah atau tidak sesuai!')
                         ->with('otp_email', $email)
                         ->with('otp_sent', true)
                         ->withInput();
        }

        // Cek kedaluwarsa kode OTP (10 menit)
        $isExpired = Carbon::parse($record->created_at)->addMinutes(10)->isPast();
        if ($isExpired) {
            return back()->with('error', 'Kode OTP telah kedaluwarsa (berlaku 10 menit). Silakan kirim ulang kode baru.')
                         ->with('otp_email', $email)
                         ->with('otp_sent', true)
                         ->withInput();
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return back()->with('error', 'Pengguna dengan email ini tidak ditemukan.');
        }

        // Update password baru
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Hapus token yang telah berhasil digunakan
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        return redirect()->route('login')->with('success', 'Password berhasil diperbarui! Silakan login menggunakan password baru Anda.');
    }
}
