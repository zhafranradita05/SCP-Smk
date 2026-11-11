@extends('layouts.waka')

@section('title', 'Kenaikan Kelas & Kelulusan Massal')

@section('header_actions')
    <div class="flex items-center space-x-2">
        <a href="{{ route('waka.promotions.export_alumni') }}" class="inline-flex items-center px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold rounded-lg transition space-x-1.5 shadow-xs">
            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Ekspor Arsip Alumni</span>
        </a>
        <a href="{{ route('waka.users.index', ['role' => 'siswa']) }}" class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition space-x-2">
            <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke Data Siswa</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Notifikasi Sukses / Gagal -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl shadow-xs flex items-center space-x-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-xs flex items-center space-x-3">
            <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span class="font-medium text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Alert Status Kolom Alumni -->
    @if(!$hasStatusCol)
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl shadow-xs flex items-start space-x-3 text-xs">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <span class="font-bold">Info Status Alumni:</span> Jalankan perintah <code class="px-1.5 py-0.5 bg-amber-100 font-mono rounded">php artisan migrate</code> agar kolom status alumni permanen aktif.
            </div>
        </div>
    @endif

    <!-- Panel Informasi & Ringkasan Siswa -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Siswa Aktif</span>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalSiswaAktifCount) }}</div>
                <span class="text-[11px] text-slate-500">Memiliki rombel aktif</span>
            </div>
            <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kelas X (Naik ke XI)</span>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($countX) }}</div>
                <span class="text-[11px] text-slate-500">Siswa tingkat 1</span>
            </div>
            <div class="px-2.5 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-1.5 font-bold text-xs">
                <span>X</span>
                <span class="text-emerald-500 font-extrabold">&rarr;</span>
                <span>XI</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kelas XI (Naik ke XII)</span>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ number_format($countXI) }}</div>
                <span class="text-[11px] text-slate-500">Siswa tingkat 2</span>
            </div>
            <div class="px-2.5 py-1.5 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl flex items-center gap-1.5 font-bold text-xs">
                <span>XI</span>
                <span class="text-blue-500 font-extrabold">&rarr;</span>
                <span>XII</span>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Kelas XII (Akan Lulus)</span>
                <div class="text-2xl font-black text-indigo-600 mt-1">{{ number_format($countXII) }}</div>
                <span class="text-[11px] text-slate-500">Status Alumni</span>
            </div>
            <div class="px-2.5 py-1.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl flex items-center gap-1.5 font-bold text-xs">
                <span>XII</span>
                <span class="text-indigo-500 font-extrabold">&rarr;</span>
                <span>🎓 Lulus</span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- FORM UTAMA KENAIKAN KELAS & KELULUSAN MASSAL SERENTAK SELURUH SEKOLAH     -->
    <!-- ========================================================================= -->
    <form action="{{ route('waka.promotions.bulk') }}" method="POST" id="bulkPromotionForm" onsubmit="return confirmBulkProcess(event)" class="space-y-6">
        @csrf

        <!-- Hero Box Kenaikan Massal -->
        <div class="bg-gradient-to-br from-slate-900 to-blue-900 text-white p-6 sm:p-8 rounded-2xl shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-500/30 text-blue-200 border border-blue-400/30 mb-2">
                        🚀 Kenaikan Serentak Akhir Tahun Ajaran
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white">Proses Kenaikan Massal Seluruh Sekolah</h2>
                    <p class="text-xs text-blue-200 mt-1 max-w-2xl leading-relaxed">
                        Sistem akan secara otomatis memindahkan seluruh rombel Kelas X &rarr; XI, Kelas XI &rarr; XII, dan meluluskan siswa Kelas XII menjadi Alumni secara serentak dalam 1 detik.
                    </p>
                </div>
                <button type="submit" class="px-6 py-3.5 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-black rounded-xl shadow-lg transition flex items-center justify-center space-x-2 shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Eksekusi Kenaikan Massal Sekarang</span>
                </button>
            </div>

            <!-- Alur Perpindahan Serentak -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-blue-800/60 text-xs">
                <div class="bg-white/10 p-3.5 rounded-xl border border-white/10">
                    <div class="text-blue-300 font-bold uppercase text-[10px]">Tahap 1: Kelulusan</div>
                    <div class="text-white font-bold text-sm mt-1">Kelas XII &rarr; Alumni</div>
                    <div class="text-blue-200 text-[11px] mt-0.5">{{ $countXII }} Siswa dinyatakan Lulus</div>
                </div>
                <div class="bg-white/10 p-3.5 rounded-xl border border-white/10">
                    <div class="text-blue-300 font-bold uppercase text-[10px]">Tahap 2: Naik Jenjang Akhir</div>
                    <div class="text-white font-bold text-sm mt-1">Kelas XI &rarr; Kelas XII</div>
                    <div class="text-blue-200 text-[11px] mt-0.5">{{ $countXI }} Siswa naik ke tingkat 3</div>
                </div>
                <div class="bg-white/10 p-3.5 rounded-xl border border-white/10">
                    <div class="text-blue-300 font-bold uppercase text-[10px]">Tahap 3: Naik Jenjang Awal</div>
                    <div class="text-white font-bold text-sm mt-1">Kelas X &rarr; Kelas XI</div>
                    <div class="text-blue-200 text-[11px] mt-0.5">{{ $countX }} Siswa naik ke tingkat 2</div>
                </div>
            </div>
        </div>

        <!-- BAGIAN PENGECUALIAN: SISWA TINGGAL KELAS (OPSIONAL) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        Pengecualian: Siswa yang TINGGAL KELAS (Opsional)
                    </h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-2xl leading-relaxed">
                        Secara default, <strong>seluruh siswa dianggap naik kelas</strong>. Jika ada siswa yang tidak naik kelas, centang namanya di bawah ini.
                        Siswa yang tinggal kelas akan <strong class="text-amber-800">tetap berada pada jurusan yang sama</strong> dan otomatis dialokasikan ke rombel yang masih memiliki kursi kosong (<strong class="text-amber-800">kapasitas maksimal 36 siswa</strong> per rombel).
                    </p>
                </div>
                <span id="bulkStayedBadge" class="text-xs font-bold px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg shrink-0">
                    0 Siswa Tinggal Kelas
                </span>
            </div>

            <!-- Input Pencarian Siswa Pengecualian -->
            <div class="max-w-md">
                <div class="relative">
                    <input type="text" id="stayedSearchInput" onkeyup="filterStayedStudents()" placeholder="Cari nama siswa, NISN, atau kelas (misal: Budi / RPL)..." class="w-full text-xs pl-9 pr-3 py-2.5 border rounded-lg border-slate-200 bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- List Siswa untuk Dipilih Tinggal Kelas -->
            <div class="max-h-72 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 text-xs">
                @forelse($allActiveStudents as $std)
                    <label class="stayed-student-item flex items-center justify-between p-2.5 hover:bg-slate-50 cursor-pointer transition" data-name="{{ strtolower($std->name) }}" data-class="{{ strtolower($std->schoolClass->name ?? '') }}" data-nisn="{{ strtolower($std->identifier ?? '') }}">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="stayed_student_ids[]" value="{{ $std->id }}" onchange="updateStayedCount()" class="stayed-checkbox w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                            <div>
                                <span class="font-bold text-slate-900">{{ $std->name }}</span>
                                <span class="text-slate-400 font-mono text-[11px] ml-1">({{ $std->identifier ?? '-' }})</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            @if($std->schoolClass)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $std->schoolClass->major_badge_color }}">
                                    {{ $std->schoolClass->major }}
                                </span>
                            @endif
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $std->schoolClass->name ?? 'Belum ada kelas' }}
                            </span>
                        </div>
                    </label>
                @empty
                    <div class="p-6 text-center text-slate-400">Tidak ada data siswa aktif.</div>
                @endforelse
            </div>
        </div>

        <!-- TABEL PRATINJAU PEMETAAN ROMBEL SELURUH SEKOLAH -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pratinjau Alur Otomatis Seluruh Rombel</h3>
                    <p class="text-xs text-slate-500">Daftar kelas asal dan kelas tujuan yang akan dipindahkan saat eksekusi.</p>
                </div>
                <span class="text-xs text-slate-500 font-mono font-bold">Total: {{ count($bulkMappingPreview) }} Rombel</span>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider font-bold border-b border-slate-200">
                            <th class="py-2.5 px-4">Kelas Asal (Tahun Ini)</th>
                            <th class="py-2.5 px-4">Siswa Aktif</th>
                            <th class="py-2.5 px-4">Arah Perpindahan</th>
                            <th class="py-2.5 px-4">Kelas Tujuan (Tahun Ajaran Baru)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($bulkMappingPreview as $preview)
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 px-4 font-bold text-slate-900">{{ $preview['source'] }}</td>
                                <td class="py-2.5 px-4 font-semibold text-slate-700">{{ $preview['count'] }} Siswa</td>
                                <td class="py-2.5 px-4 text-slate-400 font-bold text-sm">&rarr;</td>
                                <td class="py-2.5 px-4 font-bold">
                                    @if($preview['status'] === 'graduate')
                                        <span class="text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                                            {{ $preview['target'] }}
                                        </span>
                                    @elseif($preview['status'] === 'promote')
                                        <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            {{ $preview['target'] }}
                                        </span>
                                    @else
                                        <span class="text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                                            {{ $preview['target'] }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-7 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Eksekusi Kenaikan Massal Seluruh Sekolah</span>
                </button>
            </div>
        </div>
    </form>

    <!-- ========================================================================= -->
    <!-- TABEL RIWAYAT ALUMNI TERBARU (DENGAN OPSI PEMULIHAN STATUS)               -->
    <!-- ========================================================================= -->
    @if($recentAlumni->isNotEmpty())
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path></svg>
                        Daftar Alumni Terbaru (Total: {{ $totalAlumniCount }})
                    </h3>
                    <p class="text-xs text-slate-500">Siswa yang sudah lulus tidak muncul lagi di presensi harian kelas.</p>
                </div>
                <a href="{{ route('waka.promotions.export_alumni') }}" class="inline-flex items-center px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold rounded-lg transition space-x-1.5 shadow-xs shrink-0">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Unduh Arsip Alumni (CSV/Excel)</span>
                </a>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider font-bold border-b border-slate-200">
                            <th class="py-2.5 px-4">Nama Siswa</th>
                            <th class="py-2.5 px-4">NISN</th>
                            <th class="py-2.5 px-4">Status</th>
                            <th class="py-2.5 px-4 text-center">Aksi Perbaikan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentAlumni as $a)
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5 px-4 font-bold text-slate-900">{{ $a->name }}</td>
                                <td class="py-2.5 px-4 font-mono text-slate-600">{{ $a->identifier ?? '-' }}</td>
                                <td class="py-2.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        🎓 Alumni (Lulus)
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-center">
                                    <!-- Form Kembalikan ke Kelas Aktif -->
                                    <form action="{{ route('waka.promotions.restore', $a->id) }}" method="POST" class="inline-flex items-center gap-1.5" onsubmit="return confirm('Kembalikan status siswa ini ke kelas aktif?');">
                                        @csrf
                                        <select name="class_id" class="text-[11px] py-1 px-2 border border-slate-300 rounded bg-white text-slate-800" required>
                                            <option value="">-- Masukkan ke Kelas --</option>
                                            @foreach($classes as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-2.5 py-1 bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 border border-slate-200 rounded text-[11px] font-semibold transition" title="Batal Lulus & Masukkan Kelas">
                                            Pulihkan
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<!-- SCRIPT LOGIKA KENAIKAN MASSAL -->
<script>
    function filterStayedStudents() {
        const query = document.getElementById('stayedSearchInput').value.toLowerCase().trim();
        const items = document.querySelectorAll('.stayed-student-item');

        items.forEach(item => {
            const name = item.getAttribute('data-name') || '';
            const className = item.getAttribute('data-class') || '';
            const nisn = item.getAttribute('data-nisn') || '';

            if (name.includes(query) || className.includes(query) || nisn.includes(query)) {
                item.classList.remove('hidden');
                item.classList.add('flex');
            } else {
                item.classList.remove('flex');
                item.classList.add('hidden');
            }
        });
    }

    function updateStayedCount() {
        const checked = document.querySelectorAll('.stayed-checkbox:checked').length;
        const badge = document.getElementById('bulkStayedBadge');
        if (badge) {
            badge.textContent = `${checked} Siswa Tinggal Kelas`;
            if (checked > 0) {
                badge.className = 'text-xs font-bold px-3 py-1.5 bg-amber-100 text-amber-800 rounded-lg border border-amber-200 shrink-0';
            } else {
                badge.className = 'text-xs font-bold px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg shrink-0';
            }
        }
    }

    function confirmBulkProcess(e) {
        const form = document.getElementById('bulkPromotionForm');
        if (form.dataset.confirmed === 'true') {
            return true;
        }

        e.preventDefault();

        const stayedCount = document.querySelectorAll('.stayed-checkbox:checked').length;
        const msg = `<div class="text-left text-xs text-slate-700 space-y-2">` +
                    `<p class="font-semibold text-slate-900">Apakah Anda yakin ingin mengeksekusi kenaikan serentak untuk seluruh kelas?</p>` +
                    `<ul class="list-disc pl-5 space-y-1">` +
                    `<li>Semua siswa <strong>Kelas XII</strong> akan diluluskan menjadi Alumni.</li>` +
                    `<li>Semua siswa <strong>Kelas XI</strong> akan naik ke Kelas XII yang sesuai.</li>` +
                    `<li>Semua siswa <strong>Kelas X</strong> akan naik ke Kelas XI yang sesuai.</li>` +
                    `</ul>` +
                    (stayedCount > 0 
                        ? `<p class="p-2.5 bg-amber-50 text-amber-900 rounded-lg border border-amber-200 font-semibold mt-2">` +
                          `Ada <strong>${stayedCount} siswa</strong> yang Anda tandai <strong>TINGGAL KELAS</strong>.<br>` +
                          `Siswa ini akan <strong>tetap di jurusan yang sama</strong> dan otomatis dialokasikan ke rombel paralel yang masih memiliki kursi kosong (maksimal 36 siswa).</p>` 
                        : `<p class="p-2.5 bg-blue-50 text-blue-800 rounded-lg border border-blue-200 mt-2">Seluruh siswa aktif akan dipromosikan (tidak ada siswa yang tinggal kelas).</p>`) +
                    `</div>`;

        if (typeof confirmAction === 'function') {
            confirmAction(() => {
                form.dataset.confirmed = 'true';
                form.submit();
            }, msg, {
                title: '🚨 Kenaikan Massal Seluruh Sekolah',
                confirmText: 'Ya, Eksekusi Kenaikan Massal',
                isDanger: true,
                icon: 'warning'
            });
        } else {
            if (confirm('Apakah Anda yakin ingin mengeksekusi kenaikan kelas massal seluruh sekolah?')) {
                form.dataset.confirmed = 'true';
                form.submit();
            }
        }
        return false;
    }
</script>
@endsection
