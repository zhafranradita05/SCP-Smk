<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SCP (School Class Progress)</title>
    <!-- Favicon SCP -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#EFF6FF',
                            100: '#DBEAFE',
                            600: '#2563EB',
                            700: '#1D4ED8'
                        }
                    },
                    fontFamily: {
                        heading: ['Plus Jakarta Sans', 'sans-serif'],
                        body: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 bg-slate-50 text-slate-900 antialiased">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl shadow-slate-200/50 p-8 sm:p-10 border border-slate-200/90">
        <div class="mb-4">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-blue-600 font-semibold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>← Kembali ke Halaman Utama</span>
            </a>
        </div>

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="w-24 h-24 object-contain drop-shadow-md">
            </div>
            <h1 class="text-2xl font-heading font-extrabold text-slate-900 tracking-tight">Portal Akademik SCP</h1>
            <p class="text-xs text-slate-500 mt-1.5">Masuk untuk mengelola sistem monitoring kelas & jurnal</p>
        </div>

        @if ($errors->any())
            <div class="bg-rose-50 text-rose-800 p-3.5 rounded-xl text-xs mb-5 border border-rose-200 flex items-center space-x-2">
                <svg class="w-4 h-4 flex-shrink-0 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if (session('warning') || request('status') === 'session_expired')
            <div class="bg-amber-50 text-amber-800 p-3.5 rounded-xl text-xs mb-5 border border-amber-200 flex items-center space-x-2">
                <svg class="w-4 h-4 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ session('warning') ?? 'Sesi Anda telah berakhir karena tidak aktif selama 10 menit. Silakan login kembali.' }}</span>
            </div>
        @endif

        @if (session('status'))
            <div class="bg-emerald-50 text-emerald-800 p-3.5 rounded-xl text-xs mb-5 border border-emerald-200 flex items-center space-x-2">
                <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username</label>
                <input type="text" name="username" value="{{ old('username') }}" required 
                    class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder:text-slate-400"
                    placeholder="Masukkan username Anda">
            </div>

            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Password</label>
                    <a href="{{ url('/forgot-password') }}" class="text-xs text-blue-600 hover:text-blue-700 font-semibold transition">Lupa Password?</a>
                </div>
                <input type="password" name="password" required 
                    class="w-full px-4 py-3 rounded-xl bg-white border border-slate-200 text-slate-900 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition placeholder:text-slate-400"
                    placeholder="••••••••">
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-xl transition shadow-xs hover:shadow-md mt-2 text-sm">
                Masuk ke Dashboard
            </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 flex flex-col items-center gap-3">
            <a href="{{ route('app.download') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Unduh Aplikasi Mobile (.APK)</span>
            </a>

            <a href="{{ url('/') }}" class="text-xs text-slate-500 hover:text-blue-600 font-medium transition flex items-center gap-1">
                <span>← Kembali ke Halaman Utama</span>
            </a>
        </div>

        <div class="mt-4 text-center text-[11px] text-slate-400">
            &copy; {{ date('Y') }} School Class Progress. Seluruh hak dilindungi.
        </div>
    </div>

</body>
</html>
