@extends('layouts.waka')

@section('title', 'Dashboard Utama')

@section('header_actions')
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
        {{ $activeCycle }} • {{ $currentDay }}, {{ $todayFormatted }}
    </span>
@endsection

@section('content')
<div class="space-y-8">

    <!-- 1. KARTU STATISTIK RINGKASAN (WHITE & BLUE) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Status Putaran -->
        <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-blue-300 transition group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Putaran Aktif</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </span>
            </div>
            <p class="text-2xl font-heading font-extrabold text-slate-900">{{ $activeCycle }}</p>
            <p class="text-xs text-slate-500 mt-1 font-medium">{{ $currentDay }}, {{ $todayFormatted }}</p>
        </div>

        <!-- Total Jadwal Hari Ini -->
        <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-blue-300 transition group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jadwal Hari Ini</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
            </div>
            <p class="text-2xl font-heading font-extrabold text-slate-900">{{ $schedules->count() }}</p>
            <p class="text-xs text-slate-500 mt-1 font-medium">Total sesi mengajar terjadwal</p>
        </div>

        <!-- Jurnal Sudah Diisi -->
        <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-blue-300 transition group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sudah Terisi</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-700 group-hover:bg-blue-600 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </span>
            </div>
            <p class="text-2xl font-heading font-extrabold text-blue-600">{{ $sudahIsiCount }}</p>
            <p class="text-xs text-slate-500 mt-1 font-medium">Jurnal guru selesai terisi</p>
        </div>

        <!-- Jurnal Belum Diisi -->
        <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-blue-300 transition group">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Belum Terisi</span>
                <span class="p-2 rounded-xl bg-slate-100 text-slate-600 group-hover:bg-slate-700 group-hover:text-white transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <p class="text-2xl font-heading font-extrabold text-slate-700">{{ $belumIsiCount }}</p>
            <p class="text-xs text-slate-500 mt-1 font-medium">Menunggu pengisian guru</p>
        </div>
    </div>

    <!-- 2. TABEL REKAPITULASI JURNAL GURU HARI INI -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
        <div class="p-6 border-b border-slate-200/80 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-lg font-heading font-bold text-slate-900">Rekapitulasi Jurnal Mengajar Hari Ini</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Jadwal aktif pada hari <strong class="text-slate-800">{{ $currentDay }}</strong> ({{ $activeCycle }}), tanggal <strong class="text-slate-800">{{ $todayFormatted }}</strong>
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 mr-1.5"></span>
                    {{ $sudahIsiCount }} Terisi
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                    {{ $belumIsiCount }} Belum
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Guru</th>
                        <th class="py-3 px-4">Mata Pelajaran</th>
                        <th class="py-3 px-4">Kelas & Ruang</th>
                        <th class="py-3 px-4">Jam Mengajar</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4">Materi Terisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($schedules as $index => $sched)
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="py-3.5 px-4 text-slate-400 font-medium">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">
                                {{ $sched->teacher->name ?? '-' }}
                                <div class="text-xs text-slate-500 font-normal">NIP: {{ $sched->teacher->identifier ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-800 font-medium">{{ $sched->subject }}</td>
                            <td class="py-3.5 px-4 text-slate-600">
                                @php
                                    $majorCode = $sched->schoolClass->major ?? 'UMUM';
                                    $majorColor = $sched->schoolClass->major_badge_color ?? 'bg-slate-100 text-slate-700 border-slate-200';
                                @endphp
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $majorColor }}">
                                        {{ $majorCode }}
                                    </span>
                                    <span class="font-bold text-slate-900">{{ $sched->schoolClass->name ?? '-' }}</span>
                                </div>
                                <span class="text-xs text-slate-500 block">{{ $sched->room->name ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 font-mono text-xs font-semibold">
                                {{ substr($sched->start_time, 0, 5) }} - {{ substr($sched->end_time, 0, 5) }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($sched->is_filled)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        ✓ Sudah Diisi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        • Belum Diisi
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 max-w-xs truncate">
                                @if($sched->is_filled)
                                    <span class="text-slate-900 font-medium">{{ $sched->journal->materi ?? '-' }}</span>
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
