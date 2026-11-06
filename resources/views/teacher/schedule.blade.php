<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar Saya - {{ $user->name }} | SCP Portal</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; color: #0F172A; background-color: #F8FAFC; }
        h1, h2, h3, h4, .font-heading { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full flex flex-col">

    <!-- Top Navigation -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="w-9 h-9 object-contain">
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-heading font-extrabold text-slate-900 text-base">SCP Portal</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            Portal Guru
                        </span>
                    </div>
                    <p class="text-[10px] text-slate-500 font-medium">School Class Progress</p>
                </div>
            </div>

            <!-- User Info & Actions -->
            <div class="flex items-center space-x-3">
                @if(in_array($user->role, ['wali_kelas', 'guru_bk']))
                    <a href="{{ route('leaves.recap.print') }}" class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Rekap Izin Siswa
                    </a>
                @endif

                <div class="text-right hidden sm:block">
                    <div class="text-xs font-bold text-slate-900">{{ $user->name }}</div>
                    <div class="text-[10px] text-slate-500">
                        {{ $user->identifier ? 'NIP: ' . $user->identifier : '@' . $user->username }}
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 transition" title="Keluar">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">

        <!-- Header Profil & Penugasan -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-xl sm:text-2xl font-heading font-extrabold text-slate-900">
                        Jadwal Mengajar: {{ $user->name }}
                    </h1>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold {{ $user->role === 'wali_kelas' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($user->role === 'guru_bk' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-blue-50 text-blue-700 border border-blue-200') }}">
                        {{ $user->role === 'wali_kelas' ? 'Wali Kelas' : ($user->role === 'guru_bk' ? 'Guru BK' : 'Guru Pengajar') }}
                    </span>
                    @if($homeroomClass)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            Wali: {{ $homeroomClass->name }}
                        </span>
                    @endif
                    @if($bkClasses && $bkClasses->count() > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200" title="{{ $bkClasses->pluck('name')->join(', ') }}">
                            ⭐ {{ $bkClasses->count() }} Kelas BK
                        </span>
                    @endif
                    <span class="text-slate-400">•</span>
                    <span>Tahun Ajaran: <strong>{{ $setting->academic_year ?? '2026/2027' }}</strong> (Semester {{ ucfirst($setting->active_semester ?? 'Ganjil') }})</span>
                </div>
            </div>

            <!-- Siklus Switcher -->
            <div class="flex items-center gap-2 bg-slate-50 p-1.5 rounded-xl border border-slate-200">
                <a href="{{ route('teacher.schedule', ['cycle' => 'putaran_1']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $selectedCycle === 'putaran_1' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' }}">
                    Putaran 1 {{ $systemCycle === 'putaran_1' ? '• (Aktif)' : '' }}
                </a>
                <a href="{{ route('teacher.schedule', ['cycle' => 'putaran_2']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-bold transition {{ $selectedCycle === 'putaran_2' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70' }}">
                    Putaran 2 {{ $systemCycle === 'putaran_2' ? '• (Aktif)' : '' }}
                </a>
            </div>
        </div>

        <!-- 1. KARTU STATUS JURNAL HARI INI (AKTIF / NONAKTIF) -->
        <div class="rounded-2xl border p-6 transition {{ $isJournalActive ? 'bg-gradient-to-r from-emerald-50 to-teal-50/50 border-emerald-200 shadow-xs' : 'bg-gradient-to-r from-slate-50 to-slate-100/60 border-slate-200 shadow-xs' }}">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 {{ $isJournalActive ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-400 text-white' }}">
                        @if($isJournalActive)
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-heading font-extrabold text-base sm:text-lg {{ $isJournalActive ? 'text-emerald-950' : 'text-slate-800' }}">
                                Status Jurnal Mengajar Hari Ini: {{ $isJournalActive ? 'AKTIF' : 'NONAKTIF' }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold {{ $isJournalActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $todayDateFormatted }}
                            </span>
                        </div>
                        <p class="text-xs {{ $isJournalActive ? 'text-emerald-800' : 'text-slate-600' }}">
                            @if($isJournalActive)
                                Anda memiliki <strong>{{ $todaySchedules->count() }} sesi mengajar</strong> pada siklus aktif hari ini. Pengisian jurnal tatap muka dapat dilakukan via aplikasi mobile SCP.
                            @else
                                <strong>Tidak ada jadwal mengajar</strong> untuk akun Anda pada hari {{ $currentDay }} ({{ ucfirst(str_replace('_', ' ', $systemCycle)) }}). Sistem otomatis <strong>menonaktifkan pengisian jurnal</strong> dan <strong>mematikan notifikasi pengingat</strong> hari ini.
                            @endif
                        </p>
                    </div>
                </div>

                @if($isJournalActive)
                    <div class="flex items-center gap-2 text-xs font-semibold px-3.5 py-2 rounded-xl bg-white/80 border border-emerald-200 text-emerald-800 self-start md:self-auto">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>{{ $todaySchedules->where('is_filled', true)->count() }} dari {{ $todaySchedules->count() }} Sesi Terisi</span>
                    </div>
                @endif
            </div>

            <!-- Daftar Jadwal Hari Ini jika Aktif -->
            @if($isJournalActive && $todaySchedules->isNotEmpty())
                <div class="mt-5 pt-5 border-t border-emerald-200/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($todaySchedules as $s)
                        <div class="bg-white rounded-xl p-3.5 border {{ $s->is_filled ? 'border-emerald-300' : 'border-slate-200' }} shadow-xs flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="font-bold text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                                        {{ $s->schoolClass->name ?? 'Kelas' }}
                                    </span>
                                    <span class="text-[11px] font-mono text-slate-500 font-semibold">
                                        {{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}
                                    </span>
                                </div>
                                <h4 class="font-bold text-slate-900 text-xs line-clamp-1">{{ $s->subject }}</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">Ruang: {{ $s->room->name ?? '-' }}</p>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-500">Jurnal:</span>
                                @if($s->is_filled)
                                    <span class="font-bold text-emerald-700 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                        Sudah Diisi
                                    </span>
                                @else
                                    <span class="font-bold text-amber-600 flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        Menunggu Pengisian
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 2. TABEL KISI JADWAL FORMAT FET (MATRIKS JADWAL RESMI SEKOLAH) -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-heading font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Kisi Jadwal Mengajar Mingguan (Format FET Timetable)
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Jadwal resmi tatap muka per jam pelajaran untuk <strong>{{ ucfirst(str_replace('_', ' ', $selectedCycle)) }}</strong>.
                    </p>
                </div>
                <div class="text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                    Total: <strong>{{ $allSchedules->count() }} Sesi Mengajar</strong>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs text-center border-collapse">
                    <thead>
                        <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-700">
                            <th class="py-3 px-3 font-bold border-r border-slate-200 w-24">Waktu</th>
                            @foreach($daysList as $d)
                                <th class="py-3 px-3 font-bold border-r border-slate-200 {{ $d === $currentDay ? 'bg-blue-50/80 text-blue-900 font-extrabold' : '' }}">
                                    {{ $d }}
                                    @if($d === $currentDay)
                                        <span class="block text-[10px] text-blue-600 font-normal">Hari Ini</span>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach($gridTimeSlots as $slot)
                            <tr class="hover:bg-slate-50/60 transition">
                                <!-- Jam Slot Waktu -->
                                <td class="py-3 px-2 font-mono font-bold text-slate-600 bg-slate-50/70 border-r border-slate-200 text-xs">
                                    {{ $slot }}
                                </td>

                                <!-- Kolom Hari -->
                                @foreach($daysList as $d)
                                    @php
                                        $sched = $gridMatrix[$d][$slot] ?? null;
                                        $isToday = ($d === $currentDay);
                                    @endphp
                                    <td class="py-2 px-2 border-r border-slate-200 align-middle {{ $isToday ? 'bg-blue-50/20' : '' }}">
                                        @if($sched)
                                            <div class="p-2 rounded-lg bg-blue-50 border border-blue-200 text-slate-800 text-left shadow-2xs">
                                                <div class="font-extrabold text-blue-800 text-xs">{{ $sched->schoolClass->name ?? '-' }}</div>
                                                <div class="font-semibold text-slate-900 text-[11px] line-clamp-2 mt-0.5">{{ $sched->subject }}</div>
                                                <div class="text-[10px] font-mono text-slate-500 mt-1 flex items-center justify-between">
                                                    <span>{{ $sched->room->name ?? '-' }}</span>
                                                    <span>{{ substr($sched->start_time, 0, 5) }}-{{ substr($sched->end_time, 0, 5) }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-slate-300 font-mono text-xs select-none">---</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <span>Jadwal dihasilkan dan disinkronkan dari sistem kurikulum sekolah (FET Timetable Compatible).</span>
                <span>SCP &bull; Sistem Monitoring Jurnal & Jadwal Akademik</span>
            </div>
        </div>

    </main>

</body>
</html>

