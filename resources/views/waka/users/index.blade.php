@extends('layouts.waka')

@php
    $roleLabels = [
        'siswa'          => 'Data Siswa',
        'orang_tua'      => 'Data Orang Tua',
        'guru'           => 'Guru Pegawai',
        'guru_bk'        => 'Guru BK',
        'waka_kurikulum' => 'Waka Kurikulum',
    ];
    $currentRoleLabel = $roleLabels[$roleFilter] ?? 'Semua Pengguna';
@endphp

@section('title', 'Kelola Pengguna - ' . $currentRoleLabel)

@section('header_actions')
    <div class="flex items-center space-x-2">
        @if($roleFilter === 'guru')
            <a href="{{ route('waka.users.template.guru') }}" class="inline-flex items-center px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg shadow-xs transition space-x-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Unduh Template</span>
            </a>
            <button type="button" onclick="openImportGuruModal()" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Import Multi-File</span>
            </button>
        @elseif($roleFilter === 'waka_kurikulum')
            <a href="{{ route('waka.profile.edit') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-700 border border-purple-200 text-xs font-semibold rounded-lg shadow-xs transition">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Edit Profil Waka Saya</span>
            </a>
        @elseif(!$roleFilter || $roleFilter === 'siswa')
            <button type="button" onclick="openImportModal()" class="inline-flex items-center px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold rounded-lg shadow-xs transition space-x-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Import Siswa (Excel)</span>
            </button>
        @endif

        <a href="{{ route('waka.users.create', ($roleFilter && $roleFilter !== 'all') ? ['role' => $roleFilter] : []) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>Tambah Pengguna</span>
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Notifikasi Sukses / Gagal -->
    @if(session('success'))
        <div class="p-4 bg-blue-50 border border-blue-200 text-blue-900 rounded-xl shadow-xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span class="font-medium text-sm">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-xs flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                <span class="font-medium text-sm">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($roleFilter === 'siswa')
        <!-- Tab Navigasi Khusus Siswa per Angkatan -->
        <div class="bg-white p-2.5 rounded-2xl shadow-xs border border-slate-200/90 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'all']) }}" 
                   class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition {{ (!$tingkat || $tingkat === 'all') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <span>Semua Siswa</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ (!$tingkat || $tingkat === 'all') ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $studentCounts['all'] ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'X']) }}" 
                   class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $tingkat === 'X' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <span class="w-2 h-2 rounded-full {{ $tingkat === 'X' ? 'bg-white' : 'bg-blue-500' }}"></span>
                    <span>Angkatan Kelas X</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $tingkat === 'X' ? 'bg-blue-700 text-white' : 'bg-blue-50 text-blue-700 font-semibold' }}">
                        {{ $studentCounts['X'] ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'XI']) }}" 
                   class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $tingkat === 'XI' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <span class="w-2 h-2 rounded-full {{ $tingkat === 'XI' ? 'bg-white' : 'bg-indigo-500' }}"></span>
                    <span>Angkatan Kelas XI</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $tingkat === 'XI' ? 'bg-blue-700 text-white' : 'bg-indigo-50 text-indigo-700 font-semibold' }}">
                        {{ $studentCounts['XI'] ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'XII']) }}" 
                   class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $tingkat === 'XII' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <span class="w-2 h-2 rounded-full {{ $tingkat === 'XII' ? 'bg-white' : 'bg-violet-500' }}"></span>
                    <span>Angkatan Kelas XII</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $tingkat === 'XII' ? 'bg-blue-700 text-white' : 'bg-violet-50 text-violet-700 font-semibold' }}">
                        {{ $studentCounts['XII'] ?? 0 }}
                    </span>
                </a>
                <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'alumni']) }}" 
                   class="inline-flex items-center space-x-2 px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $tingkat === 'alumni' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    <span>🎓 Alumni / Lulus</span>
                    <span class="px-1.5 py-0.5 rounded-md text-[10px] {{ $tingkat === 'alumni' ? 'bg-emerald-700 text-white' : 'bg-emerald-50 text-emerald-700 font-semibold' }}">
                        {{ $studentCounts['alumni'] ?? 0 }}
                    </span>
                </a>
                @if(($studentCounts['unassigned'] ?? 0) > 0)
                    <a href="{{ route('waka.users.index', ['role' => 'siswa', 'tingkat' => 'none']) }}" 
                       class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl text-xs font-bold transition {{ $tingkat === 'none' ? 'bg-amber-600 text-white shadow-xs' : 'text-amber-700 bg-amber-50/60 hover:bg-amber-100' }}">
                        <span>⚠️ Belum Ada Kelas</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-amber-100 text-amber-800">
                            {{ $studentCounts['unassigned'] }}
                        </span>
                    </a>
                @endif
            </div>

            <!-- Tombol Cepat Kenaikan & Kelulusan -->
            <a href="{{ route('waka.promotions.index') }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition flex-shrink-0">
                <span>🎓 Kenaikan & Kelulusan</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold">Massal</span>
            </a>
        </div>
    @endif

    <!-- Bar Kontrol: Filter Tingkat, Jurusan, Rombel, Pencarian & Aksi -->
    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/90 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            
            <!-- Form Filter & Pencarian -->
            <form method="GET" action="{{ route('waka.users.index') }}" class="flex flex-wrap items-end gap-3 flex-1">
                <!-- Filter Peran Pengguna -->
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Peran Akun</label>
                    <select name="role" onchange="const t = this.form.querySelector('input[name=tingkat]'); if (t) t.value='all'; this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <option value="all" {{ (!$roleFilter || $roleFilter === 'all') ? 'selected' : '' }}>Semua Pengguna</option>
                        <option value="siswa" {{ $roleFilter === 'siswa' ? 'selected' : '' }}>Data Siswa</option>
                        <option value="orang_tua" {{ $roleFilter === 'orang_tua' ? 'selected' : '' }}>Data Orang Tua</option>
                        <option value="guru" {{ $roleFilter === 'guru' ? 'selected' : '' }}>Guru Pegawai</option>
                        <option value="guru_bk" {{ $roleFilter === 'guru_bk' ? 'selected' : '' }}>Guru BK</option>
                        <option value="waka_kurikulum" {{ $roleFilter === 'waka_kurikulum' ? 'selected' : '' }}>Waka Kurikulum</option>
                    </select>
                </div>

                @if($roleFilter === 'guru')
                    <!-- Filter Status Penugasan Wali Kelas -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tugas Wali Kelas</label>
                        <select name="status_wali" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            <option value="all" {{ ($statusWali ?? 'all') == 'all' ? 'selected' : '' }}>Semua Guru Pegawai</option>
                            <option value="wali" {{ ($statusWali ?? '') == 'wali' ? 'selected' : '' }}>⭐ Hanya Wali Kelas</option>
                            <option value="non_wali" {{ ($statusWali ?? '') == 'non_wali' ? 'selected' : '' }}>Bukan Wali Kelas</option>
                        </select>
                    </div>
                @endif

                @if(!$roleFilter || $roleFilter === 'siswa' || $roleFilter === 'guru' || $roleFilter === 'orang_tua')
                    <!-- Parameter Tingkat Kelas (Tersinkron dengan Tab Navigasi Angkatan di Atas) -->
                    <input type="hidden" name="tingkat" value="{{ $tingkat ?? 'all' }}">

                    <!-- Filter Jurusan -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Jurusan</label>
                        <select name="jurusan" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            <option value="all" {{ ($jurusan ?? 'all') == 'all' ? 'selected' : '' }}>Semua Jurusan</option>
                            @foreach($availableJurusan ?? ['AK', 'BD', 'BR', 'DKV', 'LPS', 'MP', 'RPL', 'TKJ'] as $j)
                                <option value="{{ $j }}" {{ ($jurusan ?? '') == $j ? 'selected' : '' }}>{{ $j }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Rombel -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Rombel</label>
                        <select name="rombel" onchange="this.form.submit()" class="px-3 py-2 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                            <option value="all" {{ ($rombel ?? 'all') == 'all' ? 'selected' : '' }}>Semua Rombel</option>
                            @foreach($availableRombel ?? ['1', '2', '3', '4'] as $r)
                                <option value="{{ $r }}" {{ ($rombel ?? '') == $r ? 'selected' : '' }}>Rombel {{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Search Input -->
                <div class="flex-1 min-w-[220px] max-w-sm">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pencarian</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ $search }}" 
                            placeholder="{{ $roleFilter === 'guru' ? 'Cari nama guru, NIP, kelas...' : ($roleFilter === 'siswa' ? 'Cari nama siswa, NISN, kelas...' : 'Cari nama, NIP/NISN, username...') }}" 
                            class="w-full pl-9 pr-3 py-2 text-xs border border-slate-200 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-500 bg-white text-slate-900 placeholder:text-slate-400">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition shadow-xs">
                        Cari
                    </button>
                    @if($search || (($tingkat ?? 'all') !== 'all') || (($jurusan ?? 'all') !== 'all') || (($rombel ?? 'all') !== 'all') || (($statusWali ?? 'all') !== 'all'))
                        <a href="{{ route('waka.users.index', ($roleFilter && $roleFilter !== 'all') ? ['role' => $roleFilter] : []) }}" class="px-3 py-2 text-xs text-slate-600 hover:bg-slate-100 border border-slate-200 rounded-lg font-medium transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

        </div>

        @if((($tingkat ?? 'all') !== 'all') || (($jurusan ?? 'all') !== 'all') || (($rombel ?? 'all') !== 'all') || (($statusWali ?? 'all') !== 'all') || $search)
            <!-- Indikator Filter Aktif -->
            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-xs text-slate-600">
                <span class="font-bold text-slate-700">Filter Aktif:</span>
                @if(($statusWali ?? 'all') !== 'all')
                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-md font-semibold">
                        Tugas: {{ $statusWali === 'wali' ? 'Hanya Wali Kelas' : 'Bukan Wali Kelas' }}
                    </span>
                @endif
                @if(($tingkat ?? 'all') !== 'all')
                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-md font-semibold">
                        Tingkat: {{ $tingkat === 'none' ? ($roleFilter === 'guru' ? 'Bukan Wali / Belum Ditugaskan' : 'Belum Ada Kelas') : ($tingkat === 'alumni' ? 'Alumni / Lulus' : 'Kelas ' . $tingkat) }}
                    </span>
                @endif
                @if(($jurusan ?? 'all') !== 'all')
                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-md font-semibold">
                        Jurusan: {{ $jurusan }}
                    </span>
                @endif
                @if(($rombel ?? 'all') !== 'all')
                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-md font-semibold">
                        Rombel: {{ $rombel }}
                    </span>
                @endif
                @if($search)
                    <span class="inline-flex items-center px-2.5 py-1 bg-blue-50 border border-blue-100 text-blue-700 rounded-md font-semibold">
                        Kata Kunci: "{{ $search }}"
                    </span>
                @endif
                <span class="text-slate-500 ml-auto">Total: <strong class="text-slate-900">{{ $users->total() }}</strong> data</span>
            </div>
        @endif
    </div>

    <!-- Tabel Pengguna -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                 <thead>
                    <tr class="bg-slate-50 text-slate-600 text-[11px] uppercase tracking-wider font-bold border-b border-slate-200">
                        <th class="p-3.5 px-4">Pengguna</th>
                        <th class="p-3.5 px-4 whitespace-nowrap">Username</th>
                        <th class="p-3.5 px-4 whitespace-nowrap">Role</th>
                        <th class="p-3.5 px-4 whitespace-nowrap">Kelas / Penugasan</th>
                        <th class="p-3.5 px-4 whitespace-nowrap">NIP / NISN</th>
                        <th class="p-3.5 px-4 whitespace-nowrap">No. HP</th>
                        <th class="p-3.5 px-4 text-center whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($users as $user)
                    <tr class="hover:bg-blue-50/40 transition">
                        <td class="p-3.5 px-4">
                            <div class="font-bold text-slate-900">{{ $user->name }}</div>
                            <div class="text-xs text-slate-500 font-mono">{{ $user->email }}</div>
                        </td>
                        <td class="p-3.5 px-4 text-slate-700 font-mono text-xs font-semibold whitespace-nowrap">
                            {{ $user->username }}
                        </td>
                        <td class="p-3.5 px-4 whitespace-nowrap">
                            @php
                                $badges = [
                                    'waka_kurikulum' => 'bg-purple-50 text-purple-700 border-purple-200',
                                    'guru'           => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'wali_kelas'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'guru_bk'        => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    'siswa'          => 'bg-slate-50 text-slate-700 border-slate-200',
                                    'orang_tua'      => 'bg-amber-50 text-amber-700 border-amber-200',
                                ];
                                $label = [
                                    'waka_kurikulum' => 'Waka Kurikulum',
                                    'guru'           => 'Guru',
                                    'wali_kelas'     => 'Wali Kelas',
                                    'guru_bk'        => 'Guru BK',
                                    'siswa'          => 'Siswa',
                                    'orang_tua'      => 'Orang Tua',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold rounded-full border whitespace-nowrap {{ $badges[$user->role] ?? 'bg-slate-50 text-slate-700 border-slate-200' }}">
                                {{ $label[$user->role] ?? ucfirst($user->role) }}
                            </span>
                        </td>
                        <td class="p-3.5 px-4 whitespace-nowrap">
                            @if($user->role === 'siswa')
                                @if(($user->status ?? '') === 'lulus')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        🎓 Alumni (Lulus)
                                    </span>
                                @elseif($user->schoolClass)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                        {{ $user->schoolClass->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-rose-600 italic bg-rose-50 px-2 py-0.5 rounded border border-rose-200">Belum Ada Kelas</span>
                                @endif
                            @elseif(in_array($user->role, ['guru', 'wali_kelas']))
                                @if($user->homeroomClass)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $user->homeroomClass->major_badge_color }} border" title="Ditugaskan sebagai Wali Kelas">
                                        Wali: {{ $user->homeroomClass->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 font-mono">Guru Pengajar</span>
                                @endif
                            @elseif($user->role === 'guru_bk')
                                @if($user->bkClasses && $user->bkClasses->count() > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200" title="{{ $user->bkClasses->pluck('name')->implode(', ') }}">
                                        ⭐ {{ $user->bkClasses->count() }} Kelas BK
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 font-mono">Belum Ada Kelas</span>
                                @endif
                            @elseif($user->role === 'orang_tua')
                                @if($user->student)
                                    <div>
                                        <div class="text-xs font-bold text-slate-800">Anak: {{ $user->student->name }}</div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            @if($user->student->schoolClass)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                    {{ $user->student->schoolClass->name }}
                                                </span>
                                            @endif
                                            <span class="text-[11px] font-mono text-slate-500">NIS: {{ $user->student->identifier }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400 font-mono">-</span>
                                @endif
                            @else
                                <span class="text-xs text-slate-400 font-mono">-</span>
                            @endif
                        </td>
                        <td class="p-3.5 px-4 text-slate-600 font-mono text-xs whitespace-nowrap">
                            {{ $user->identifier ?? '-' }}
                        </td>
                        <td class="p-3.5 px-4 text-slate-600 text-xs whitespace-nowrap">
                            {{ $user->phone ?? '-' }}
                        </td>
                        <td class="p-3.5 px-4 text-center whitespace-nowrap">
                            <div class="inline-flex items-center space-x-1.5">
                                <a href="{{ route('waka.users.edit', $user) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Edit Pengguna">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>

                                @if(auth()->id() !== $user->id)
                                <form action="{{ route('waka.users.destroy', $user) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            onclick="confirmDelete(this.form, 'Apakah Anda yakin ingin menghapus pengguna {{ addslashes($user->name) }}?')" 
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                                            title="Hapus Pengguna">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-500">
                            Tidak ada data pengguna ditemukan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $users->links() }}
        </div>
        @endif
    </div>

</div>

<!-- Modal Import Siswa dari Excel / CSV -->
<div id="importModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs hidden p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full border border-slate-200 overflow-hidden transform transition-all">
        <!-- Header Modal -->
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl border border-blue-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                    <h3 class="font-heading font-bold text-slate-900 text-base">Import Multi-Format Data Siswa</h3>
                    <p class="text-xs text-slate-500">Mendukung format presensi sekolah, buku nilai, Excel, CSV, TSV, dan PDF</p>
                </div>
            </div>
            <button type="button" onclick="closeImportModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <!-- Form Import -->
        <form action="{{ route('waka.users.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Target Angkatan (Tingkat) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pilih Target Angkatan (Tingkat) *</label>
                <select id="selectTargetAngkatan" name="target_angkatan" class="w-full text-xs text-slate-700 border border-slate-200 rounded-lg p-2.5 bg-white font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="all">-- Semua Angkatan / Otomatis (Sesuai File) --</option>
                    <option value="X" {{ ($tingkat ?? '') == 'X' ? 'selected' : '' }}>Angkatan Kelas X (Tingkat 10)</option>
                    <option value="XI" {{ ($tingkat ?? '') == 'XI' ? 'selected' : '' }}>Angkatan Kelas XI (Tingkat 11)</option>
                    <option value="XII" {{ ($tingkat ?? '') == 'XII' ? 'selected' : '' }}>Angkatan Kelas XII (Tingkat 12)</option>
                </select>
                <p class="text-[11px] text-slate-500 mt-1">Pilih angkatan sesuai file yang diunggah. Sistem akan otomatis mendeteksi setiap jurusan dan kelas dari sheet/tabel masing-masing.</p>
            </div>

            <!-- Input File (Mendukung Multi-file) -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Pilih File Spreadsheet / Presensi *</label>
                <input type="file" name="files[]" multiple accept=".xlsx, .xls, .csv, .txt, .pdf" required class="w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-200 rounded-lg p-2 bg-white cursor-pointer">
                <p class="text-[11px] text-slate-500 mt-1.5">Mendukung format: <strong>.xlsx</strong>, <strong>.csv</strong>, <strong>.txt</strong>, atau <strong>.pdf</strong>. Bisa pilih lebih dari 1 file sekaligus.</p>
            </div>

            <!-- Petunjuk Format Bebas -->
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 space-y-1.5">
                <div class="font-bold text-slate-900 flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Format Fleksibel Tanpa Wajib Template:</span>
                </div>
                <ul class="list-disc pl-4 space-y-1 text-[11px] text-slate-600">
                    <li><strong>Bisa langsung unggah format presensi asli sekolah</strong> (tabel kehadiran 12 pertemuan, buku nilai, dll).</li>
                    <li>Sistem otomatis mendeteksi kolom: <strong>Nama Siswa</strong>, <strong>NIS / NISN</strong>, dan <strong>Kelas</strong> (termasuk header bertingkat).</li>
                    <li>Jika file Excel memiliki banyak sheet (misal tiap sheet mewakili 1 kelas), semua sheet akan otomatis diimpor sekaligus.</li>
                    <li>Siswa yang sudah ada akan diperbarui datanya secara otomatis tanpa duplikasi.</li>
                </ul>
            </div>

            <!-- Panduan Template Resmi (Opsional) -->
            <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-xl flex items-center justify-between gap-3">
                <div class="text-[11px] text-slate-600">
                    Ingin menggunakan format kolom standar sederhana?
                </div>
                <a href="{{ route('waka.users.template') }}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition inline-flex items-center space-x-1.5 flex-shrink-0 shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Unduh Template CSV</span>
                </a>
            </div>

            <!-- Footer Tombol -->
            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition inline-flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Unggah & Import</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORT MULTI-FILE GURU & PEGAWAI (EXCEL, CSV, TSV, PDF, TXT, JSON) -->
<div id="modalImportGuru" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl w-full max-w-xl p-6 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                </div>
                <div>
                    <h3 class="text-base font-heading font-bold text-slate-900">Import Multi-File Guru & Pegawai</h3>
                    <p class="text-xs text-slate-500">Mendukung semua format file: Excel, PDF, CSV, TSV, TXT, dan JSON</p>
                </div>
            </div>
            <button type="button" onclick="closeImportGuruModal()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
        </div>

        <form method="POST" action="{{ route('waka.users.import.guru') }}" enctype="multipart/form-data" class="space-y-4" onsubmit="document.getElementById('btnSubmitImportGuru').disabled = true; document.getElementById('btnSubmitImportGuru').innerText = 'Sedang Memverifikasi & Mengimport Data Guru...';">
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
                    Pilih File Guru / Pegawai (Bisa Multi-Upload Sekaligus) *
                </label>
                <div class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-xl p-5 text-center bg-slate-50/50 hover:bg-emerald-50/20 transition cursor-pointer" onclick="document.getElementById('inputGuruFiles').click()">
                    <input type="file" id="inputGuruFiles" name="files[]" multiple accept=".xlsx,.xls,.csv,.pdf,.json,.txt,.tsv" class="hidden" onchange="previewGuruFiles(this)" required>
                    <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-800">Klik di sini atau seret file ke area ini</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">Dapat memilih file Excel (.xlsx), PDF rekap guru, CSV Dapodik/Simpatika, atau Teks</p>
                </div>
                <!-- Preview File Terpilih -->
                <div id="guruFilePreviewList" class="mt-2 space-y-1.5 hidden"></div>
            </div>

            <!-- Panduan Deteksi Cerdas Sistem -->
            <div class="p-3 bg-blue-50/60 border border-blue-200 rounded-xl text-xs text-blue-900 space-y-1.5">
                <p class="font-bold flex items-center text-blue-800">
                    <svg class="w-4 h-4 mr-1 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                    Verifikasi Otomatis & Keamanan Data:
                </p>
                <ul class="list-disc pl-5 space-y-0.5 text-[11px] text-slate-700">
                    <li><strong>Deteksi Kolom Cerdas:</strong> Sistem otomatis mendeteksi kolom Nama Guru, NIP / NUPTK, Email, No. HP, dan Status.</li>
                    <li><strong>Tidak Menghapus Data:</strong> Guru yang sudah ada akan diperbarui datanya secara aman tanpa menghapus akun atau jadwal yang sudah berjalan.</li>
                    <li><strong>Hak Akses Terjaga:</strong> Guru yang bertugas sebagai Wali Kelas tidak akan diturunkan perannya.</li>
                    <li><strong>Akun Baru:</strong> Guru baru otomatis dibuatkan akun dengan password awal <code>password123</code>.</li>
                </ul>
            </div>

            <!-- Download Template Shortcut Links -->
            <div class="flex flex-wrap items-center justify-between text-xs pt-1 border-t border-slate-100">
                <span class="text-slate-500 text-[11px]">Format acuan jika dibutuhkan:</span>
                <a href="{{ route('waka.users.template.guru') }}" class="text-emerald-700 hover:text-emerald-800 font-bold hover:underline text-[11px] inline-flex items-center space-x-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Unduh Template Format Guru (.csv / Excel)</span>
                </a>
            </div>

            <!-- Footer Tombol -->
            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeImportGuruModal()" class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit" id="btnSubmitImportGuru" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs transition inline-flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Unggah & Proses Multi-File</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
    }
    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
    }

    function openImportGuruModal() {
        document.getElementById('modalImportGuru').classList.remove('hidden');
    }
    function closeImportGuruModal() {
        document.getElementById('modalImportGuru').classList.add('hidden');
    }

    function previewGuruFiles(input) {
        const preview = document.getElementById('guruFilePreviewList');
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

        Array.from(input.files).forEach((file) => {
            const item = document.createElement('div');
            item.className = 'flex items-center justify-between px-2.5 py-1.5 bg-emerald-50/70 border border-emerald-200/80 rounded-lg text-xs text-slate-800';
            item.innerHTML = `<span class="truncate font-semibold">📁 ${file.name}</span><span class="text-[10px] text-emerald-700 font-mono ml-2">(${(file.size / 1024).toFixed(1)} KB)</span>`;
            preview.appendChild(item);
        });
    }

    window.addEventListener('click', function(e) {
        const modalSiswa = document.getElementById('importModal');
        if (e.target === modalSiswa) {
            closeImportModal();
        }
        const modalGuru = document.getElementById('modalImportGuru');
        if (e.target === modalGuru) {
            closeImportGuruModal();
        }
    });
</script>
@endsection
