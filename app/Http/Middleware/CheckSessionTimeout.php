<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     * Sesi aktif Waka Kurikulum otomatis berakhir setelah 10 menit (600 detik) tidak ada aktivitas.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Timeout aktif khusus untuk role waka_kurikulum
            if ($user->role === 'waka_kurikulum') {
                $maxIdleSeconds = 600; // 10 menit (600 detik)
                $lastActivity = session('waka_last_activity_time');

                if ($lastActivity && (time() - $lastActivity) > $maxIdleSeconds) {
                    Auth::logout();
                    if ($request->hasSession()) {
                        $request->session()->invalidate();
                        $request->session()->regenerateToken();
                    }

                    if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Sesi Anda telah berakhir karena tidak aktif selama 10 menit.',
                            'session_expired' => true,
                            'redirect' => route('login', ['status' => 'session_expired'])
                        ], 401);
                    }

                    return redirect()->route('login', ['status' => 'session_expired'])
                        ->with('warning', 'Sesi Anda telah berakhir karena tidak aktif selama 10 menit. Silakan login kembali.');
                }

                // Perbarui timestamp aktivitas sesi waka
                session(['waka_last_activity_time' => time()]);
            }
        }

        return $next($request);
    }
}

