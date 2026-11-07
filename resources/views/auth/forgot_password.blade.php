<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SCP</title>
    <!-- Favicon SCP -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Inter', sans-serif; } 
        h1, h2, h3, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center p-4 bg-slate-50 text-slate-900 antialiased">

    <div class="bg-white p-8 sm:p-10 rounded-3xl shadow-xl shadow-slate-200/50 w-full max-w-md border border-slate-200/90 transition-all">
        
        <!-- Header & Logo -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center mb-3">
                <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="w-16 h-16 object-contain drop-shadow-md">
            </div>
            <h2 class="text-2xl font-heading font-extrabold text-slate-900 tracking-tight">
                {{ $step === 2 ? 'Verifikasi Kode OTP' : 'Lupa Password' }}
            </h2>
            <p class="text-slate-500 text-xs mt-1.5 max-w-xs mx-auto">
                @if($step === 2)
                    Masukkan 6-digit kode OTP dari Gmail Anda serta kata sandi baru.
                @else
                    Masukkan email terdaftar Anda untuk menerima kode OTP verifikasi melalui Gmail.
                @endif
            </p>
        </div>

        <!-- Alert Notifikasi Sukses -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 p-3.5 mb-5 rounded-2xl flex items-start space-x-2.5 text-xs">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <div class="font-medium leading-relaxed">{!! session('success') !!}</div>
            </div>
        @endif

        <!-- Alert Notifikasi Error -->
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 mb-5 rounded-2xl flex items-start space-x-2.5 text-xs">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-600 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <div class="font-semibold leading-relaxed">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 mb-5 rounded-2xl text-xs space-y-1">
                @foreach ($errors->all() as $err)
                    <div class="flex items-center space-x-2">
                        <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>
                        <span>{{ $err }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- ======================================================== -->
        <!-- STEP 1: MASUKKAN EMAIL UNTUK MINTA KODE OTP              -->
        <!-- ======================================================== -->
        @if($step === 1)
            <form action="{{ route('password.send_otp') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Email Terdaftar di Database
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none transition placeholder:text-slate-400" 
                            placeholder="nama@sekolah.com">
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl transition shadow-xs text-sm mt-3 flex items-center justify-center space-x-2">
                    <span>Kirim Kode OTP ke Gmail</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </form>

        <!-- ======================================================== -->
        <!-- STEP 2: VERIFIKASI KODE OTP & GANTI PASSWORD BARU        -->
        <!-- ======================================================== -->
        @else
            <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
                @csrf
                
                <!-- Email Terpilih (Readonly) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Email Tujuan</label>
                        <a href="{{ route('password.request') }}" class="text-[11px] text-blue-600 hover:underline font-semibold">Ganti Email</a>
                    </div>
                    <input type="email" name="email" value="{{ old('email', $email) }}" readonly
                        class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-600 text-sm font-medium cursor-not-allowed">
                </div>

                <!-- Input Kode OTP -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Kode OTP 6 Digit
                    </label>
                    <input type="text" name="otp" value="{{ old('otp') }}" required maxlength="6" inputmode="numeric" autocomplete="one-time-code" autofocus
                        class="w-full px-4 py-3 rounded-xl border-2 border-amber-300 bg-amber-50/40 text-slate-900 text-center font-mono font-bold text-xl tracking-[0.4em] focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition placeholder:text-slate-300 placeholder:tracking-widest" 
                        placeholder="••••••">
                    <p class="text-[11px] text-slate-400 mt-1 text-center">Buka Gmail Anda dan masukkan 6 digit angka yang diterima.</p>
                </div>

                <!-- Password Baru -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password Baru</label>
                    <input type="password" name="new_password" required minlength="6"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition placeholder:text-slate-400" 
                        placeholder="Minimal 6 karakter">
                </div>

                <!-- Konfirmasi Password Baru -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ulangi Password Baru</label>
                    <input type="password" name="new_password_confirmation" required minlength="6"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-white text-slate-900 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition placeholder:text-slate-400" 
                        placeholder="Ketik ulang password baru">
                </div>

                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl transition shadow-xs text-sm mt-3 flex items-center justify-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Verifikasi & Simpan Password</span>
                </button>
            </form>

            <!-- Form Kirim Ulang OTP -->
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Belum menerima kode OTP?</span>
                <form action="{{ route('password.send_otp') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="email" value="{{ $email }}">
                    <button type="submit" class="font-bold text-blue-600 hover:text-blue-800 transition hover:underline">
                        Kirim Ulang OTP
                    </button>
                </form>
            </div>
        @endif

        <div class="mt-6 text-center pt-4 border-t border-slate-100">
            <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-blue-600 font-semibold transition inline-flex items-center space-x-1">
                <span>&larr;</span>
                <span>Kembali ke halaman Login</span>
            </a>
        </div>
    </div>

</body>
</html>
