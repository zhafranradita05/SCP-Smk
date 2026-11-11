@extends('layouts.waka')

@section('title', 'Rekapitulasi Semester')

@section('header_actions')
    <a href="{{ route('leaves.recap.print') }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        <span>Cetak Laporan Izin Siswa</span>
    </a>
    <a href="{{ route('waka.recap.print', ['academic_year' => $selectedYear, 'semester' => $selectedSemester]) }}" target="_blank" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
        <span>Cetak Rekap Mengajar</span>
    </a>
@endsection

@section('content')
<div class="space-y-6">

    <!-- 1. FILTER BAR PER SEMESTER & TAHUN AJARAN -->
    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-4">
        <form method="GET" action="{{ route('waka.recap.index') }}" id="recapFilterForm" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
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
            <span class="text-slate-400 ml-auto">Rentang: <strong>{{ $startDate->format('d M Y') }}</strong> s/d <strong>{{ $endDate->format('d M Y') }}</strong></span>
        </div>
    </div>

    <!-- 2. KARTU STATISTIK SEMESTER -->
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

    <!-- 3. TABEL REKAPITULASI KINERJA MENGAJAR GURU -->
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

    <!-- 4. TABEL DETAIL RIWAYAT JURNAL SEMESTER INI -->
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
@endsection
