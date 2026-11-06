<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{

        /**
     * 1. Mengirimkan kode OTP 6-digit ke Gmail pengguna
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string'
        ]);

        $input = trim($request->email);
        $upperInput = strtoupper($input);

        // Cari berdasarkan Email, Username, atau Identifier/NIS
        $user = User::where(function ($q) use ($input, $upperInput) {
            $q->where('email', $input)
              ->orWhere('username', $input)
              ->orWhere('username', $upperInput)
              ->orWhere('identifier', $input)
              ->orWhere('identifier', $upperInput);
        })->first();

        // Jika diawali huruf 'P' (akun orang tua)
        if (!$user && str_starts_with($upperInput, 'P')) {
            $studentNis = substr($upperInput, 1);
            $user = User::where('role', 'orang_tua')
                ->where(function ($q) use ($input, $upperInput, $studentNis) {
                    $q->where('username', $input)
                      ->orWhere('username', $upperInput)
                      ->orWhere('identifier', $input)
                      ->orWhere('identifier', $upperInput)
                      ->orWhereHas('student', function ($sq) use ($studentNis) {
                          $sq->where('identifier', $studentNis)
                             ->orWhere('username', strtolower($studentNis));
                      });
                })->first();
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun atau alamat email tidak ditemukan di sistem!'
            ], 404);
        }

        // Generate 6 digit angka acak
        $otp = (string) rand(100000, 999999);

        // Simpan ke tabel password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $otp,
                'created_at' => Carbon::now()
            ]
        );

        // Coba kirim via SMTP Gmail
        $mailSuccess = false;
        try {
            Mail::send('emails.otp', ['name' => $user->name, 'otp' => $otp], function ($message) use ($user) {
                $message->to($user->email)
                        ->subject('Kode OTP Reset Password - SCP Presensi');
            });
            $mailSuccess = true;
        } catch (\Exception $e) {
            Log::error('SMTP Gmail Error: ' . $e->getMessage());
            $mailSuccess = false;
        }

        if ($mailSuccess) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kode OTP berhasil dikirim ke ' . $user->email . '. Silakan buka aplikasi Gmail di perangkat Anda untuk melihat kode verifikasi.',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim email ke Gmail. Pastikan Sandi Aplikasi Google di file .env sudah benar dan internet aktif.'
            ], 500);
        }
    }

    /**
     * 2. Memverifikasi apakah kode OTP cocok dan belum kedaluwarsa
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'otp'   => 'required|string|size:6'
        ]);

        $input = trim($request->email);
        $user = User::where('email', $input)
            ->orWhere('username', $input)
            ->orWhere('identifier', $input)
            ->first();
        $targetEmail = $user ? $user->email : $input;

        $record = DB::table('password_reset_tokens')
            ->where('email', $targetEmail)
            ->first();

        if (!$record || $record->token !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP yang Anda masukkan salah!'
            ], 422);
        }

        // Cek masa berlaku (10 menit)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(10)->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kode OTP telah kedaluwarsa (lebih dari 10 menit). Silakan minta kode baru.'
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kode OTP valid! Silakan masukkan password baru Anda.'
        ]);
    }

    /**
     * 3. Menyimpan password baru setelah OTP terverifikasi
     */
    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'email'        => 'required|string',
            'otp'          => 'required|string|size:6',
            'new_password' => 'required|min:6'
        ]);

        $input = trim($request->email);
        $user = User::where('email', $input)
            ->orWhere('username', $input)
            ->orWhere('identifier', $input)
            ->first();
        $targetEmail = $user ? $user->email : $input;

        $record = DB::table('password_reset_tokens')
            ->where('email', $targetEmail)
            ->first();

        if (!$record || $record->token !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi reset tidak valid atau kode OTP salah!'
            ], 422);
        }

        if (!$user) {
            $user = User::where('email', $targetEmail)->first();
        }
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan!'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Hapus token yang sudah terpakai
        DB::table('password_reset_tokens')->where('email', $targetEmail)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah, silakan login dengan password baru.'
        ]);
    }

    // 1. FITUR LOGIN (Mendukung Guru, Siswa [NIS], dan Orang Tua [P<NIS>])
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $input = trim($request->username);
        $upperInput = strtoupper($input);

        // 1. Pencarian Pengguna yang fleksibel (Username, Email, atau Identifier / NIS)
        $user = User::where(function ($q) use ($input, $upperInput) {
            $q->where('username', $input)
              ->orWhere('username', $upperInput)
              ->orWhere('email', $input)
              ->orWhere('identifier', $input)
              ->orWhere('identifier', $upperInput);
        })->first();

        // 2. Jika input diawali 'P'/'p' (Login Orang Tua menggunakan P + NIS anak)
        if (!$user && str_starts_with($upperInput, 'P')) {
            $studentNis = substr($upperInput, 1); // Buang huruf 'P' di depan untuk mencari NIS anak
            $user = User::where('role', 'orang_tua')
                ->where(function ($q) use ($input, $upperInput, $studentNis) {
                    $q->where('username', $input)
                      ->orWhere('username', $upperInput)
                      ->orWhere('identifier', $input)
                      ->orWhere('identifier', $upperInput)
                      ->orWhereHas('student', function ($sq) use ($studentNis) {
                          $sq->where('identifier', $studentNis)
                             ->orWhere('username', strtolower($studentNis));
                      });
                })->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username/NIS atau Password salah!'
            ], 401);
        }

        // Cek status siswa yang sudah lulus (Alumni)
        if ($user->role === 'siswa' && ($user->status ?? 'aktif') === 'lulus') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda berstatus Alumni (Lulus). Akses presensi aplikasi mobile telah dinonaktifkan.'
            ], 403);
        }

        // Cek jika akun orang tua tetapi anaknya sudah lulus
        if ($user->role === 'orang_tua' && $user->student && ($user->student->status ?? 'aktif') === 'lulus') {
            return response()->json([
                'status' => 'error',
                'message' => 'Siswa yang bersangkutan telah dinyatakan Lulus (Alumni). Akses portal orang tua dinonaktifkan.'
            ], 403);
        }

        // Muat relasi kelas siswa/guru/orang_tua
        if ($user->role === 'orang_tua') {
            $user->load(['student.schoolClass', 'schoolClass']);
        } else {
            $user->load('schoolClass');
        }

        // Generate Token Sanctum
        $token = $user->createToken('scp_mobile_token')->plainTextToken;

        // Informasi Anak (untuk sesi Orang Tua)
        $child = $user->student;
        $childName = $child ? $child->name : null;
        $className = $child && $child->schoolClass ? $child->schoolClass->name : ($user->schoolClass->name ?? '');
        $roleSubtitle = $user->role === 'orang_tua' 
            ? ("Orang Tua dari {$childName} (" . ($className ?: 'Peserta Didik') . ")")
            : ($user->schoolClass->name ?? 'Siswa');

        $userPayload = $user->toArray();
        $userPayload['student_name'] = $childName;
        $userPayload['student_identifier'] = $child ? $child->identifier : null;
        $userPayload['class_name'] = $className;
        $userPayload['role_subtitle'] = $roleSubtitle;
        if ($child && $child->schoolClass) {
            $userPayload['school_class'] = $child->schoolClass;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => $userPayload
        ]);
    }
    // 2. FITUR LOGOUT
    public function logout(Request $request)
    {
        // Menghapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }

    // 3. FITUR FORGOT PASSWORD (SIMULASI SEDERHANA UNTUK UKK)
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak ditemukan!'
            ], 404);
        }

        // Login otomatis (Bypass) tanpa OTP
        $token = $user->createToken('scp_mobile_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil masuk menggunakan Email',
            'token' => $token,
            'user' => $user
        ]);
    }

    // 3. FITUR VERIFIKASI EMAIL SEBELUM RESET PASSWORD
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak terdaftar di sistem!'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Email ditemukan',
            'user' => [
                'name'     => $user->name,
                'username' => $user->username,
                'email'    => $user->email,
                'role'     => $user->role,
            ]
        ]);
    }

    // 4. FITUR SIMPAN PASSWORD BARU
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|min:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email tidak ditemukan!'
            ], 404);
        }

        // Langsung update password (karena ini API simpel tanpa kirim email asli)
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah, silakan login dengan password baru.'
        ]);
    }
}

