<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SCP') - Sistem Manajemen Waka</title>

    <!-- Favicon SCP -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- SweetAlert2 CDN (Pop-up Dialog Ditengah Layar) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#EFF6FF',
                            100: '#DBEAFE',
                            200: '#BFDBFE',
                            300: '#93C5FD',
                            400: '#60A5FA',
                            500: '#3B82F6',
                            600: '#2563EB',
                            700: '#1D4ED8',
                            800: '#1E40AF',
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

    <!-- Hanya 2 Font Saja: Plus Jakarta Sans & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #0F172A;
            background-color: #F8FAFC;
        }
        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        /* Custom scrollbar clean */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        /* Dropdown transition */
        .dropdown-menu {
            transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
        }
        .dropdown-menu.hidden-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: scale(0.97) translateY(-4px);
            pointer-events: none;
        }
        .dropdown-menu.active-dropdown {
            opacity: 1;
            visibility: visible;
            transform: scale(1) translateY(0);
            pointer-events: auto;
        }
    </style>
</head>
<body class="min-h-full flex flex-col antialiased">

    <!-- Top Navigation Bar (Menggantikan Sidebar Kaku AI dengan Dropdown Modern) -->
    <header class="bg-white border-b border-slate-200/90 sticky top-0 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <!-- Logo & Brand Identity -->
                <div class="flex items-center space-x-3 flex-shrink-0">
                    <a href="{{ url('/waka/dashboard') }}" class="flex items-center space-x-3 group">
                        <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="w-10 h-10 object-contain drop-shadow-sm group-hover:scale-105 transition-transform">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-heading font-extrabold text-slate-900 text-base tracking-tight group-hover:text-blue-600 transition">SCP Portal</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                    Waka
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium leading-none">School Class Progress</p>
                        </div>
                    </a>
                </div>

                <!-- Desktop Navigation with Dropdowns -->
                <nav class="hidden md:flex items-center space-x-1 flex-1 justify-center max-w-2xl">
                    <!-- Dashboard Link -->
                    <a href="{{ url('/waka/dashboard') }}" 
                       class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('waka/dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Dashboard
                    </a>

                    <!-- Dropdown: Kelola Pengguna -->
                    <div class="relative dropdown-container">
                        <button type="button" 
                                onclick="toggleDropdown('dropdown-users')"
                                class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->routeIs('waka.users.*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <span>Kelola Pengguna</span>
                            <svg class="w-4 h-4 transition-transform duration-200 dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Panel Dropdown Pengguna -->
                        <div id="dropdown-users" class="dropdown-menu hidden-dropdown absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-slate-200/90 py-1.5 z-50">
                            <div class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                Filter Peran Pengguna
                            </div>
                            <!-- Data Siswa & Sub-Menu Angkatan -->
                            <div class="px-1">
                                <a href="{{ route('waka.users.index', ['role' => 'siswa']) }}" 
                                   class="flex items-center justify-between px-2.5 py-1.5 text-sm rounded-lg text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition {{ request('role') == 'siswa' && (!request('tingkat') || request('tingkat') == 'all') ? 'bg-blue-50 text-blue-700 font-bold' : '' }}">
                                    <span class="flex items-center space-x-2">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        <span>Data Siswa</span>
                                    </span>
                                </a>

                                <!-- Sub-Menu Filter Angkatan -->
                                <div class="ml-3 pl-2.5 border-l-2 border-blue-100 my-1 space-y-0.5">
                                    <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'X']) }}" 
                                       class="flex items-center justify-between px-2 py-1 text-xs rounded-md text-slate-600 hover:text-blue-700 hover:bg-blue-50 transition {{ request('role') == 'siswa' && request('tingkat') == 'X' ? 'bg-blue-50 text-blue-700 font-bold' : '' }}">
                                        <span>• Angkatan Kelas X</span>
                                        <span class="text-[9px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-mono">10</span>
                                    </a>
                                    <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'XI']) }}" 
                                       class="flex items-center justify-between px-2 py-1 text-xs rounded-md text-slate-600 hover:text-blue-700 hover:bg-blue-50 transition {{ request('role') == 'siswa' && request('tingkat') == 'XI' ? 'bg-blue-50 text-blue-700 font-bold' : '' }}">
                                        <span>• Angkatan Kelas XI</span>
                                        <span class="text-[9px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-mono">11</span>
                                    </a>
                                    <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'XII']) }}" 
                                       class="flex items-center justify-between px-2 py-1 text-xs rounded-md text-slate-600 hover:text-blue-700 hover:bg-blue-50 transition {{ request('role') == 'siswa' && request('tingkat') == 'XII' ? 'bg-blue-50 text-blue-700 font-bold' : '' }}">
                                        <span>• Angkatan Kelas XII</span>
                                        <span class="text-[9px] bg-slate-100 text-slate-500 px-1 py-0.5 rounded font-mono">12</span>
                                    </a>
                                    <a href="{{ route('waka.promotions.index') }}" 
                                       class="flex items-center justify-between px-2 py-1 text-xs rounded-md text-emerald-700 hover:bg-emerald-50 transition {{ request()->routeIs('waka.promotions*') ? 'bg-emerald-50 text-emerald-800 font-bold' : '' }}">
                                        <span class="flex items-center space-x-1">
                                            <span>🎓 Kenaikan & Kelulusan</span>
                                        </span>
                                        <span class="text-[9px] font-bold px-1 rounded bg-emerald-100 text-emerald-700">Opsi 1</span>
                                    </a>
                                </div>
                            </div>
                            <a href="{{ route('waka.users.index', ['role' => 'orang_tua']) }}" 
                               class="flex items-center justify-between px-3.5 py-2 text-sm text-slate-700 hover:bg-amber-50 hover:text-amber-700 transition {{ request('role') == 'orang_tua' ? 'bg-amber-50/70 text-amber-700 font-bold' : '' }}">
                                <span class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>Data Orang Tua</span>
                                </span>
                            </a>
                            <a href="{{ route('waka.users.index', ['role' => 'guru']) }}" 
                               class="flex items-center justify-between px-3.5 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition {{ request('role') == 'guru' ? 'bg-blue-50/70 text-blue-700 font-bold' : '' }}">
                                <span class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    <span>Guru Pegawai</span>
                                </span>
                            </a>
                            <a href="{{ route('waka.users.index', ['role' => 'guru_bk']) }}" 
                               class="flex items-center justify-between px-3.5 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition {{ request('role') == 'guru_bk' ? 'bg-blue-50/70 text-blue-700 font-bold' : '' }}">
                                <span class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    <span>Guru BK</span>
                                </span>
                            </a>
                            <a href="{{ route('waka.users.index', ['role' => 'waka_kurikulum']) }}" 
                               class="flex items-center justify-between px-3.5 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition {{ request('role') == 'waka_kurikulum' ? 'bg-blue-50/70 text-blue-700 font-bold' : '' }}">
                                <span class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                                    <span>Waka Kurikulum</span>
                                </span>
                            </a>
                            <div class="my-1 border-t border-slate-100"></div>
                            <a href="{{ route('waka.users.index') }}" 
                               class="flex items-center px-3.5 py-2 text-sm font-semibold text-slate-800 hover:bg-blue-50 hover:text-blue-700 transition {{ request()->routeIs('waka.users.index') && !request('role') ? 'bg-blue-50/70 text-blue-700' : '' }}">
                                Semua Pengguna
                            </a>
                            <a href="{{ route('waka.users.create') }}" 
                               class="flex items-center px-3.5 py-2 text-sm font-medium text-blue-600 hover:bg-blue-50 transition">
                                + Tambah Pengguna
                            </a>
                        </div>
                    </div>

                    <!-- Dropdown: Master & Jadwal -->
                    <div class="relative dropdown-container">
                        <button type="button" 
                                onclick="toggleDropdown('dropdown-schedules')"
                                class="flex items-center space-x-1.5 px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ (request()->is('waka/master*') || request()->is('waka/schedules*') || request()->is('waka/settings*')) ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <span>Master & Jadwal</span>
                            <svg class="w-4 h-4 transition-transform duration-200 dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Panel Dropdown Jadwal -->
                        <div id="dropdown-schedules" class="dropdown-menu hidden-dropdown absolute left-0 mt-2 w-60 bg-white rounded-xl shadow-lg border border-slate-200/90 py-1.5 z-50">
                            <div class="px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                Kurikulum & Penjadwalan
                            </div>
                            <a href="{{ url('/waka/master') }}" 
                               class="flex items-center space-x-2.5 px-3.5 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition {{ request()->is('waka/master*') ? 'bg-blue-50 text-blue-700 font-bold' : '' }}">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span>Master Kelas & Ruang</span>
                            </a>
                            <a href="{{ url('/waka/schedules') }}" 
                               class="flex items-center space-x-2.5 px-3.5 py-2 text-sm text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition {{ request()->is('waka/schedules*') ? 'bg-blue-50 text-blue-700 font-bold' : '' }}">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span>Kelola Jadwal & Putaran</span>
                            </a>
                        </div>
                    </div>

                    <!-- Rekapitulasi Link -->
                    <a href="{{ url('/waka/recap') }}" 
                       class="px-3.5 py-2 rounded-lg text-sm font-semibold transition {{ request()->is('waka/recap*') ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                        Rekap Semester
                    </a>
                </nav>

                <!-- Right Side: User Menu & Mobile Trigger -->
                <div class="flex items-center space-x-3">
                    
                    <!-- Indikator Sesi Aktif Waka (Auto-Logout 10 Menit Tidak Aktif) -->
                    <div id="waka-session-badge" 
                         class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 transition-all duration-300 shadow-2xs select-none cursor-pointer"
                         title="Sesi aktif Waka. Otomatis diperbarui saat ada aktivitas mouse/keyboard/klik. Klik untuk perpanjang langsung.">
                        <span class="relative flex h-2 w-2">
                            <span id="session-ping-dot" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span id="session-dot" class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span class="hidden sm:inline text-slate-500 font-normal">Sesi:</span>
                        <span id="session-countdown-display" class="font-mono font-bold text-emerald-700">10:00</span>
                    </div>

                    <!-- Dropdown: Profil Pengguna & Keluar -->
                    <div class="relative dropdown-container">
                        <button type="button" 
                                onclick="toggleDropdown('dropdown-profile')"
                                class="flex items-center space-x-2.5 p-1.5 rounded-xl border border-slate-200/90 hover:border-blue-300 hover:bg-slate-50 transition">
                            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white font-heading font-bold text-xs flex items-center justify-center shadow-xs">
                                {{ strtoupper(substr(auth()->user()->name ?? 'W', 0, 1)) }}
                            </div>
                            <div class="hidden sm:block text-left pr-1">
                                <span class="block text-xs font-bold text-slate-800 leading-tight max-w-[120px] truncate">
                                    {{ auth()->user()->name ?? 'Waka Kurikulum' }}
                                </span>
                                <span class="block text-[10px] text-blue-600 font-medium leading-none">
                                    Kurikulum
                                </span>
                            </div>
                            <svg class="w-3.5 h-3.5 text-slate-400 dropdown-arrow transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Profile Dropdown Panel -->
                        <div id="dropdown-profile" class="dropdown-menu hidden-dropdown absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-lg border border-slate-200/90 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-xs text-slate-500">Masuk sebagai:</p>
                                <p class="text-sm font-bold text-slate-900 truncate">{{ auth()->user()->name ?? 'Pengguna' }}</p>
                                <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700">
                                    Waka Kurikulum
                                </span>
                            </div>

                            <div class="py-1">
                                <a href="{{ route('waka.profile.edit') }}" class="flex items-center space-x-2 px-4 py-2 text-xs font-semibold text-blue-700 bg-blue-50/70 hover:bg-blue-100/70 transition">
                                    <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    <span>Edit Profil Waka</span>
                                </a>
                                <a href="{{ url('/waka/dashboard') }}" class="flex items-center px-4 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <span>Dashboard Utama</span>
                                </a>
                                <a href="{{ route('waka.users.index') }}" class="flex items-center px-4 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <span>Kelola Data Pengguna</span>
                                </a>
                                <a href="{{ url('/waka/schedules') }}" class="flex items-center px-4 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <span>Pengaturan Jadwal</span>
                                </a>
                                <a href="{{ url('/') }}" target="_blank" class="flex items-center justify-between px-4 py-2 text-xs text-slate-700 hover:bg-blue-50 hover:text-blue-700 transition border-t border-slate-100">
                                    <span>Halaman Landing Page</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>

                            <div class="pt-1 border-t border-slate-100 px-2">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition flex items-center justify-between">
                                        <span>Keluar dari Sistem</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Hamburger Button -->
                    <button type="button" 
                            onclick="toggleMobileMenu()" 
                            class="md:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200">
                        <svg id="hamburger-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- Mobile Navigation Menu (Drawer/Accordion) -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-slate-200 bg-white px-4 pt-3 pb-4 space-y-2 shadow-md">
            <div class="flex items-center space-x-3 pb-2.5 mb-2 border-b border-slate-100">
                <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="w-8 h-8 object-contain drop-shadow-xs">
                <div>
                    <span class="font-heading font-bold text-slate-900 text-xs">SCP Portal Waka</span>
                    <p class="text-[10px] text-slate-500">School Class Progress</p>
                </div>
            </div>

            <!-- Sisa Waktu Sesi Aktif di Menu Mobile -->
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                <span class="font-medium text-slate-600 flex items-center space-x-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                    <span>Sisa Waktu Sesi:</span>
                </span>
                <span id="mobile-session-countdown-display" class="font-mono font-bold text-emerald-700">10:00</span>
            </div>

            <a href="{{ url('/waka/dashboard') }}" 
               class="block px-3 py-2 rounded-lg text-sm font-semibold {{ request()->is('waka/dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-100' }}">
                Dashboard
            </a>

            <a href="{{ route('waka.profile.edit') }}" 
               class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold {{ request()->routeIs('waka.profile.*') ? 'bg-blue-50 text-blue-700 font-bold' : 'text-blue-700 bg-blue-50/50 hover:bg-blue-100/60' }}">
                <span class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>Edit Profil Waka</span>
                </span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-bold">Akun</span>
            </a>

            <!-- Mobile Accordion Kelola Pengguna -->
            <details class="group">
                <summary class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 cursor-pointer list-none hover:bg-slate-100 {{ request()->routeIs('waka.users.*') ? 'bg-blue-50 text-blue-700' : '' }}">
                    <span>Kelola Pengguna</span>
                    <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </summary>
                <div class="pl-4 pr-2 py-1 space-y-1 mt-1 border-l-2 border-blue-200 ml-3">
                    <a href="{{ route('waka.users.index', ['role' => 'siswa']) }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50">Data Siswa</a>
                    <a href="{{ route('waka.users.index', ['role' => 'orang_tua']) }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-amber-700 hover:bg-amber-50">Data Orang Tua</a>
                    <a href="{{ route('waka.promotions.index') }}" class="block px-3 py-1.5 rounded-md text-xs font-semibold text-emerald-700 hover:bg-emerald-50">🎓 Kenaikan & Kelulusan</a>
                    <a href="{{ route('waka.users.index', ['role' => 'guru']) }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50">Guru Pegawai</a>
                    <a href="{{ route('waka.users.index', ['role' => 'guru_bk']) }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50">Guru BK</a>
                    <a href="{{ route('waka.users.index', ['role' => 'waka_kurikulum']) }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50">Waka Kurikulum</a>
                    <a href="{{ route('waka.users.index') }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50">Semua Pengguna</a>
                </div>
            </details>

            <!-- Mobile Accordion Master & Jadwal -->
            <details class="group">
                <summary class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-semibold text-slate-700 cursor-pointer list-none hover:bg-slate-100 {{ (request()->is('waka/master*') || request()->is('waka/schedules*')) ? 'bg-blue-50 text-blue-700' : '' }}">
                    <span>Master & Jadwal</span>
                    <svg class="w-4 h-4 text-slate-400 group-open:rotate-180 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </summary>
                <div class="pl-4 pr-2 py-1 space-y-1 mt-1 border-l-2 border-blue-200 ml-3">
                    <a href="{{ url('/waka/master') }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50">Master Kelas & Ruang</a>
                    <a href="{{ url('/waka/schedules') }}" class="block px-3 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50">Kelola Jadwal & Putaran</a>
                </div>
            </details>

            <a href="{{ url('/waka/recap') }}" 
               class="block px-3 py-2 rounded-lg text-sm font-semibold {{ request()->is('waka/recap*') ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-100' }}">
                Rekap Semester
            </a>

            <div class="pt-2 border-t border-slate-100">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-center px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition">
                        Keluar Sistem
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Sub-Header / Breadcrumb Title Bar -->
    <div class="bg-white border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-heading font-extrabold text-slate-900 tracking-tight">@yield('title')</h1>
                <p class="text-xs text-slate-500 font-medium">Sistem Monitoring Jurnal & Jadwal Akademik</p>
            </div>
            <div class="flex items-center space-x-2">
                @yield('header_actions')
            </div>
        </div>
    </div>

    <!-- Main Content Canvas (Spacious, Airy, Non-Stiff) -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        @yield('content')
    </main>

    <!-- Footer Modern -->
    <footer class="bg-white border-t border-slate-200/80 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
            <span>&copy; {{ date('Y') }} <strong>School Class Progress (SCP)</strong>. Hak Cipta Dilindungi.</span>
            <div class="flex items-center space-x-3 text-blue-600 font-medium">
                <span>Panel Waka Kurikulum</span>
                <span>•</span>
                <span>Versi 1.0</span>
            </div>
        </div>
    </footer>

    <!-- Interactive Dropdowns Logic (Click + Outside Click + Clean Animation) -->
    <script>
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const isOpened = dropdown.classList.contains('active-dropdown');
            
            // Close all open dropdowns first
            closeAllDropdowns();

            if (!isOpened) {
                dropdown.classList.remove('hidden-dropdown');
                dropdown.classList.add('active-dropdown');
                // Rotate arrow if present
                const parent = dropdown.closest('.dropdown-container');
                if (parent) {
                    const arrow = parent.querySelector('.dropdown-arrow');
                    if (arrow) arrow.style.transform = 'rotate(180deg)';
                }
            }
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('active-dropdown');
                menu.classList.add('hidden-dropdown');
            });
            document.querySelectorAll('.dropdown-arrow').forEach(arrow => {
                arrow.style.transform = '';
            });
        }

        // Close on outside click
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown-container')) {
                closeAllDropdowns();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAllDropdowns();
            }
        });

        // Mobile drawer toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>

    <!-- ============================================================== -->
    <!-- MODAL POP-UP DI TENGAH LAYAR (NATIVE TAILWIND & SWEETALERT2)    -->
    <!-- ============================================================== -->
    <div id="scp-center-modal-overlay" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 hidden opacity-0"
         style="backdrop-filter: blur(4px);">
        <div id="scp-center-modal-box" 
             class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-sm sm:max-w-md w-full p-6 sm:p-7 text-center transform scale-95 transition-all duration-200">
            
            <!-- Animated Icon Container -->
            <div id="scp-modal-icon-bg" class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-rose-50 text-rose-600 ring-8 ring-rose-50/50">
                <svg id="scp-modal-icon-danger" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <svg id="scp-modal-icon-success" class="w-8 h-8 hidden text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg id="scp-modal-icon-info" class="w-8 h-8 hidden text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>

            <!-- Title & Message -->
            <h3 id="scp-modal-title" class="text-lg font-heading font-extrabold text-slate-900 mb-2 tracking-tight">Konfirmasi Tindakan</h3>
            <div id="scp-modal-message" class="text-sm text-slate-600 mb-6 leading-relaxed font-normal">Apakah Anda yakin ingin melanjutkan tindakan ini?</div>

            <!-- Buttons -->
            <div class="flex items-center justify-center gap-3">
                <button id="scp-modal-btn-cancel" type="button" class="w-full px-5 py-2.5 rounded-xl border border-slate-300/80 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-xs">
                    Batal
                </button>
                <button id="scp-modal-btn-confirm" type="button" class="w-full px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-xs shadow-rose-200">
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <script>
        // --- 1. POPUP KONFIRMASI HAPUS DI TENGAH LAYAR ---
        window.confirmDelete = function(formOrCallback, message, title = 'Konfirmasi Hapus') {
            const defaultMsg = message || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    html: defaultMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48', // Tailwind rose-600
                    cancelButtonColor: '#64748b',  // Tailwind slate-500
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'rounded-2xl p-6 sm:p-7 shadow-2xl border border-slate-100',
                        title: 'font-heading text-lg font-extrabold text-slate-900',
                        htmlContainer: 'text-sm text-slate-600',
                        confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs',
                        cancelButton: 'px-5 py-2.5 rounded-xl font-semibold text-xs shadow-xs',
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (typeof formOrCallback === 'function') {
                            formOrCallback();
                        } else if (formOrCallback && typeof formOrCallback.submit === 'function') {
                            formOrCallback.submit();
                        }
                    }
                });
                return false;
            }

            // Fallback: Modal Tailwind Native
            openCustomCenterModal({
                title: title,
                message: defaultMsg,
                type: 'danger',
                confirmText: 'Ya, Hapus!',
                cancelText: 'Batal',
                showCancel: true,
                onConfirm: () => {
                    if (typeof formOrCallback === 'function') {
                        formOrCallback();
                    } else if (formOrCallback && typeof formOrCallback.submit === 'function') {
                        formOrCallback.submit();
                    }
                }
            });
            return false;
        };

        // --- 2. POPUP KONFIRMASI UMUM DI TENGAH LAYAR ---
        window.confirmAction = function(onConfirm, message, options = {}) {
            const title = options.title || 'Konfirmasi Tindakan';
            const confirmText = options.confirmText || 'Ya, Lanjutkan';
            const cancelText = options.cancelText || 'Batal';
            const isDanger = options.isDanger || false;
            const icon = options.icon || (isDanger ? 'warning' : 'question');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    html: message,
                    icon: icon,
                    showCancelButton: true,
                    confirmButtonColor: isDanger ? '#e11d48' : '#2563eb',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: confirmText,
                    cancelButtonText: cancelText,
                    reverseButtons: true,
                    focusCancel: true,
                    customClass: {
                        popup: 'rounded-2xl p-6 sm:p-7 shadow-2xl border border-slate-100',
                        title: 'font-heading text-lg font-extrabold text-slate-900',
                        htmlContainer: 'text-sm text-slate-600',
                        confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs',
                        cancelButton: 'px-5 py-2.5 rounded-xl font-semibold text-xs shadow-xs',
                    }
                }).then((result) => {
                    if (result.isConfirmed && typeof onConfirm === 'function') {
                        onConfirm();
                    }
                });
                return;
            }

            // Fallback Native Modal
            openCustomCenterModal({
                title: title,
                message: message,
                type: isDanger ? 'danger' : 'info',
                confirmText: confirmText,
                cancelText: cancelText,
                showCancel: true,
                onConfirm: onConfirm
            });
        };

        // --- 3. POPUP SUKSES / ERROR (PENGGANTI NOTIFIKASI BANNER) ---
        window.showSuccessModal = function(message, title = 'Berhasil!') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: title,
                    html: message,
                    confirmButtonColor: '#2563eb', // blue-600
                    confirmButtonText: 'Selesai',
                    timer: 3500,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'rounded-2xl p-6 sm:p-7 shadow-2xl border border-slate-100',
                        title: 'font-heading text-lg font-extrabold text-slate-900',
                        htmlContainer: 'text-sm text-slate-600',
                        confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs',
                    }
                });
                return;
            }

            openCustomCenterModal({
                title: title,
                message: message,
                type: 'success',
                confirmText: 'Selesai',
                showCancel: false
            });
        };

        window.showErrorModal = function(message, title = 'Perhatian / Terjadi Kesalahan') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: title,
                    html: message,
                    confirmButtonColor: '#e11d48', // rose-600
                    confirmButtonText: 'Tutup',
                    customClass: {
                        popup: 'rounded-2xl p-6 sm:p-7 shadow-2xl border border-slate-100',
                        title: 'font-heading text-lg font-extrabold text-slate-900',
                        htmlContainer: 'text-sm text-slate-600',
                        confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs',
                    }
                });
                return;
            }

            openCustomCenterModal({
                title: title,
                message: message,
                type: 'danger',
                confirmText: 'Tutup',
                showCancel: false
            });
        };

        window.showAlertModal = function(message, type = 'info', title = 'Informasi') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type,
                    title: title,
                    html: message,
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Mengerti',
                    customClass: {
                        popup: 'rounded-2xl p-6 sm:p-7 shadow-2xl border border-slate-100',
                        title: 'font-heading text-lg font-extrabold text-slate-900',
                        htmlContainer: 'text-sm text-slate-600',
                        confirmButton: 'px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs',
                    }
                });
                return;
            }

            openCustomCenterModal({
                title: title,
                message: message,
                type: type === 'warning' ? 'danger' : 'info',
                confirmText: 'Mengerti',
                showCancel: false
            });
        };

        // Ganti alert bawaan browser secara global
        window.alert = function(msg) {
            window.showAlertModal(msg);
        };

        // --- 4. LOGIKA MODAL POP-UP TAILWIND NATIVE (FALLBACK OFFLINE) ---
        function openCustomCenterModal(config) {
            const overlay = document.getElementById('scp-center-modal-overlay');
            const box = document.getElementById('scp-center-modal-box');
            const titleEl = document.getElementById('scp-modal-title');
            const msgEl = document.getElementById('scp-modal-message');
            const btnCancel = document.getElementById('scp-modal-btn-cancel');
            const btnConfirm = document.getElementById('scp-modal-btn-confirm');
            const iconBg = document.getElementById('scp-modal-icon-bg');
            const iconDanger = document.getElementById('scp-modal-icon-danger');
            const iconSuccess = document.getElementById('scp-modal-icon-success');
            const iconInfo = document.getElementById('scp-modal-icon-info');

            if (!overlay || !box) return;

            titleEl.textContent = config.title || 'Pemberitahuan';
            msgEl.innerHTML = config.message || '';

            iconDanger.classList.add('hidden');
            iconSuccess.classList.add('hidden');
            iconInfo.classList.add('hidden');

            if (config.type === 'success') {
                iconSuccess.classList.remove('hidden');
                iconBg.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50';
                btnConfirm.className = 'w-full px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs';
            } else if (config.type === 'danger') {
                iconDanger.classList.remove('hidden');
                iconBg.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-rose-50 text-rose-600 ring-8 ring-rose-50/50';
                btnConfirm.className = 'w-full px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-xs shadow-rose-200';
            } else {
                iconInfo.classList.remove('hidden');
                iconBg.className = 'w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center bg-blue-50 text-blue-600 ring-8 ring-blue-50/50';
                btnConfirm.className = 'w-full px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition shadow-xs';
            }

            btnConfirm.textContent = config.confirmText || 'OK';
            btnCancel.textContent = config.cancelText || 'Batal';

            if (config.showCancel === false) {
                btnCancel.classList.add('hidden');
            } else {
                btnCancel.classList.remove('hidden');
            }

            function closeModal() {
                overlay.classList.add('opacity-0');
                box.classList.remove('scale-100');
                box.classList.add('scale-95');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                }, 200);
            }

            btnCancel.onclick = function() {
                closeModal();
            };

            btnConfirm.onclick = function() {
                closeModal();
                if (typeof config.onConfirm === 'function') {
                    config.onConfirm();
                }
            };

            overlay.onclick = function(e) {
                if (e.target === overlay) closeModal();
            };

            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                box.classList.remove('scale-95');
                box.classList.add('scale-100');
            }, 10);
        }

        // --- 5. INTERSEPTOR OTOMATIS: UBAH SEMUA confirm() DI FORM MENJADI POP-UP TENGAH LAYAR ---
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.dataset.confirmed === 'true') {
                return;
            }

            const onsubmitAttr = form.getAttribute('onsubmit');
            if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
                e.preventDefault();
                e.stopImmediatePropagation();

                let message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
                const match = onsubmitAttr.match(/confirm\(\s*['"`]([\s\S]*?)['"`]\s*\)/);
                if (match && match[1]) {
                    message = match[1];
                }

                const isDelete = form.querySelector('input[name="_method"][value="DELETE"]') !== null 
                              || message.toLowerCase().includes('hapus');

                if (isDelete) {
                    confirmDelete(() => {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }, message);
                } else {
                    confirmAction(() => {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }, message);
                }
            }
        }, true);
    </script>

    <!-- Trigger Notifikasi Flash Laravel (Muncul di Tengah Layar) -->
    @if(session('success'))
        <div id="flash-success-data" data-message="{{ session('success') }}" class="hidden"></div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const el = document.getElementById('flash-success-data');
                if (el && el.dataset.message) {
                    showSuccessModal(el.dataset.message);
                }
            });
        </script>
    @endif

    @if(session('error'))
        <div id="flash-error-data" data-message="{{ session('error') }}" class="hidden"></div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const el = document.getElementById('flash-error-data');
                if (el && el.dataset.message) {
                    showErrorModal(el.dataset.message);
                }
            });
        </script>
    @endif

    @if(session('warning'))
        <div id="flash-warning-data" data-message="{{ session('warning') }}" class="hidden"></div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const el = document.getElementById('flash-warning-data');
                if (el && el.dataset.message) {
                    Swal.fire({
                        title: 'Perhatian',
                        text: el.dataset.message,
                        icon: 'warning',
                        confirmButtonText: 'Tutup',
                        confirmButtonColor: '#2563EB',
                    });
                }
            });
        </script>
    @endif

    <!-- MANAJEMEN SESI AKTIF WAKA (OTOMATIS LOGOUT JIKA 10 MENIT TIDAK AKTIF) -->
    <script>
        (function() {
            const IDLE_LIMIT = 600; // 10 menit (600 detik)
            let secondsRemaining = IDLE_LIMIT;
            let lastHeartbeat = Date.now();
            let isLoggedOut = false;
            let timerInterval = null;

            const badge = document.getElementById('waka-session-badge');
            const countdownDisplay = document.getElementById('session-countdown-display');
            const mobileCountdownDisplay = document.getElementById('mobile-session-countdown-display');
            const pingDot = document.getElementById('session-ping-dot');
            const dot = document.getElementById('session-dot');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            function formatTime(seconds) {
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                return `${m}:${s}`;
            }

            function updateDisplay() {
                const formatted = formatTime(secondsRemaining);
                if (countdownDisplay) countdownDisplay.textContent = formatted;
                if (mobileCountdownDisplay) mobileCountdownDisplay.textContent = formatted;

                if (!badge) return;

                if (secondsRemaining <= 60) {
                    // <= 1 menit: status Kritis (Merah Berkedip)
                    badge.className = "flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-300 transition-all duration-300 shadow-xs animate-pulse select-none cursor-pointer";
                    if (countdownDisplay) countdownDisplay.className = "font-mono font-extrabold text-rose-700";
                    if (dot) dot.className = "relative inline-flex rounded-full h-2 w-2 bg-rose-600";
                    if (pingDot) pingDot.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75";
                } else if (secondsRemaining <= 180) {
                    // <= 3 menit: status Peringatan (Kuning/Amber)
                    badge.className = "flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-300 transition-all duration-300 shadow-2xs select-none cursor-pointer";
                    if (countdownDisplay) countdownDisplay.className = "font-mono font-bold text-amber-700";
                    if (dot) dot.className = "relative inline-flex rounded-full h-2 w-2 bg-amber-500";
                    if (pingDot) pingDot.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75";
                } else {
                    // Normal: > 3 menit (Hijau Emerald)
                    badge.className = "flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 transition-all duration-300 shadow-2xs select-none cursor-pointer";
                    if (countdownDisplay) countdownDisplay.className = "font-mono font-bold text-emerald-700";
                    if (dot) dot.className = "relative inline-flex rounded-full h-2 w-2 bg-emerald-500";
                    if (pingDot) pingDot.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75";
                }
            }

            function sendHeartbeat() {
                if (!csrfToken || isLoggedOut) return;
                fetch("{{ route('waka.session.keepalive') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ active: true })
                }).then(response => {
                    if (response.status === 401 || response.status === 419) {
                        triggerSessionExpire();
                    }
                }).catch(() => {});
            }

            function resetIdleTimer() {
                if (isLoggedOut) return;
                secondsRemaining = IDLE_LIMIT;
                updateDisplay();

                // Perbarui sesi di backend jika telah beraktivitas lebih dari 2 menit sejak heartbeat sebelumnya
                const now = Date.now();
                if (now - lastHeartbeat > 120000) {
                    lastHeartbeat = now;
                    sendHeartbeat();
                }
            }

            function triggerSessionExpire() {
                if (isLoggedOut) return;
                isLoggedOut = true;
                clearInterval(timerInterval);

                if (countdownDisplay) countdownDisplay.textContent = '00:00';
                if (mobileCountdownDisplay) mobileCountdownDisplay.textContent = '00:00';

                // Tampilkan Popup SweetAlert2 di tengah layar
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Sesi Telah Berakhir',
                        text: 'Anda tidak melakukan aktivitas selama 10 menit. Untuk keamanan data, sesi Anda telah ditutup secara otomatis.',
                        icon: 'warning',
                        confirmButtonText: 'Login Kembali',
                        confirmButtonColor: '#2563EB',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        window.location.href = "{{ route('login') }}?status=session_expired";
                    });
                }

                // Redirect otomatis setelah 4 detik bila popup tidak diklik
                setTimeout(function() {
                    window.location.href = "{{ route('login') }}?status=session_expired";
                }, 4000);
            }

            // Tangkap semua interaksi pengguna (gerakan kursor, klik mouse, keyboard, scroll layar, sentuhan mobile)
            const activityEvents = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
            let throttleTimer = null;

            activityEvents.forEach(function(evt) {
                window.addEventListener(evt, function() {
                    if (!throttleTimer) {
                        throttleTimer = setTimeout(function() {
                            resetIdleTimer();
                            throttleTimer = null;
                        }, 1000); // Throttling 1 detik agar hemat performa browser
                    }
                }, { passive: true });
            });

            // Klik badge sesi untuk refresh/keepalive instan secara manual
            if (badge) {
                badge.addEventListener('click', function() {
                    resetIdleTimer();
                    lastHeartbeat = Date.now();
                    sendHeartbeat();
                    if (typeof Swal !== 'undefined') {
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Sesi aktif diperpanjang (10:00)'
                        });
                    }
                });
            }

            // Jalankan countdown 1 detik sekali
            updateDisplay();
            timerInterval = setInterval(function() {
                if (secondsRemaining > 0) {
                    secondsRemaining--;
                    updateDisplay();
                } else {
                    triggerSessionExpire();
                }
            }, 1000);
        })();
    </script>
</body>
</html>
