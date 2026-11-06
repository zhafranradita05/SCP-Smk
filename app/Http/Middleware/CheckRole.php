<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Pastikan pengguna sudah terotentikasi
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Unauthenticated.'
                ], 401);
            }
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Ratakan daftar role yang diperbolehkan (mendukung format 'role:a,b' dan 'role:a, b')
        $allowedRoles = [];
        foreach ($roles as $role) {
            foreach (explode(',', $role) as $r) {
                $trimmed = trim($r);
                if ($trimmed !== '') {
                    $allowedRoles[] = $trimmed;
                }
            }
        }

        // 3. Cek apakah role pengguna ada di daftar role yang diizinkan
        if (!in_array($user->role, $allowedRoles)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini.'
                ], 403);
            }

            // Keamanan Khusus Siswa & Orang Tua: Dilarang keras mengakses halaman web internal waka
            if (in_array($user->role, ['siswa', 'orang_tua'])) {
                Auth::logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect()->route('login')->withErrors([
                    'username' => 'Akses ditolak! Akun ' . ($user->role === 'orang_tua' ? 'Orang Tua' : 'Siswa') . ' hanya dapat digunakan melalui aplikasi mobile.',
                ]);
            }

            abort(403, 'Akses ditolak. Halaman ini hanya untuk pengguna dengan hak akses: ' . implode(', ', $allowedRoles));
        }

        return $next($request);
    }
}
