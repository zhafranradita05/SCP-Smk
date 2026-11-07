@extends('layouts.waka')

@section('title', 'Kenaikan Kelas & Kelulusan Siswa')

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

    <!-- Alert Edukasi Status Alumni -->
    @if(!$hasStatusCol)
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl shadow-xs flex items-start space-x-3 text-xs">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <span class="font-bold">Info Status Alumni:</span> Jalankan perintah <code class="px-1.5 py-0.5 bg-amber-100 font-mono rounded">php artisan migrate</code> di terminal Anda agar kolom status alumni permanen aktif.
            </div>
        </div>
    @endif

    <!-- Panel Informasi & Ringkasan -->
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

    <!-- TAB SWITCHER: DUA PILIHAN METODE KENAIKAN -->
    <div class="bg-slate-100 p-1.5 rounded-2xl border border-slate-200 flex flex-col sm:flex-row gap-1.5">
        <button type="button" onclick="switchTab('single')" id="tabBtnSingle" class="flex-1 py-3 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2.5 shadow-xs bg-white text-blue-700">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            <span>Opsi 1: Kenaikan Per Rombel (Checklist Siswa)</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">Rekomendasi jika ada catatan</span>
        </button>

        <button type="button" onclick="switchTab('bulk')" id="tabBtnBulk" class="flex-1 py-3 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2.5 text-slate-600 hover:text-slate-900 hover:bg-white/60">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <span>Opsi 2: Kenaikan Massal Serentak (1-Klik Seluruh Sekolah)</span>
            <span class="text-[10px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Cepat & Otomatis</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- PANEL OPSI 1: KENAIKAN PER ROMBEL (DENGAN CHECKLIST TELITI)              -->
    <!-- ========================================================================= -->
    <div id="panelSingle" class="space-y-6">
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
            <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold">1</span>
                Pilih Kelas Asal & Tindakan Kenaikan
            </h2>
            <p class="text-xs text-slate-500 mb-5">Pilih rombel asal yang akan diproses kenaikan kelas atau kelulusannya satu per satu.</p>

            <!-- Dropdown Pilih Kelas Asal -->
            <form method="GET" action="{{ route('waka.promotions.index') }}" class="mb-6">
                <div class="max-w-md">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kelas Asal Siswa *</label>
                    <div class="flex gap-2">
                        <select name="source_class_id" onchange="this.form.submit()" class="w-full text-xs font-semibold p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="">-- Pilih Rombel Kelas Asal --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ $sourceClassId == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->students_count }} Siswa)
                                </option>
                            @endforeach
                        </select>
                        <noscript>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg">Pilih</button>
                        </noscript>
                    </div>
                </div>
            </form>

            @if($sourceClass)
                <!-- Form Proses Kenaikan / Kelulusan Single Class -->
                <form action="{{ route('waka.promotions.process') }}" method="POST" id="promotionForm" onsubmit="return confirmProcess(event)">
                    @csrf
                    <input type="hidden" name="source_class_id" value="{{ $sourceClass->id }}">

                    <!-- Konfigurasi Aksi Kenaikan / Kelulusan -->
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Tentukan Tindakan untuk Siswa Terpilih:</div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Opsi: Naik Kelas -->
                            <label id="promoteCard" class="flex items-start gap-3 p-3.5 border rounded-xl cursor-pointer transition {{ !$isFinalYearClass ? 'border-blue-500 bg-blue-50/60 shadow-xs' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                                <input type="radio" name="action_type" value="promote" id="actionPromote" {{ !$isFinalYearClass ? 'checked' : '' }} onchange="toggleActionType()" class="w-4 h-4 mt-0.5 text-blue-600 focus:ring-blue-500">
                                <div class="flex-1">
                                    <span class="block text-xs font-bold text-slate-900">Naikkan ke Kelas Berikutnya</span>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">Memindahkan siswa yang dicentang ke rombel jenjang di atasnya (contoh: X &rarr; XI, XI &rarr; XII).</span>
                                    
                                    <div id="targetClassWrapper" class="mt-3 {{ $isFinalYearClass ? 'hidden' : '' }}">
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Kelas Tujuan:</label>
                                        <select name="target_class_id" id="targetClassSelect" onchange="updateRowBadges()" class="w-full text-xs font-semibold p-2 border rounded-lg border-slate-300 bg-white text-slate-900 focus:border-blue-500">
                                            <option value="">-- Pilih Kelas Tujuan --</option>
                                            @foreach($classes as $c)
                                                @if($c->id != $sourceClass->id)
                                                    <option value="{{ $c->id }}" {{ ($suggestedTargetClassId == $c->id) ? 'selected' : '' }}>
                                                        {{ $c->name }} (Saat ini: {{ $c->students_count }} Siswa)
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </label>

                            <!-- Opsi: Luluskan Siswa (Alumni) -->
                            <label id="graduateCard" class="flex items-start gap-3 p-3.5 border rounded-xl cursor-pointer transition {{ $isFinalYearClass ? 'border-blue-500 bg-blue-50/60 shadow-xs' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                                <input type="radio" name="action_type" value="graduate" id="actionGraduate" {{ $isFinalYearClass ? 'checked' : '' }} onchange="toggleActionType()" class="w-4 h-4 mt-0.5 text-blue-600 focus:ring-blue-500">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="block text-xs font-bold text-slate-900">🎓 Luluskan Siswa (Status Alumni)</span>
                                        @if($isFinalYearClass)
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded">Disarankan</span>
                                        @endif
                                    </div>
                                    <span class="block text-[11px] text-slate-500 mt-0.5">
                                        Siswa dilepas dari kelas (<code class="font-mono text-[10px]">class_id = null</code>) dan berstatus Alumni. Riwayat presensi masa lalu tidak terhapus.
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Checklist Siswa -->
                    <div class="mt-6 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-blue-600 text-white text-[11px] font-bold">2</span>
                                    Checklist Siswa Kelas {{ $sourceClass->name }} (Total: {{ $students->count() }} Siswa)
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Centang siswa yang <strong>NAIK KELAS</strong> atau <strong>LULUS</strong>. Jika ada siswa yang <strong>TINGGAL KELAS</strong>, hapus centangnya.
                                </p>
                            </div>

                            <!-- Bar Kontrol Checklist -->
                            <div class="flex items-center gap-3">
                                <button type="button" onclick="toggleSelectAll(true)" class="text-xs text-blue-600 hover:text-blue-800 font-bold hover:underline">
                                    Centang Semua
                                </button>
                                <span class="text-slate-300">|</span>
                                <button type="button" onclick="toggleSelectAll(false)" class="text-xs text-slate-600 hover:text-slate-800 font-medium hover:underline">
                                    Kosongkan Semua
                                </button>
                            </div>
                        </div>

                        <!-- Counter Bar -->
                        <div class="flex items-center justify-between p-3 bg-blue-50/70 border border-blue-100 rounded-xl text-xs text-blue-900">
                            <span class="font-medium">
                                <strong id="selectedCountText">{{ $students->count() }}</strong> dari <strong>{{ $students->count() }}</strong> siswa terpilih untuk diproses.
                            </span>
                            <span class="text-[11px] text-slate-600" id="stayedCountText">
                                0 siswa tidak dicentang (tetap di {{ $sourceClass->name }}).
                            </span>
                        </div>

                        <!-- Tabel Siswa -->
                        <div class="overflow-x-auto border border-slate-200 rounded-xl">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider font-bold border-b border-slate-200">
                                        <th class="py-3 px-4 w-12 text-center">
                                            <input type="checkbox" id="checkAllHeader" checked onchange="handleHeaderCheck(this)" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500">
                                        </th>
                                        <th class="py-3 px-4">Nama Siswa</th>
                                        <th class="py-3 px-4">NISN / Identifier</th>
                                        <th class="py-3 px-4">Username</th>
                                        <th class="py-3 px-4">Status Proses Kenaikan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($students as $index => $s)
                                        <tr class="student-row hover:bg-slate-50/80 transition" id="row-{{ $s->id }}">
                                            <td class="py-3 px-4 text-center">
                                                <input type="checkbox" name="student_ids[]" value="{{ $s->id }}" checked onchange="updateSelectionState()" class="student-checkbox w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500 cursor-pointer">
                                            </td>
                                            <td class="py-3 px-4 font-bold text-slate-900 flex items-center space-x-2.5">
                                                <div class="w-7 h-7 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-[10px] uppercase">
                                                    {{ substr($s->name, 0, 2) }}
                                                </div>
                                                <span>{{ $s->name }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-slate-600 font-mono">{{ $s->identifier ?? '-' }}</td>
                                            <td class="py-3 px-4 text-slate-500">{{ $s->username }}</td>
                                            <td class="py-3 px-4">
                                                <span class="status-badge-checked inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    ✓ Naik / Lulus
                                                </span>
                                                <span class="status-badge-unchecked hidden items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    ⚠️ Tinggal Kelas (Tetap di {{ $sourceClass->name }})
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-slate-400">
                                                Tidak ada siswa aktif di kelas {{ $sourceClass->name }}.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($students->isNotEmpty())
                            <div class="pt-4 flex items-center justify-end space-x-3">
                                <a href="{{ route('waka.promotions.index') }}" class="px-4 py-2.5 text-xs text-slate-600 hover:bg-slate-100 border border-slate-200 rounded-lg font-semibold transition">
                                    Batal
                                </a>
                                <button type="submit" id="btnSubmitProcess" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-xs transition flex items-center space-x-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span id="btnSubmitText">Simpan & Proses Kenaikan Kelas</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </form>
            @else
                <!-- Placeholder Ketika Belum Memilih Kelas Asal -->
                <div class="py-12 border-2 border-dashed border-slate-200 rounded-2xl text-center space-y-2">
                    <div class="w-12 h-12 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">Pilih Kelas Asal untuk Memulai</h3>
                    <p class="text-xs text-slate-500 max-w-sm mx-auto">
                        Pilih salah satu kelas di dropdown atas untuk melihat daftar siswa, mereview yang naik kelas, dan menandai jika ada yang tinggal kelas.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- PANEL OPSI 2: KENAIKAN MASSAL SERENTAK (1-KLIK SELURUH SEKOLAH)          -->
    <!-- ========================================================================= -->
    <div id="panelBulk" class="space-y-6 hidden">
        <form action="{{ route('waka.promotions.bulk') }}" method="POST" id="bulkPromotionForm" onsubmit="return confirmBulkProcess(event)">
            @csrf

            <!-- Hero Box Kenaikan Massal -->
            <div class="bg-gradient-to-br from-slate-900 to-blue-900 text-white p-6 sm:p-8 rounded-2xl shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold bg-blue-500/30 text-blue-200 border border-blue-400/30 mb-2">
                            🚀 Kenaikan Serentak Akhir Tahun Ajaran
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-white">Proses Kenaikan Massal Seluruh Sekolah</h2>
                        <p class="text-xs text-blue-200 mt-1 max-w-2xl">
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

            <!-- BAGIAN PENGECUALIAN: SISWA TINGGAL KELAS (JIKA ADA) -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            Pengecualian: Siswa yang TINGGAL KELAS (Opsional)
                        </h3>
                        <p class="text-xs text-slate-500">
                            Secara default, <strong>seluruh siswa dianggap naik kelas</strong>. Jika ada 1 atau 2 siswa yang tidak naik kelas, cari namanya di bawah ini dan centang untuk dikecualikan.
                        </p>
                    </div>
                    <span id="bulkStayedBadge" class="text-xs font-bold px-3 py-1 bg-slate-100 text-slate-700 rounded-lg">
                        0 Siswa Tinggal Kelas
                    </span>
                </div>

                <!-- Input Pencarian Siswa Pengecualian -->
                <div class="max-w-md">
                    <input type="text" id="stayedSearchInput" onkeyup="filterStayedStudents()" placeholder="Cari nama siswa atau kelas (misal: Budi / RPL)..." class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                </div>

                <!-- List Siswa untuk Dipilih Tinggal Kelas -->
                <div class="max-h-60 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 text-xs">
                    @forelse($allActiveStudents as $std)
                        <label class="stayed-student-item flex items-center justify-between p-2.5 hover:bg-slate-50 cursor-pointer transition" data-name="{{ strtolower($std->name) }}" data-class="{{ strtolower($std->schoolClass->name ?? '') }}">
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" name="stayed_student_ids[]" value="{{ $std->id }}" onchange="updateStayedCount()" class="stayed-checkbox w-4 h-4 text-amber-600 rounded border-slate-300 focus:ring-amber-500">
                                <div>
                                    <span class="font-bold text-slate-900">{{ $std->name }}</span>
                                    <span class="text-slate-400 font-mono text-[11px] ml-1">({{ $std->identifier ?? '-' }})</span>
                                </div>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                {{ $std->schoolClass->name ?? 'Belum ada kelas' }}
                            </span>
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
                    <span class="text-xs text-slate-500 font-mono">Total: {{ count($bulkMappingPreview) }} Rombel</span>
                </div>

                <div class="overflow-x-auto border border-slate-200 rounded-xl">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 uppercase tracking-wider font-bold border-b border-slate-200">
                                <th class="py-2.5 px-4">Kelas Asal (Tahun Ini)</th>
                                <th class="py-2.5 px-4">Siswa Aktif</th>
                                <th class="py-2.5 px-4">Arah Perpindahan</th>
                                <th class="py-2.5 px-4">Kelas Tujuan (Tahun Baru)</th>
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
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TABEL RIWAYAT ALUMNI TERBARU (DENGAN OPSI BATAL LULUS)                 -->
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

<!-- SCRIPT LOGIKA INTERAKTIF (SINGLE & BULK) -->
<script>
    // 1. Switch Antar Tab (Single vs Bulk)
    function switchTab(mode) {
        const panelSingle = document.getElementById('panelSingle');
        const panelBulk = document.getElementById('panelBulk');
        const tabBtnSingle = document.getElementById('tabBtnSingle');
        const tabBtnBulk = document.getElementById('tabBtnBulk');

        if (mode === 'bulk') {
            panelSingle.classList.add('hidden');
            panelBulk.classList.remove('hidden');

            tabBtnBulk.className = 'flex-1 py-3 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2.5 shadow-xs bg-white text-emerald-700';
            tabBtnSingle.className = 'flex-1 py-3 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2.5 text-slate-600 hover:text-slate-900 hover:bg-white/60';
            window.location.hash = 'bulk';
        } else {
            panelBulk.classList.add('hidden');
            panelSingle.classList.remove('hidden');

            tabBtnSingle.className = 'flex-1 py-3 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2.5 shadow-xs bg-white text-blue-700';
            tabBtnBulk.className = 'flex-1 py-3 px-4 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2.5 text-slate-600 hover:text-slate-900 hover:bg-white/60';
            window.location.hash = 'single';
        }
    }

    // 2. Logika Opsi 1 (Single / Per Rombel)
    function toggleActionType() {
        const isPromote = document.getElementById('actionPromote').checked;
        const targetWrapper = document.getElementById('targetClassWrapper');
        const promoteCard = document.getElementById('promoteCard');
        const graduateCard = document.getElementById('graduateCard');
        const btnText = document.getElementById('btnSubmitText');

        if (isPromote) {
            targetWrapper.classList.remove('hidden');
            promoteCard.classList.add('border-blue-500', 'bg-blue-50/60', 'shadow-xs');
            promoteCard.classList.remove('border-slate-200', 'bg-white');
            graduateCard.classList.remove('border-blue-500', 'bg-blue-50/60', 'shadow-xs');
            graduateCard.classList.add('border-slate-200', 'bg-white');
            if (btnText) btnText.textContent = 'Simpan & Proses Kenaikan Kelas';
        } else {
            targetWrapper.classList.add('hidden');
            graduateCard.classList.add('border-blue-500', 'bg-blue-50/60', 'shadow-xs');
            graduateCard.classList.remove('border-slate-200', 'bg-white');
            promoteCard.classList.remove('border-blue-500', 'bg-blue-50/60', 'shadow-xs');
            promoteCard.classList.add('border-slate-200', 'bg-white');
            if (btnText) btnText.textContent = 'Simpan & Luluskan Siswa Terpilih';
        }

        updateRowBadges();
    }

    function toggleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = checked;
        });
        const headerCb = document.getElementById('checkAllHeader');
        if (headerCb) headerCb.checked = checked;

        updateSelectionState();
    }

    function handleHeaderCheck(headerCb) {
        toggleSelectAll(headerCb.checked);
    }

    function updateSelectionState() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        let selectedCount = 0;
        const total = checkboxes.length;

        checkboxes.forEach(cb => {
            const row = document.getElementById('row-' + cb.value);
            if (row) {
                const checkedBadge = row.querySelector('.status-badge-checked');
                const uncheckedBadge = row.querySelector('.status-badge-unchecked');

                if (cb.checked) {
                    selectedCount++;
                    checkedBadge.classList.remove('hidden');
                    checkedBadge.classList.add('inline-flex');
                    uncheckedBadge.classList.remove('inline-flex');
                    uncheckedBadge.classList.add('hidden');
                    row.classList.remove('bg-amber-50/40');
                } else {
                    checkedBadge.classList.remove('inline-flex');
                    checkedBadge.classList.add('hidden');
                    uncheckedBadge.classList.remove('hidden');
                    uncheckedBadge.classList.add('inline-flex');
                    row.classList.add('bg-amber-50/40');
                }
            }
        });

        const selectedText = document.getElementById('selectedCountText');
        const stayedText = document.getElementById('stayedCountText');
        const headerCb = document.getElementById('checkAllHeader');

        if (selectedText) selectedText.textContent = selectedCount;
        if (stayedText) {
            const stayedCount = total - selectedCount;
            stayedText.textContent = `${stayedCount} siswa tidak dicentang (tetap di kelas asal).`;
        }

        if (headerCb) {
            headerCb.checked = (selectedCount === total && total > 0);
        }

        updateRowBadges();
    }

    function updateRowBadges() {
        const isPromote = document.getElementById('actionPromote') ? document.getElementById('actionPromote').checked : true;
        const targetSelect = document.getElementById('targetClassSelect');
        const targetName = targetSelect && targetSelect.selectedIndex > 0 ? targetSelect.options[targetSelect.selectedIndex].text.split('(')[0].trim() : 'Kelas Tujuan';

        document.querySelectorAll('.status-badge-checked').forEach(badge => {
            if (isPromote) {
                badge.textContent = `✓ Naik ke ${targetName}`;
                badge.className = 'status-badge-checked inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200';
            } else {
                badge.textContent = '🎓 Lulus (Alumni)';
                badge.className = 'status-badge-checked inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200';
            }
        });
    }

    function confirmProcess(e) {
        const form = document.getElementById('promotionForm');
        if (form.dataset.confirmed === 'true') {
            return true;
        }

        e.preventDefault();

        const isPromote = document.getElementById('actionPromote').checked;
        const checkboxes = document.querySelectorAll('.student-checkbox:checked');
        const total = document.querySelectorAll('.student-checkbox').length;
        const selectedCount = checkboxes.length;
        const stayedCount = total - selectedCount;

        if (selectedCount === 0) {
            showAlertModal('Pilih minimal 1 siswa untuk diproses!', 'warning', 'Peringatan');
            return false;
        }

        if (isPromote) {
            const targetSelect = document.getElementById('targetClassSelect');
            if (!targetSelect.value) {
                showAlertModal('Silakan pilih kelas tujuan terlebih dahulu!', 'warning', 'Peringatan');
                targetSelect.focus();
                return false;
            }
            const targetName = targetSelect.options[targetSelect.selectedIndex].text.split('(')[0].trim();
            const msg = `Apakah Anda yakin ingin menaikkan <strong>${selectedCount}</strong> siswa ke <strong>${targetName}</strong>?` +
                        (stayedCount > 0 ? `<br><br><span class="p-2 bg-amber-50 text-amber-800 rounded border border-amber-200 text-xs block font-semibold">CATATAN: Ada ${stayedCount} siswa yang TIDAK dicentang dan akan TETAP TINGGAL di kelas asal.</span>` : '');
            
            confirmAction(() => {
                form.dataset.confirmed = 'true';
                form.submit();
            }, msg, {
                title: 'Konfirmasi Kenaikan Kelas',
                confirmText: 'Ya, Naikkan Siswa',
                icon: 'question'
            });
            return false;
        } else {
            const msg = `Apakah Anda yakin ingin meluluskan <strong>${selectedCount}</strong> siswa ini ke status <strong>Alumni</strong>?<br><br>` +
                        `Siswa yang diluluskan akan dilepas dari rombel aktif, namun seluruh riwayat presensi masa lalu tetap aman.` +
                        (stayedCount > 0 ? `<br><br><span class="p-2 bg-amber-50 text-amber-800 rounded border border-amber-200 text-xs block font-semibold">CATATAN: Ada ${stayedCount} siswa yang TIDAK dicentang dan akan tetap aktif di kelas asal.</span>` : '');
            
            confirmAction(() => {
                form.dataset.confirmed = 'true';
                form.submit();
            }, msg, {
                title: 'Konfirmasi Kelulusan Siswa',
                confirmText: 'Ya, Luluskan Siswa',
                icon: 'question'
            });
            return false;
        }
    }

    // 3. Logika Opsi 2 (Kenaikan Massal & Pengecualian)
    function filterStayedStudents() {
        const query = document.getElementById('stayedSearchInput').value.toLowerCase().trim();
        const items = document.querySelectorAll('.stayed-student-item');

        items.forEach(item => {
            const name = item.getAttribute('data-name');
            const className = item.getAttribute('data-class');
            if (name.includes(query) || className.includes(query)) {
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
                badge.className = 'text-xs font-bold px-3 py-1 bg-amber-100 text-amber-800 rounded-lg border border-amber-200';
            } else {
                badge.className = 'text-xs font-bold px-3 py-1 bg-slate-100 text-slate-700 rounded-lg';
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
                        ? `<p class="p-2 bg-amber-50 text-amber-800 rounded border border-amber-200 font-semibold mt-2">Ada ${stayedCount} siswa yang Anda tandai TINGGAL KELAS dan tidak akan ikut naik.</p>` 
                        : `<p class="p-2 bg-blue-50 text-blue-800 rounded border border-blue-200 mt-2">Seluruh siswa aktif akan dipromosikan (tidak ada yang tinggal kelas).</p>`) +
                    `</div>`;

        confirmAction(() => {
            form.dataset.confirmed = 'true';
            form.submit();
        }, msg, {
            title: '🚨 Kenaikan Massal Seluruh Sekolah',
            confirmText: 'Ya, Eksekusi Sekarang',
            isDanger: true,
            icon: 'warning'
        });
        return false;
    }

    // Inisialisasi Tab & Mode
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash === '#bulk') {
            switchTab('bulk');
        } else {
            switchTab('single');
        }

        if (document.getElementById('actionPromote')) {
            toggleActionType();
            updateSelectionState();
        }
    });
</script>
@endsection
