@extends('layouts.waka')

@section('title', 'Dashboard Utama')

@section('header_actions')
    <div class="flex items-center gap-2">
        <a href="{{ url('/waka/schedules') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 shadow-2xs transition">
            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span>Kelola Jadwal</span>
        </a>
        <a href="{{ url('/waka/recap') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Rekap Semester</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. KARTU STATISTIK DENGAN SENTUHAN WARNA HARMONIS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- 1. Putaran Aktif (Emerald Accent) -->
        <div class="bg-gradient-to-br from-emerald-50/70 via-white to-white p-5 rounded-2xl shadow-xs border border-emerald-200/90 hover:border-emerald-400 hover:shadow-md transition-all group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>Putaran Aktif</span>
                    </span>
                    @if(isset($rotationInfo) && $rotationInfo['is_auto'])
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300" title="Putaran otomatis berganti tiap 2 minggu">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                            Otomatis 2 Minggu
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            Manual
                        </span>
                    @endif
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-heading font-extrabold text-slate-900 tracking-tight">{{ $activeCycle }}</p>
                    <span class="text-xs font-semibold text-emerald-700">({{ ($activeCycle ?? '') === 'Putaran 2' ? 'Normatif & Adaptif' : 'Produktif' }})</span>
                </div>
            </div>

            <div class="mt-3.5 pt-2.5 border-t border-emerald-100/80 text-xs">
                @if(isset($rotationInfo) && $rotationInfo['is_auto'] && $rotationInfo['next_rotation_formatted'])
                    <div class="flex items-center justify-between text-emerald-900">
                        <span class="text-[11px] font-medium flex items-center gap-1 text-slate-600">
                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span>Ganti: {{ $rotationInfo['next_rotation_formatted'] }}</span>
                        </span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">
                            {{ $rotationInfo['remaining_text'] }}
                        </span>
                    </div>
                @else
                    <p class="text-xs text-slate-500 font-medium">{{ $currentDay }}, {{ $todayFormatted }}</p>
                @endif
            </div>
        </div>

        <!-- 2. Jadwal Hari Ini (Blue Accent) -->
        <div class="bg-gradient-to-br from-blue-50/70 via-white to-white p-5 rounded-2xl shadow-xs border border-blue-200/90 hover:border-blue-400 hover:shadow-md transition-all group flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-bold text-blue-800 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    <span>Jadwal Hari Ini</span>
                </span>
                <span class="p-2 rounded-xl bg-blue-600 text-white shadow-xs group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
            </div>
            <div>
                <p class="text-3xl font-heading font-extrabold text-slate-900 tracking-tight">{{ $schedules->count() }}</p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Sesi mengajar terjadwal hari ini</p>
            </div>
            <div class="mt-3.5 pt-2.5 border-t border-blue-100/80 text-[11px] font-medium text-blue-700 flex items-center justify-between">
                <span>{{ $currentDay }}, {{ $todayFormatted }}</span>
                <span class="font-bold text-blue-800">{{ $totalKelas }} Kelas Aktif</span>
            </div>
        </div>

        <!-- 3. Jurnal Sudah Diisi (Teal Accent) -->
        <div class="bg-gradient-to-br from-teal-50/70 via-white to-white p-5 rounded-2xl shadow-xs border border-teal-200/90 hover:border-teal-400 hover:shadow-md transition-all group flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-bold text-teal-800 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                    <span>Sudah Terisi</span>
                </span>
                <span class="p-2 rounded-xl bg-teal-600 text-white shadow-xs group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </span>
            </div>
            <div>
                <p class="text-3xl font-heading font-extrabold text-teal-700 tracking-tight">{{ $sudahIsiCount }}</p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Jurnal guru selesai diverifikasi</p>
            </div>
            <div class="mt-3.5 pt-2.5 border-t border-teal-100/80 text-[11px] font-medium text-teal-700 flex items-center justify-between">
                <span>Keterisian Jurnal:</span>
                <span class="font-bold text-teal-800">
                    {{ $schedules->count() > 0 ? round(($sudahIsiCount / $schedules->count()) * 100) : 0 }}%
                </span>
            </div>
        </div>

        <!-- 4. Jurnal Belum Diisi (Amber Accent) -->
        <div class="bg-gradient-to-br from-amber-50/70 via-white to-white p-5 rounded-2xl shadow-xs border border-amber-200/90 hover:border-amber-400 hover:shadow-md transition-all group flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span>Belum Terisi</span>
                </span>
                <span class="p-2 rounded-xl bg-amber-500 text-white shadow-xs group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div>
                <p class="text-3xl font-heading font-extrabold text-amber-700 tracking-tight">{{ $belumIsiCount }}</p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Menunggu input jurnal guru</p>
            </div>
            <div class="mt-3.5 pt-2.5 border-t border-amber-100/80 text-[11px] font-medium text-amber-700 flex items-center justify-between">
                <span>Status:</span>
                <span class="font-bold {{ $belumIsiCount > 0 ? 'text-amber-800' : 'text-emerald-700' }}">
                    {{ $belumIsiCount > 0 ? 'Perlu Pengingat' : 'Semua Selesai' }}
                </span>
            </div>
        </div>
    </div>

    <!-- 2. TABEL REKAPITULASI JURNAL GURU HARI INI -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-base font-heading font-bold text-slate-900 flex items-center gap-2">
                    <span>Rekapitulasi Jurnal Mengajar Hari Ini</span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800">{{ $currentDay }}</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Monitoring real-time keterisian jurnal mengajar kelas {{ $todayFormatted }}
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-600 mr-1.5"></span>
                    {{ $sudahIsiCount }} Terisi
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 mr-1.5"></span>
                    {{ $belumIsiCount }} Belum
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-600 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3.5 px-4 w-12 text-center whitespace-nowrap">No</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[170px]">Guru Pengajar</th>
                        <th class="py-3.5 px-4 min-w-[170px]">Mata Pelajaran</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[160px]">Kelas & Ruang</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[130px]">Jam Mengajar</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap min-w-[120px]">Status</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Materi Terisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($schedules as $index => $sched)
                        <tr class="hover:bg-blue-50/30 transition">
                            <td class="py-3.5 px-4 text-slate-400 font-medium text-center">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-semibold text-slate-900">{{ $sched->teacher->name ?? '-' }}</div>
                                <div class="text-xs text-slate-500 font-normal">NIP: {{ $sched->teacher->identifier ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-800 font-medium">
                                <span>{{ $sched->subject }}</span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @php
                                    $majorCode = $sched->schoolClass->major ?? 'UMUM';
                                    $majorColor = $sched->schoolClass->major_badge_color ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                    $roomName = $sched->room->name ?? '-';
                                @endphp
                                <div class="flex flex-col gap-1.5 items-start">
                                    <!-- Baris 1: Badge Jurusan + Nama Kelas Lengkap (Tidak Tertumpuk) -->
                                    <div class="flex items-center gap-2 whitespace-nowrap">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border flex-shrink-0 {{ $majorColor }}">
                                            {{ $majorCode }}
                                        </span>
                                        <span class="font-bold text-slate-900 text-sm tracking-tight">{{ $sched->schoolClass->name ?? '-' }}</span>
                                    </div>
                                    <!-- Baris 2: Badge Ruangan Mandiri Rapi -->
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100/90 text-slate-600 text-xs font-medium border border-slate-200/80 whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <span>{{ $roomName }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 font-mono text-xs font-semibold whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-slate-800">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>{{ substr($sched->start_time, 0, 5) }} - {{ substr($sched->end_time, 0, 5) }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($sched->is_filled)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-50 text-teal-700 border border-teal-200">
                                        ✓ Sudah Diisi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        • Belum Diisi
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 max-w-xs">
                                @if($sched->is_filled)
                                    <span class="text-slate-900 font-medium block truncate">{{ $sched->journal->materi ?? '-' }}</span>
                                    <span class="text-xs text-slate-500 block">{{ $sched->journal->created_at->format('H:i') }} WIB</span>
                                @else
                                    <span class="text-slate-400 italic text-xs">Belum ada catatan materi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Tidak ada jadwal mengajar pada hari {{ $currentDay }} ({{ $activeCycle }}).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
