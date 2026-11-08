<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCP - School Class Progress | Monitoring Jurnal & Presensi Akademik</title>

    <!-- Favicon SCP -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#EFF6FF',
                            100: '#DBEAFE',
                            200: '#BFDBFE',
                            500: '#3B82F6',
                            600: '#2563EB',
                            700: '#1D4ED8',
                            900: '#1E3A8A'
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

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased selection:bg-blue-600 selection:text-white">

    <!-- 1. TOP NAVBAR -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-18 py-3">
                
                <!-- Logo & Brand -->
                <a href="{{ url('/') }}" class="flex items-center space-x-3 group">
                    <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="w-10 h-10 object-contain drop-shadow-sm group-hover:scale-105 transition-transform">
                    <div>
                        <div class="flex items-center space-x-1.5">
                            <span class="font-heading font-extrabold text-slate-900 text-lg tracking-tight group-hover:text-blue-600 transition">SCP</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100">Portal</span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">School Class Progress</p>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-600">
                    <a href="#fitur" class="hover:text-blue-600 transition">Fitur Utama</a>
                    <a href="#putaran" class="hover:text-blue-600 transition">Sistem 2 Putaran</a>
                    <a href="#alur" class="hover:text-blue-600 transition">Alur Kerja</a>
                    <a href="#download-app" class="hover:text-blue-600 transition flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Aplikasi Mobile</span>
                    </a>
                </nav>

                <!-- Action Buttons -->
                <div class="flex items-center space-x-3">
                    <a href="#download-app" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition shadow-2xs">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Download APK</span>
                    </a>
                    
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 shadow-sm hover:shadow transition">
                        <span>Portal Login Waka</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

            </div>
        </div>
    </header>

    <!-- 2. HERO SECTION -->
    <section class="relative pt-12 pb-20 md:pt-20 md:pb-28 overflow-hidden bg-gradient-to-b from-white via-slate-50 to-slate-100">
        <!-- Background Glowing Orbs -->
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-gradient-to-r from-blue-100/60 via-indigo-100/40 to-teal-100/50 rounded-full blur-3xl pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto">
                
                <!-- Badge Pill -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-semibold mb-6 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping"></span>
                    <span>Sistem Monitoring Akademik & Jurnal SMK Terpadu</span>
                </div>

                <!-- Main Headline -->
                <h1 class="text-3xl sm:text-5xl md:text-6xl font-heading font-extrabold text-slate-900 tracking-tight leading-[1.15] mb-6">
                    Monitoring Jurnal & Presensi Kelas <span class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Real-Time & Otomatis</span>
                </h1>

                <!-- Subtitle -->
                <p class="text-base sm:text-lg text-slate-600 leading-relaxed mb-8 font-normal">
                    Platform terintegrasi yang memudahkan <strong>Waka Kurikulum</strong>, <strong>Guru Pengajar</strong>, <strong>Siswa</strong>, dan <strong>Orang Tua</strong> dalam mengelola siklus pembelajaran 2 mingguan, transparansi materi jurnal, dan kehadiran kelas.
                </p>

                <!-- Dual CTA Buttons -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 mb-12">
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md hover:shadow-lg transition">
                        <span>Masuk ke Portal Waka</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </a>

                    <a href="#download-app" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl text-sm font-bold bg-white hover:bg-slate-50 text-slate-800 border border-slate-200/90 shadow-2xs hover:border-slate-300 transition">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Unduh Aplikasi Mobile (.APK)</span>
                    </a>
                </div>

                <!-- Trust Micro-Badges -->
                <div class="pt-6 border-t border-slate-200/70 grid grid-cols-2 sm:grid-cols-4 gap-4 text-left">
                    <div class="p-3 bg-white/70 rounded-xl border border-slate-200/60 shadow-2xs">
                        <span class="block text-xs font-bold text-slate-800">100% Real-time</span>
                        <span class="text-[11px] text-slate-500">Jurnal terisi langsung dari kelas</span>
                    </div>
                    <div class="p-3 bg-white/70 rounded-xl border border-slate-200/60 shadow-2xs">
                        <span class="block text-xs font-bold text-slate-800">Siklus 2 Minggu</span>
                        <span class="text-[11px] text-slate-500">Putaran berganti otomatis</span>
                    </div>
                    <div class="p-3 bg-white/70 rounded-xl border border-slate-200/60 shadow-2xs">
                        <span class="block text-xs font-bold text-slate-800">Multi-Role Terpadu</span>
                        <span class="text-[11px] text-slate-500">Waka, Guru, Siswa, & Wali</span>
                    </div>
                    <div class="p-3 bg-white/70 rounded-xl border border-slate-200/60 shadow-2xs">
                        <span class="block text-xs font-bold text-slate-800">Anti-Tabrakan</span>
                        <span class="text-[11px] text-slate-500">Validasi jadwal & ruang cerdas</span>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 3. FITUR UTAMA SISTEM -->
    <section id="fitur" class="py-16 md:py-24 bg-white border-y border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-100">
                    Fitur Unggulan
                </span>
                <h2 class="text-2xl sm:text-4xl font-heading font-extrabold text-slate-900 mt-3 tracking-tight">
                    Dirancang Khusus untuk Kebutuhan Sekolah & SMK
                </h2>
                <p class="text-xs sm:text-sm text-slate-500 mt-2">
                    Solusi terpadu menjawab tantangan administrasi jadwal blok, kehadiran siswa, dan pelaporan jurnal.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                
                <!-- Fitur 1: Portal Waka Kurikulum -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/90 hover:border-blue-300 hover:shadow-md transition group">
                    <div class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Control Center Waka</h3>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Pantau keterisian jurnal seluruh guru hari ini, import jadwal bebas tabrakan dari FET Timetable atau Excel, serta kelola kenaikan kelas dan cetak rekapitulasi semester.
                    </p>
                    <a href="{{ route('login') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                        <span>Akses Portal Waka</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <!-- Fitur 2: Sistem Putaran 2 Minggu -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/90 hover:border-emerald-300 hover:shadow-md transition group">
                    <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Otomatisasi Siklus 2 Minggu</h3>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Jadwal otomatis berganti antara Putaran 1 (Produktif) dan Putaran 2 (Normatif & Adaptif) setiap 14 hari secara presisi. Waka tidak perlu repot klik tombol ganti setiap minggu.
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                        ✓ Jadwal Terkunci Akurat
                    </span>
                </div>

                <!-- Fitur 3: Aplikasi Mobile Guru -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/90 hover:border-indigo-300 hover:shadow-md transition group">
                    <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Aplikasi Mobile Guru</h3>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Guru membuka jadwal mengajar harian langsung dari genggaman, menginput materi jurnal pembelajaran, mengunggah foto kegiatan kelas, dan menandai presensi siswa.
                    </p>
                    <a href="#download-app" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1">
                        <span>Unduh Aplikasi Mobile</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <!-- Fitur 4: Transparansi Siswa & Wali -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/90 hover:border-amber-300 hover:shadow-md transition group">
                    <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center mb-5 shadow-xs group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h3 class="text-base font-heading font-bold text-slate-900 mb-2">Transparansi Siswa & Wali</h3>
                    <p class="text-xs text-slate-600 leading-relaxed mb-4">
                        Siswa mengetahui riwayat kehadiran dan materi yang dipelajari. Orang tua dapat memantau status presensi anak secara terbuka untuk mencegah siswa bolos atau absen tanpa keterangan.
                    </p>
                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        ✓ Presensi Akurat & Jujur
                    </span>
                </div>

            </div>

        </div>
    </section>

    <!-- 4. SISTEM 2 PUTARAN PENJELASAN -->
    <section id="putaran" class="py-16 md:py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-3xl p-8 sm:p-12 text-white shadow-xl relative overflow-hidden">
                <div class="max-w-3xl">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-400/30">
                        Model Penjadwalan Blok SMK
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-heading font-extrabold mt-4 mb-4 tracking-tight">
                        Bagaimana Sistem Siklus 2 Putaran Bekerja?
                    </h2>
                    <p class="text-sm text-slate-300 leading-relaxed mb-8">
                        Sekolah Menengah Kejuruan sering kali menggunakan kurikulum blok di mana siswa bergantian antara materi kejuruan praktik dan teori normatif-adaptif. SCP mengelola rotasi ini secara otomatis tanpa membuat data jadwal bertabrakan.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-heading font-bold text-emerald-400 text-lg">Putaran 1</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300">Blok 1 (14 Hari)</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Dikhususkan untuk mata pelajaran <strong>Produktif / Konsentrasi Keahlian</strong> dan sesi praktikum di laboratorium atau bengkel sekolah.
                            </p>
                        </div>

                        <div class="p-5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-heading font-bold text-blue-300 text-lg">Putaran 2</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-200">Blok 2 (14 Hari)</span>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Dikhususkan untuk mata pelajaran <strong>Normatif & Adaptif</strong> (Pendidikan Agama, PPKn, Bahasa, Matematika, IPA, dan Sejarah).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. DOWNLOAD APLIKASI MOBILE SECTION -->
    <section id="download-app" class="py-16 md:py-24 bg-white border-t border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gradient-to-br from-emerald-50 via-teal-50 to-blue-50 border border-emerald-200/90 rounded-3xl p-8 sm:p-12 shadow-sm">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    
                    <div class="lg:col-span-7">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold mb-4">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            <span>Aplikasi Android SCP Mobile</span>
                        </div>
                        <h2 class="text-2xl sm:text-4xl font-heading font-extrabold text-slate-900 tracking-tight mb-4">
                            Unduh Aplikasi Mobile untuk Guru & Siswa
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
                            Akses jadwal dan pengisian jurnal mengajar secara cepat langsung melalui smartphone Android Anda. Tersedia untuk akun <strong>Guru</strong>, <strong>Wali Kelas</strong>, <strong>Guru BK</strong>, <strong>Siswa</strong>, dan <strong>Orang Tua</strong>.
                        </p>

                        <!-- Download Action -->
                        <div class="flex flex-wrap items-center gap-3 mb-6">
                            <a href="{{ route('app.download') }}" class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-xl text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-md hover:shadow-lg transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>Download File APK (Android)</span>
                            </a>
                            <span class="text-xs text-slate-500 font-medium">Format: .APK • Versi 1.0 (Produksi)</span>
                        </div>

                        <!-- 3 Langkah Mudah -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-6 border-t border-emerald-200/80 text-xs text-slate-700">
                            <div>
                                <span class="font-bold text-emerald-900 block mb-0.5">1. Unduh APK</span>
                                <span class="text-slate-500 text-[11px]">Klik tombol unduh di atas untuk menyimpan file instalasi.</span>
                            </div>
                            <div>
                                <span class="font-bold text-emerald-900 block mb-0.5">2. Izinkan Instalasi</span>
                                <span class="text-slate-500 text-[11px]">Buka file dan izinkan instalasi dari sumber ini di HP Anda.</span>
                            </div>
                            <div>
                                <span class="font-bold text-emerald-900 block mb-0.5">3. Masuk Akun</span>
                                <span class="text-slate-500 text-[11px]">Login menggunakan username & password yang sudah terdaftar.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Card / Badge -->
                    <div class="lg:col-span-5 flex justify-center">
                        <div class="w-full max-w-sm bg-white rounded-2xl p-6 border border-emerald-200/80 shadow-md text-center">
                            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-200">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            </div>
                            <h3 class="font-heading font-extrabold text-slate-900 text-lg mb-1">SCP Presensi App</h3>
                            <p class="text-xs text-slate-500 mb-4">Aplikasi Mobile Resmi Presensi & Jurnal Kelas</p>
                            
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70 text-xs text-left space-y-2 mb-4">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Target Sistem:</span>
                                    <span class="font-bold text-slate-800">Android 8.0+</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Koneksi API:</span>
                                    <span class="font-bold text-emerald-600">Terhubung (HTTPS)</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Domain API:</span>
                                    <span class="font-mono text-[11px] text-slate-700">classprogress.web.id</span>
                                </div>
                            </div>

                            <a href="{{ route('app.download') }}" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition block shadow-xs">
                                Unduh Sekarang (.APK)
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- 6. FOOTER -->
    <footer class="bg-slate-900 text-slate-400 py-12 border-t border-slate-800 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="w-8 h-8 object-contain">
                    <div>
                        <span class="font-heading font-bold text-white text-sm">SCP - School Class Progress</span>
                        <p class="text-[11px] text-slate-500">Sistem Monitoring Jurnal & Presensi Kelas Berbasis Siklus 2 Mingguan</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-6">
                    <a href="#fitur" class="hover:text-white transition">Fitur Utama</a>
                    <a href="#putaran" class="hover:text-white transition">Sistem Putaran</a>
                    <a href="{{ route('app.download') }}" class="hover:text-white transition">Download APK</a>
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-500 transition">
                        Portal Login Waka
                    </a>
                </div>

            </div>

            <div class="mt-8 pt-6 border-t border-slate-800/80 text-center text-slate-500 text-[11px]">
                &copy; {{ date('Y') }} SCP (School Class Progress). Seluruh Hak Cipta Dilindungi.
            </div>
        </div>
    </footer>

</body>
</html>
