@extends('layouts.waka')

@section('title', 'Master Data Kelas & Ruang')

@section('header_actions')
    <div class="flex flex-wrap items-center gap-2">
        <!-- Dropdown Unduh Template -->
        <div class="relative dropdown-container">
            <button onclick="toggleDropdown('dropdown-template-master')" type="button" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold rounded-lg shadow-xs transition">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Unduh Template</span>
                <svg class="w-3 h-3 text-slate-400 transition-transform duration-200 dropdown-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div id="dropdown-template-master" class="dropdown-menu hidden-dropdown absolute right-0 mt-1 w-56 bg-white border border-slate-200 rounded-xl shadow-lg py-1.5 z-50 text-xs">
                <a href="{{ route('master.template', ['type' => 'all']) }}" onclick="closeAllDropdowns()" class="flex items-center px-3.5 py-2 hover:bg-blue-50 text-slate-700 hover:text-blue-700 transition">
                    <span class="font-bold mr-1.5">📑</span> Template Terpadu (Semua)
                </a>
                <a href="{{ route('master.template', ['type' => 'walikelas']) }}" onclick="closeAllDropdowns()" class="flex items-center px-3.5 py-2 hover:bg-blue-50 text-slate-700 hover:text-blue-700 transition">
                    <span class="font-bold mr-1.5">👨‍🏫</span> Template Khusus Wali Kelas
                </a>
                <a href="{{ route('master.template', ['type' => 'ruangan']) }}" onclick="closeAllDropdowns()" class="flex items-center px-3.5 py-2 hover:bg-blue-50 text-slate-700 hover:text-blue-700 transition">
                    <span class="font-bold mr-1.5">🏢</span> Template Khusus Ruangan
                </a>
            </div>
        </div>

        <!-- Tombol Import Multi-File -->
        <button onclick="document.getElementById('modalImportMaster').classList.remove('hidden')" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            <span>Import Multi-File</span>
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Notifikasi Sukses / Peringatan / Error -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-xl shadow-xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span class="font-medium text-sm">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl shadow-xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span class="font-medium text-sm">{{ session('warning') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-xs">
            <ul class="text-xs list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 1. KONTROL FILTER & PENCARIAN DATA KELAS -->
    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Form Filter Jurusan, Tingkat, & Wali Kelas -->
            <form method="GET" action="{{ url('/waka/master') }}" class="flex flex-wrap items-center gap-3 flex-1">
                
                <!-- Filter Tingkat (Kelas) -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tingkat Kelas</label>
                    <select name="tingkat" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:outline-none">
                        <option value="all" {{ $tingkat == 'all' ? 'selected' : '' }}>Semua Tingkat</option>
                        <option value="X" {{ $tingkat == 'X' ? 'selected' : '' }}>Kelas X</option>
                        <option value="XI" {{ $tingkat == 'XI' ? 'selected' : '' }}>Kelas XI</option>
                        <option value="XII" {{ $tingkat == 'XII' ? 'selected' : '' }}>Kelas XII</option>
                    </select>
                </div>

                <!-- Filter Jurusan -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Jurusan</label>
                    <select name="jurusan" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:outline-none">
                        <option value="all" {{ $jurusan == 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                        @foreach($availableJurusan as $j)
                            <option value="{{ $j }}" {{ $jurusan == $j ? 'selected' : '' }}>{{ $j }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Wali Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Wali Kelas</label>
                    <select name="wali_kelas_id" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:outline-none max-w-[180px]">
                        <option value="all" {{ $waliKelasId == 'all' ? 'selected' : '' }}>Semua Wali Kelas</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" {{ $waliKelasId == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Pencarian Teks -->
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Kelas / Guru</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Ketik nama kelas/guru..." class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:outline-none placeholder:text-slate-400">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="self-end flex space-x-2">
                    <button type="submit" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                        Cari
                    </button>
                    @if($tingkat != 'all' || $jurusan != 'all' || $waliKelasId != 'all' || $search)
                        <a href="{{ url('/waka/master') }}" class="px-3 py-2 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold rounded-lg transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- 2. GRID TABEL KELAS & RUANGAN -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Tabel Data Kelas (Lebar 2 Kolom) -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-xs border border-slate-200/90">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-heading font-bold text-slate-900">Daftar Kelas ({{ $classes->count() }} Terdata)</h2>
                        <p class="text-xs text-slate-500">Daftar rombel kelas, jurusan, serta penanggung jawab wali kelas.</p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                            <th class="p-3 border-b border-slate-200 w-12 text-center">No</th>
                            <th class="p-3 border-b border-slate-200">Nama Kelas</th>
                            <th class="p-3 border-b border-slate-200">Jurusan</th>
                            <th class="p-3 border-b border-slate-200">Wali Kelas</th>
                            <th class="p-3 border-b border-slate-200">Guru BK</th>
                            <th class="p-3 border-b border-slate-200 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($classes as $idx => $class)
                        @php
                            $parts = explode(' ', $class->name);
                            $t = $parts[0] ?? 'X';
                            $j = $parts[1] ?? 'Umum';
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition text-sm">
                            <td class="p-3 text-center text-slate-400 font-mono text-xs">{{ $idx + 1 }}</td>
                            <td class="p-3">
                                <div class="font-bold text-slate-900 text-sm">{{ $class->name }}</div>
                                <span class="text-[10px] text-slate-400">ID: #{{ $class->id }}</span>
                            </td>
                            <td class="p-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                    {{ $j }}
                                </span>
                            </td>
                            <td class="p-3">
                                @if($class->homeroomTeacher)
                                    <div class="flex items-center space-x-2">
                                        <div class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                                            {{ substr($class->homeroomTeacher->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-xs text-slate-800">{{ $class->homeroomTeacher->name }}</div>
                                            <div class="text-[10px] text-slate-500 font-mono">{{ $class->homeroomTeacher->identifier ?? 'NIP: -' }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs italic text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">Belum Ditentukan</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if($class->bkTeacher)
                                    <div class="flex items-center space-x-2">
                                        <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">
                                            {{ substr($class->bkTeacher->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-xs text-slate-800">{{ $class->bkTeacher->name }}</div>
                                            <div class="text-[10px] text-indigo-600 font-semibold flex items-center gap-1">
                                                <span>Guru BK</span>
                                                <span class="text-slate-300">•</span>
                                                <span class="font-mono text-slate-500">{{ $class->bkTeacher->identifier ?? '' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[11px] text-slate-400 italic">Belum Ada BK</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <button type="button" 
                                            onclick="openEditClassModal('{{ $class->id }}', '{{ $class->name }}', '{{ $class->homeroom_teacher_id }}', '{{ $class->bk_teacher_id }}')" 
                                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition">
                                        {{ ($class->homeroom_teacher_id || $class->bk_teacher_id) ? 'Ubah Guru' : 'Atur Guru' }}
                                    </button>
                                    <form action="{{ route('class.destroy', $class->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas {{ $class->name }}? Pastikan kelas tidak memiliki siswa!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition" title="Hapus Kelas">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">
                                Tidak ditemukan data kelas yang sesuai dengan filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabel Data Ruangan (Lebar 1 Kolom) -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/90">
            <div class="flex items-center space-x-2.5 mb-4 border-b border-slate-100 pb-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path></svg>
                </div>
                <div>
                    <h2 class="text-base font-heading font-bold text-slate-900">Daftar Ruangan</h2>
                    <p class="text-xs text-slate-500">{{ $rooms->count() }} Ruangan/Lab</p>
                </div>
            </div>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                        <th class="p-2.5 border-b border-slate-200">Kode</th>
                        <th class="p-2.5 border-b border-slate-200">Nama Ruangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rooms as $room)
                    <tr class="hover:bg-blue-50/40 transition text-sm">
                        <td class="p-2.5 text-blue-700 font-mono text-xs font-bold">{{ $room->code ?? '-' }}</td>
                        <td class="p-2.5 font-bold text-slate-800 text-xs">{{ $room->name }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="p-4 text-center text-slate-500 text-xs">Belum ada data ruangan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

</div>

<!-- MODAL EDIT / ATUR WALI KELAS -->
<div id="modalEditClass" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl border border-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-base font-heading font-bold text-slate-900">Atur Penugasan Kelas</h3>
            <button onclick="document.getElementById('modalEditClass').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
        </div>

        <form id="formEditClass" method="POST" action="" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Kelas</label>
                <input type="text" id="editClassName" name="name" required class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Guru Wali Kelas</label>
                <select id="editHomeroomTeacherId" name="homeroom_teacher_id" class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:outline-none">
                    <option value="">-- Belum Ada Wali Kelas (Kosongkan) --</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}">
                            {{ $t->name }} ({{ $t->identifier ? 'NIP: ' . $t->identifier : 'Guru' }})
                        </option>
                    @endforeach
                </select>
                <span class="text-[10px] text-slate-500 mt-1 block">Guru yang dipilih otomatis akan mendapatkan hak akses sebagai Wali Kelas. 1 guru hanya membina 1 kelas.</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Guru BK (Bimbingan Konseling)</label>
                <select id="editBkTeacherId" name="bk_teacher_id" class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:outline-none">
                    <option value="">-- Belum Ada Guru BK (Kosongkan) --</option>
                    @foreach($bkTeachers as $bkt)
                        <option value="{{ $bkt->id }}">
                            {{ $bkt->name }} ({{ $bkt->role === 'guru_bk' ? 'Guru BK' : 'Guru' }})
                        </option>
                    @endforeach
                </select>
                <span class="text-[10px] text-slate-500 mt-1 block">Guru BK bertugas membina & merekap kehadiran/izin siswa di kelas ini. 1 Guru BK dapat membina banyak kelas.</span>
            </div>

            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('modalEditClass').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg border border-slate-200">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORT MULTI-FILE MASTER DATA (WALI KELAS & RUANGAN) -->
<div id="modalImportMaster" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-xl p-6 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </div>
                <div>
                    <h3 class="text-base font-heading font-bold text-slate-900">Import Multi-File Master Data</h3>
                    <p class="text-xs text-slate-500">Auto-Detect & Auto-Verify data Wali Kelas & Ruangan</p>
                </div>
            </div>
            <button onclick="document.getElementById('modalImportMaster').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('master.import') }}" enctype="multipart/form-data" class="space-y-4" onsubmit="document.getElementById('btnSubmitImportMaster').disabled = true; document.getElementById('btnSubmitImportMaster').innerText = 'Sedang Memverifikasi & Mengimport Data...';">
            @csrf

            <!-- Format File Supported Badges -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <div class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center justify-between">
                    <span>Format File yang Didukung Sistem:</span>
                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[10px] font-semibold border border-emerald-200">Auto-Detect 100%</span>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-1 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-700">📊 Excel (.xlsx, .xls)</span>
                    <span class="px-2 py-1 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-700">📑 Dokumen PDF (.pdf)</span>
                    <span class="px-2 py-1 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-700">📋 CSV / TSV (.csv, .tsv)</span>
                    <span class="px-2 py-1 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-700">📦 JSON (.json)</span>
                    <span class="px-2 py-1 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-700">📝 Dokumen Teks (.txt)</span>
                </div>
            </div>

            <!-- Upload Area (Bisa pilih 1 atau banyak file sekaligus) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Pilih File (Bisa Multi-Upload Sekaligus) *
                </label>
                <div class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl p-5 text-center bg-slate-50/50 hover:bg-emerald-50/20 transition cursor-pointer" onclick="document.getElementById('inputMasterFiles').click()">
                    <input type="file" id="inputMasterFiles" name="files[]" multiple accept=".xlsx,.xls,.csv,.pdf,.json,.txt,.tsv" class="hidden" onchange="previewMasterFiles(this)" required>
                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-800">Klik di sini atau seret file ke area ini</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Dapat memilih 1 file terpadu atau beberapa file terpisah (misal: 1 file wali kelas & 1 file ruangan)</p>
                </div>
                <!-- Preview File Terpilih -->
                <div id="masterFilePreviewList" class="mt-2 space-y-1.5 hidden"></div>
            </div>

            <!-- Panduan Deteksi Cerdas Sistem -->
            <div class="p-3 bg-blue-50/60 border border-blue-200 rounded-xl text-xs text-blue-900 space-y-1.5">
                <p class="font-bold flex items-center text-blue-800">
                    <svg class="w-4 h-4 mr-1 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    Cara Kerja Verifikasi Otomatis:
                </p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px] text-slate-700">
                    <li><strong>Wali Kelas:</strong> Sistem mendeteksi nama kelas (misal: <em>X RPL 1, XI TKJ 1</em>) dan nama guru. Jika guru sudah ada, rolenya otomatis dijadikan Wali Kelas. Jika belum ada, sistem membuat akun baru otomatis.</li>
                    <li><strong>Ruangan:</strong> Cukup cantumkan nama ruangan saja (misal: <em>LAB 19, R10</em>) tanpa kode ruangan. Kode ruangan akan otomatis dibuatkan dan didaftarkan oleh sistem.</li>
                    <li><strong>Pengecualian:</strong> Akun guru yang dipertahankan seperti <em>RUSDIYANTO, S.Pd</em> tidak akan terganggu.</li>
                </ul>
            </div>

            <!-- Download Template Shortcut Links -->
            <div class="flex flex-wrap items-center justify-between text-xs pt-1 border-t border-slate-100">
                <span class="text-slate-500 text-[11px]">Butuh format acuan?</span>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('master.template', ['type' => 'all']) }}" class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline text-[11px]">Template Terpadu</a>
                    <span class="text-slate-300">•</span>
                    <a href="{{ route('master.template', ['type' => 'walikelas']) }}" class="text-blue-700 hover:text-blue-800 font-bold hover:underline text-[11px]">Template Wali Kelas</a>
                    <span class="text-slate-300">•</span>
                    <a href="{{ route('master.template', ['type' => 'ruangan']) }}" class="text-purple-700 hover:text-purple-800 font-bold hover:underline text-[11px]">Template Ruangan</a>
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('modalImportMaster').classList.add('hidden')" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 rounded-lg border border-slate-200">Batal</button>
                <button type="submit" id="btnSubmitImportMaster" class="px-5 py-2.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Mulai Verifikasi & Import</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditClassModal(id, name, teacherId, bkId) {
        const form = document.getElementById('formEditClass');
        form.action = '{{ url("/waka/master/class") }}/' + id;
        document.getElementById('editClassName').value = name;
        document.getElementById('editHomeroomTeacherId').value = teacherId || '';
        document.getElementById('editBkTeacherId').value = bkId || '';
        document.getElementById('modalEditClass').classList.remove('hidden');
    }

    function previewMasterFiles(input) {
        const preview = document.getElementById('masterFilePreviewList');
        preview.innerHTML = '';
        if (!input.files || input.files.length === 0) {
            preview.classList.add('hidden');
            return;
        }
        preview.classList.remove('hidden');
        const header = document.createElement('div');
        header.className = 'text-[11px] font-bold text-emerald-800 mb-1';
        header.innerText = input.files.length + ' file dipilih untuk diverifikasi:';
        preview.appendChild(header);

        Array.from(input.files).forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'flex items-center justify-between px-2.5 py-1.5 bg-emerald-50/70 border border-emerald-200/80 rounded-lg text-xs text-slate-800';
            item.innerHTML = `<span class="truncate font-semibold">📁 ${file.name}</span><span class="text-[10px] text-emerald-700 font-mono ml-2">(${(file.size / 1024).toFixed(1)} KB)</span>`;
            preview.appendChild(item);
        });
    }
</script>
@endsection
