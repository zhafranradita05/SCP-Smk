@extends('layouts.waka')

@section('title', 'Rekapitulasi Semester & Daftar Hadir')

@section('header_actions')
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('leaves.recap.print') }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Cetak Izin Siswa</span>
        </a>
        <a href="{{ route('waka.recap.attendance.print', ['academic_year' => $selectedYear, 'semester' => $selectedSemester, 'class_id' => $selectedClassId]) }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Cetak Daftar Hadir</span>
        </a>
        <a href="{{ route('waka.recap.print', ['academic_year' => $selectedYear, 'semester' => $selectedSemester]) }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            <span>Cetak Rekap Jurnal</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. FILTER BAR PER SEMESTER, TAHUN AJARAN & KELAS -->
    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-4">
        <form method="GET" action="{{ route('waka.recap.index') }}" id="recapFilterForm" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <!-- Pilihan Tahun Ajaran -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tahun Ajaran</label>
                <select name="academic_year" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="2024/2025" {{ $selectedYear == '2024/2025' ? 'selected' : '' }}>2024/2025</option>
                    <option value="2025/2026" {{ $selectedYear == '2025/2026' ? 'selected' : '' }}>2025/2026</option>
                    <option value="2026/2027" {{ $selectedYear == '2026/2027' ? 'selected' : '' }}>2026/2027</option>
                    <option value="2027/2028" {{ $selectedYear == '2027/2028' ? 'selected' : '' }}>2027/2028</option>
                </select>
            </div>

            <!-- Pilihan Semester -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Semester</label>
                <select name="semester" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="ganjil" {{ $selectedSemester == 'ganjil' ? 'selected' : '' }}>Semester Ganjil (Juli - Des)</option>
                    <option value="genap" {{ $selectedSemester == 'genap' ? 'selected' : '' }}>Semester Genap (Jan - Jun)</option>
                </select>
            </div>

            <!-- Pilihan Kelas (Untuk Rekap Daftar Hadir Siswa) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Filter Kelas</label>
                <select name="class_id" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-900 text-xs font-semibold focus:outline-none focus:border-blue-500 cursor-pointer">
                    <option value="all" {{ $selectedClassId == 'all' ? 'selected' : '' }}>Semua Kelas ({{ $allClasses->count() }} Kelas)</option>
                    @foreach($allClasses as $c)
                        <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex space-x-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-semibold shadow-xs transition">
                    Terapkan
                </button>
                <a href="{{ route('waka.recap.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition" title="Kembalikan ke default">
                    Reset
                </a>
            </div>
        </form>

        <!-- Indikator Filter Aktif -->
        <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-xs text-slate-600">
            <span class="font-bold text-slate-700">Filter Terpilih:</span>
            <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-md font-semibold">
                Tahun Ajaran: {{ $selectedYear }}
            </span>
            <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-md font-semibold capitalize">
                Semester: {{ $selectedSemester }}
            </span>
            @if($selectedClassId && $selectedClassId !== 'all')
                @php $activeCls = $allClasses->firstWhere('id', $selectedClassId); @endphp
                <span class="inline-flex items-center px-2.5 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-md font-semibold">
                    Kelas: {{ $activeCls->name ?? $selectedClassId }}
                </span>
            @endif
            <span class="text-slate-400 ml-auto">Rentang: <strong>{{ $startDate->format('d M Y') }}</strong> s/d <strong>{{ $endDate->format('d M Y') }}</strong></span>
        </div>
    </div>

    <!-- 2. NAVIGASI DUA MENU UTAMA (TAB SWITCHER) -->
    <div class="bg-slate-100/80 p-1.5 rounded-2xl flex space-x-2 border border-slate-200">
        <button type="button" id="tabBtnDaftarHadir" onclick="switchWakaRecapTab('daftar_hadir')" 
                class="flex-1 py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 shadow-xs bg-white text-indigo-700 border border-slate-200/80">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Daftar Hadir Siswa</span>
            <span class="px-2 py-0.5 text-[11px] rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 font-semibold">{{ $studentAttendanceRecap->count() }} Siswa</span>
        </button>
        <button type="button" id="tabBtnRekapJurnal" onclick="switchWakaRecapTab('rekap_jurnal')" 
                class="flex-1 py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 text-slate-600 hover:text-slate-900">
            <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Rekapitulasi Jurnal</span>
            <span class="px-2 py-0.5 text-[11px] rounded-full bg-blue-50 text-blue-700 border border-blue-200 font-semibold">{{ $journals->count() }} Sesi</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- MENU 1: REKAPAN DAFTAR HADIR SISWA -->
    <!-- ========================================================================= -->
    <div id="sectionDaftarHadir" class="space-y-6">

        <!-- KARTU STATISTIK DAFTAR HADIR SISWA -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="bg-white p-4.5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-emerald-300 transition">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 block">Total Hadir</span>
                <p class="text-2xl font-heading font-extrabold text-emerald-600">{{ number_format($totalHadirAll, 0, ',', '.') }}</p>
                <span class="text-[11px] text-slate-500 font-medium">Presensi Masuk Sesi</span>
            </div>

            <div class="bg-white p-4.5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-sky-300 transition">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 block">Total Sakit</span>
                <p class="text-2xl font-heading font-extrabold text-sky-600">{{ number_format($totalSakitAll, 0, ',', '.') }}</p>
                <span class="text-[11px] text-slate-500 font-medium">Sakit dengan Bukti</span>
            </div>

            <div class="bg-white p-4.5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-amber-300 transition">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 block">Total Izin</span>
                <p class="text-2xl font-heading font-extrabold text-amber-600">{{ number_format($totalIzinAll, 0, ',', '.') }}</p>
                <span class="text-[11px] text-slate-500 font-medium">Izin Terverifikasi</span>
            </div>

            <div class="bg-white p-4.5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-rose-300 transition">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 block">Total Alpa</span>
                <p class="text-2xl font-heading font-extrabold text-rose-600">{{ number_format($totalAlpaAll, 0, ',', '.') }}</p>
                <span class="text-[11px] text-slate-500 font-medium">Tanpa Keterangan</span>
            </div>

            <div class="col-span-2 sm:col-span-1 bg-white p-4.5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-indigo-300 transition">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1 block">Rata-Rata Kehadiran</span>
                <p class="text-2xl font-heading font-extrabold text-indigo-600">{{ $avgPersentaseHadir }}%</p>
                <span class="text-[11px] text-slate-500 font-medium">{{ number_format($totalSemuaKehadiran, 0, ',', '.') }} Presensi Sesi</span>
            </div>
        </div>

        @if($selectedClassId === 'all')
        <!-- TAMPILAN UTAMA: REKAPITULASI DAFTAR HADIR PER KELAS -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-heading font-bold text-slate-900">Rekapitulasi Kehadiran Siswa Per Kelas</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pilih atau klik salah satu kelas di bawah ini untuk langsung membuka rincian daftar absen siswa.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-md border border-indigo-100">
                    {{ $attendanceByClass->count() }} Kelas Terdata
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] uppercase tracking-wider font-bold border-b border-slate-200">
                            <th class="py-3.5 px-4">No</th>
                            <th class="py-3.5 px-4">Nama Kelas</th>
                            <th class="py-3.5 px-4 text-center">Jumlah Siswa</th>
                            <th class="py-3.5 px-4 text-center">Hadir</th>
                            <th class="py-3.5 px-4 text-center">Sakit</th>
                            <th class="py-3.5 px-4 text-center">Izin</th>
                            <th class="py-3.5 px-4 text-center">Alpa</th>
                            <th class="py-3.5 px-4 text-center">Total Sesi</th>
                            <th class="py-3.5 px-4 text-center">% Rata-rata Hadir</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($attendanceByClass as $idx => $ac)
                        <tr class="hover:bg-indigo-50/40 transition cursor-pointer group" 
                            onclick="window.location='{{ route('waka.recap.index', ['academic_year' => $selectedYear, 'semester' => $selectedSemester, 'class_id' => $ac['id']]) }}#daftar-hadir'">
                            <td class="py-3.5 px-4 font-medium text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-slate-900 group-hover:text-indigo-600 transition flex items-center space-x-1.5">
                                    <span>{{ $ac['name'] }}</span>
                                    <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center text-slate-600 font-semibold">{{ $ac['total_students'] }} Siswa</td>
                            <td class="py-3.5 px-4 text-center font-bold text-emerald-600">{{ $ac['hadir'] }}</td>
                            <td class="py-3.5 px-4 text-center text-sky-600 font-medium">{{ $ac['sakit'] }}</td>
                            <td class="py-3.5 px-4 text-center text-amber-600 font-medium">{{ $ac['izin'] }}</td>
                            <td class="py-3.5 px-4 text-center text-rose-600 font-medium">{{ $ac['alpa'] }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-700">{{ $ac['total_pertemuan'] }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ac['persentase'] >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($ac['persentase'] >= 75 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                    {{ $ac['persentase'] }}%
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center" onclick="event.stopPropagation()">
                                <a href="{{ route('waka.recap.index', ['academic_year' => $selectedYear, 'semester' => $selectedSemester, 'class_id' => $ac['id']]) }}#daftar-hadir" 
                                   class="inline-flex items-center space-x-1 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200">
                                    <span>Buka Daftar Absen</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-500">Belum ada data kelas yang terdaftar.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <!-- TAMPILAN RINCIAN DAFTAR ABSEN SISWA UNTUK KELAS YANG DIKLIK -->
        @php $activeClassName = $allClasses->firstWhere('id', $selectedClassId)->name ?? 'Kelas Terpilih'; @endphp
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <a href="{{ route('waka.recap.index', ['academic_year' => $selectedYear, 'semester' => $selectedSemester, 'class_id' => 'all']) }}#daftar-hadir" 
                       class="inline-flex items-center space-x-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition" title="Kembali ke Rekap Semua Kelas">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>Kembali ke Daftar Kelas</span>
                    </a>
                    <div>
                        <h2 class="text-base font-heading font-bold text-slate-900">Daftar Absen Siswa Kelas {{ $activeClassName }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Rekap kehadiran siswa per sesi pada periode {{ $selectedYear }} ({{ ucfirst($selectedSemester) }}).
                        </p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <input type="text" id="studentSearchInput" placeholder="Cari nama / NISN..." onkeyup="filterStudentTable()" 
                           class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 focus:outline-none focus:border-indigo-500">
                    <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md">
                        Total: {{ $studentAttendanceRecap->count() }} Siswa
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="studentAttendanceTable">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] uppercase tracking-wider font-bold border-b border-slate-200">
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">NISN</th>
                            <th class="py-3 px-4">Nama Siswa</th>
                            <th class="py-3 px-4">Kelas</th>
                            <th class="py-3 px-4 text-center">Hadir</th>
                            <th class="py-3 px-4 text-center">Sakit</th>
                            <th class="py-3 px-4 text-center">Izin</th>
                            <th class="py-3 px-4 text-center">Alpa</th>
                            <th class="py-3 px-4 text-center">Total Sesi</th>
                            <th class="py-3 px-4 text-center">% Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($studentAttendanceRecap as $idx => $s)
                        <tr class="hover:bg-slate-50/80 transition student-row">
                            <td class="py-3.5 px-4 font-medium text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-600">{{ $s['nisn'] }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900 student-name">{{ $s['name'] }}</td>
                            <td class="py-3.5 px-4 text-slate-700 text-xs font-semibold">{{ $s['class_name'] }}</td>
                            <td class="py-3.5 px-4 text-center font-bold text-emerald-600">{{ $s['hadir'] }}</td>
                            <td class="py-3.5 px-4 text-center text-sky-600 font-medium">{{ $s['sakit'] }}</td>
                            <td class="py-3.5 px-4 text-center text-amber-600 font-medium">{{ $s['izin'] }}</td>
                            <td class="py-3.5 px-4 text-center text-rose-600 font-medium">
                                @if($s['alpa'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $s['alpa'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-bold text-slate-700">{{ $s['total_pertemuan'] }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $s['persentase'] >= 85 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($s['persentase'] >= 75 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                    {{ $s['persentase'] }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="py-10 text-center text-slate-500">
                                <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                <p class="font-medium text-slate-700">Belum ada siswa terdaftar pada kelas ini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>

    <!-- ========================================================================= -->
    <!-- MENU 2: REKAPITULASI JURNAL MENGAJAR GURU -->
    <!-- ========================================================================= -->
    <div id="sectionRekapJurnal" class="space-y-6 hidden">

        <!-- KARTU STATISTIK JURNAL & IZIN -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-blue-300 transition group">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Total Jurnal Terisi</span>
                <p class="text-3xl font-heading font-extrabold text-blue-600">{{ $journals->count() }}</p>
                <span class="text-xs text-slate-500 font-medium">Sesi mengajar pada {{ $selectedYear }} ({{ ucfirst($selectedSemester) }})</span>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-blue-300 transition group">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Total Izin Siswa</span>
                <p class="text-3xl font-heading font-extrabold text-slate-900">{{ $totalIzin }}</p>
                <span class="text-xs text-slate-500 font-medium">Disetujui pada periode ini</span>
            </div>

            <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 hover:border-blue-300 transition group">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1 block">Periode Semester</span>
                <p class="text-lg font-heading font-bold text-slate-900 uppercase">Semester {{ $selectedSemester }} ({{ $selectedYear }})</p>
                <span class="text-xs text-slate-500 font-medium">{{ $startDate->format('d M Y') }} s/d {{ $endDate->format('d M Y') }}</span>
            </div>
        </div>

        <!-- TABEL REKAPITULASI KINERJA MENGAJAR GURU -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-heading font-bold text-slate-900">Rekapitulasi Pengisian Jurnal Per Guru</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Akumulasi sesi kelas yang berhasil diisi jurnalnya pada Tahun Ajaran {{ $selectedYear }} ({{ ucfirst($selectedSemester) }}).</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-md">
                    {{ $teacherRecap->count() }} Guru
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] uppercase tracking-wider font-bold border-b border-slate-200">
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">Nama Guru</th>
                            <th class="py-3 px-4">NIP</th>
                            <th class="py-3 px-4 text-center">Total Jurnal Selesai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($teacherRecap as $idx => $tr)
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="py-3.5 px-4 font-medium text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-900">{{ $tr['name'] }}</td>
                            <td class="py-3.5 px-4 font-mono text-xs text-slate-600">{{ $tr['nip'] }}</td>
                            <td class="py-3.5 px-4 text-center">
                                @if($tr['total_journals'] > 0)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $tr['total_journals'] }} Sesi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-slate-400 bg-slate-50 border border-slate-200">
                                        0 Sesi
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-500">Belum ada data guru.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TABEL DETAIL RIWAYAT JURNAL KELAS SEMESTER INI -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h2 class="text-base font-heading font-bold text-slate-900">Detail Riwayat Jurnal Kelas</h2>
                <p class="text-xs text-slate-500 mt-0.5">Daftar lengkap pelaksanaan mengajar dan bukti foto pada periode {{ $selectedYear }} ({{ ucfirst($selectedSemester) }}).</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] uppercase tracking-wider font-bold border-b border-slate-200">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Guru</th>
                            <th class="py-3 px-4">Mata Pelajaran & Kelas</th>
                            <th class="py-3 px-4">Materi Pembelajaran</th>
                            <th class="py-3 px-4 text-center">Foto Bukti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @forelse($journals as $j)
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="py-3.5 px-4 font-semibold text-slate-900">{{ \Carbon\Carbon::parse($j->date)->format('d/m/Y') }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">{{ $j->teacher->name ?? 'Guru' }}</td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $j->schedule->subject ?? '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $j->schedule->schoolClass->name ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-700 max-w-xs truncate">{{ $j->materi }}</td>
                            <td class="py-3.5 px-4 text-center">
                                @if($j->photo_url)
                                    <a href="{{ asset('storage/' . $j->photo_url) }}" target="_blank" class="inline-block text-xs font-semibold text-blue-600 hover:text-blue-800 underline">
                                        Lihat Foto
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-500">
                                <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <p class="font-medium text-slate-700">Belum ada jurnal mengajar pada periode {{ $selectedYear }} ({{ ucfirst($selectedSemester) }}).</p>
                                <p class="text-xs text-slate-400 mt-1">Rentang tanggal: {{ $startDate->format('d M Y') }} s/d {{ $endDate->format('d M Y') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- JAVASCRIPT TAB SWITCHER & LIVE FILTER -->
<script>
    function switchWakaRecapTab(tab) {
        const secDaftarHadir = document.getElementById('sectionDaftarHadir');
        const secRekapJurnal = document.getElementById('sectionRekapJurnal');
        const btnDaftarHadir = document.getElementById('tabBtnDaftarHadir');
        const btnRekapJurnal = document.getElementById('tabBtnRekapJurnal');

        if (tab === 'daftar_hadir') {
            secDaftarHadir.classList.remove('hidden');
            secRekapJurnal.classList.add('hidden');

            btnDaftarHadir.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 shadow-xs bg-white text-indigo-700 border border-slate-200/80';
            btnRekapJurnal.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 text-slate-600 hover:text-slate-900';
            
            // Simpan preferensi tab di hash URL
            window.location.hash = 'daftar-hadir';
        } else {
            secDaftarHadir.classList.add('hidden');
            secRekapJurnal.classList.remove('hidden');

            btnDaftarHadir.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 text-slate-600 hover:text-slate-900';
            btnRekapJurnal.className = 'flex-1 py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition flex items-center justify-center space-x-2 shadow-xs bg-white text-blue-700 border border-slate-200/80';
            
            // Simpan preferensi tab di hash URL
            window.location.hash = 'rekap-jurnal';
        }
    }

    function filterStudentTable() {
        const input = document.getElementById('studentSearchInput');
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll('.student-row');

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    }

    // Auto-buka tab sesuai hash URL jika ada
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash === '#rekap-jurnal') {
            switchWakaRecapTab('rekap_jurnal');
        } else {
            switchWakaRecapTab('daftar_hadir');
        }
    });
</script>
@endsection
