@extends('layouts.waka')

@section('title', 'Kelola Jadwal & Siklus Blok')

@section('header_actions')
    @if(!$setting->is_schedule_locked)
        <button type="button" onclick="openImportScheduleModal()" class="inline-flex items-center px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold rounded-lg shadow-xs transition space-x-2" title="Import jadwal dari file PDF, Excel, CSV, atau JSON">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            <span>Import Jadwal (Multi-File)</span>
        </button>
    @else
        <span class="inline-flex items-center px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200">
            🔒 Jadwal Terkunci
        </span>
    @endif
@endsection

@section('content')
<div class="space-y-8">

    <!-- Notifikasi -->
    @if(session('success'))
        <div class="p-4 bg-blue-50 border border-blue-200 text-blue-900 rounded-xl shadow-xs flex items-center space-x-2">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl shadow-xs font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span class="text-sm">{{ session('error') }}</span>
        </div>
    @endif

    <!-- 1. PANEL PENGATURAN PUTARAN BLOK & LOCK SCHEDULE -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 p-6">
        <h2 class="text-lg font-heading font-bold text-slate-900 mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Pengaturan Siklus Putaran & Kunci Jadwal
        </h2>
        <p class="text-xs text-slate-500 mb-6">Tentukan siklus blok yang sedang aktif serta kunci jadwal agar tidak bisa diubah.</p>

        <form action="{{ route('waka.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center mb-5">
                <!-- Tahun Ajaran Sistem -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Tahun Ajaran Aktif:</label>
                    <select name="academic_year" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-semibold">
                        <option value="2024/2025" {{ ($setting->academic_year ?? '2024/2025') == '2024/2025' ? 'selected' : '' }}>2024/2025</option>
                        <option value="2025/2026" {{ ($setting->academic_year ?? '') == '2025/2026' ? 'selected' : '' }}>2025/2026</option>
                        <option value="2026/2027" {{ ($setting->academic_year ?? '') == '2026/2027' ? 'selected' : '' }}>2026/2027</option>
                        <option value="2027/2028" {{ ($setting->academic_year ?? '') == '2027/2028' ? 'selected' : '' }}>2027/2028</option>
                    </select>
                </div>

                <!-- Semester Sistem -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Semester Aktif:</label>
                    <select name="active_semester" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-semibold">
                        <option value="ganjil" {{ ($setting->active_semester ?? 'ganjil') == 'ganjil' ? 'selected' : '' }}>Semester Ganjil (Juli - Des)</option>
                        <option value="genap" {{ ($setting->active_semester ?? '') == 'genap' ? 'selected' : '' }}>Semester Genap (Jan - Jun)</option>
                    </select>
                </div>

                <!-- Pilihan Putaran -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Putaran Aktif Minggu Ini:</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 p-2.5 border rounded-lg cursor-pointer flex-1 transition {{ $setting->active_cycle == 'putaran_1' ? 'border-blue-500 bg-blue-50/70 text-blue-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input type="radio" name="active_cycle" value="putaran_1" {{ $setting->active_cycle == 'putaran_1' ? 'checked' : '' }} class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs">Putaran 1</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 border rounded-lg cursor-pointer flex-1 transition {{ $setting->active_cycle == 'putaran_2' ? 'border-blue-500 bg-blue-50/70 text-blue-900 font-bold shadow-xs' : 'border-slate-200 hover:bg-slate-50' }}">
                            <input type="radio" name="active_cycle" value="putaran_2" {{ $setting->active_cycle == 'putaran_2' ? 'checked' : '' }} class="w-4 h-4 text-blue-600 focus:ring-blue-500">
                            <span class="text-xs">Putaran 2</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Lock Schedule Checkbox -->
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="is_schedule_locked" value="1" {{ $setting->is_schedule_locked ? 'checked' : '' }} class="w-4 h-4 mt-0.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                    <div>
                        <span class="block font-bold text-slate-900 text-xs">Kunci Jadwal (Lock Schedule)</span>
                        <span class="block text-[11px] text-slate-500 mt-0.5">Jika dicentang, penambahan dan penghapusan jadwal dinonaktifkan.</span>
                    </div>
                </label>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                    Simpan Perubahan Siklus & Kunci
                </button>
            </div>
        </form>
    </div>

    <!-- 2. FORM INPUT JADWAL BARU -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="text-lg font-heading font-bold text-slate-900">Tambah Jadwal Pelajaran</h2>
                <p class="text-xs text-slate-500">Sistem dilengkapi validasi anti-tabrakan waktu, ruang, dan kelas.</p>
            </div>
            <div class="flex items-center gap-2.5">
                @if(!$setting->is_schedule_locked)
                    <button type="button" onclick="openImportScheduleModal()" class="inline-flex items-center px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold rounded-lg shadow-xs transition space-x-2" title="Import jadwal dari file PDF, Excel, CSV, atau JSON">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Import Jadwal (Multi-File)</span>
                    </button>
                @else
                    <span class="px-3 py-1 bg-slate-100 text-slate-700 text-xs font-semibold rounded-full border border-slate-200">
                        🔒 Kunci Aktif
                    </span>
                @endif
            </div>
        </div>

        <form action="{{ route('waka.schedules.store') }}" method="POST">
            @csrf
            <fieldset {{ $setting->is_schedule_locked ? 'disabled' : '' }} class="{{ $setting->is_schedule_locked ? 'opacity-60 cursor-not-allowed' : '' }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <!-- Guru -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Guru *</label>
                        <select name="teacher_id" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            <option value="">-- Pilih Guru --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ old('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Kelas *</label>
                        <select name="class_id" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-semibold" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($majors as $mCode => $m)
                                <optgroup label="Jurusan {{ $mCode }} ({{ $m['name'] }})">
                                    @foreach($m['classes'] as $cls)
                                        <option value="{{ $cls['id'] }}" {{ old('class_id') == $cls['id'] ? 'selected' : '' }}>{{ $cls['name'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <!-- Ruangan -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Ruangan *</label>
                        <select name="room_id" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            <option value="">-- Pilih Ruang --</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ old('room_id') == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mata Pelajaran -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Mata Pelajaran *</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Contoh: Pemrograman Web" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                    </div>

                    <!-- Putaran -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Putaran *</label>
                        <select name="cycle" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            <option value="putaran_1" {{ old('cycle') == 'putaran_1' ? 'selected' : '' }}>Putaran 1</option>
                            <option value="putaran_2" {{ old('cycle') == 'putaran_2' ? 'selected' : '' }}>Putaran 2</option>
                        </select>
                    </div>

                    <!-- Hari -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Hari *</label>
                        <select name="day" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari)
                                <option value="{{ $hari }}" {{ old('day') == $hari ? 'selected' : '' }}>{{ $hari }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Jam Mulai -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Jam Mulai *</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                    </div>

                    <!-- Jam Selesai -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Jam Selesai *</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" class="w-full text-xs p-2.5 border rounded-lg border-slate-200 bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                    </div>
                </div>

                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-lg shadow-xs transition">
                    + Simpan Jadwal Pelajaran
                </button>
            </fieldset>
        </form>
    </div>

    <!-- 3. TABEL DAFTAR JADWAL TERDAFTAR DENGAN FILTER JURUSAN & KELAS -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/90 overflow-hidden">
        
        <!-- Header Tabel & Action -->
        <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="font-heading font-bold text-slate-900 text-lg">Daftar Jadwal Pelajaran</h2>
                    <span id="badgeTotalJadwal" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $schedules->count() }} Jadwal
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Kelola dan pantau jadwal pelajaran yang dikelompokkan per jurusan dan per kelas binaan secara rapi.</p>
            </div>
            
            <div class="flex items-center gap-2.5">
                @if(!$setting->is_schedule_locked)
                    <button type="button" onclick="openImportScheduleModal()" class="inline-flex items-center px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold rounded-lg shadow-xs transition space-x-2" title="Import jadwal dari file PDF, Excel, CSV, atau JSON">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Import Jadwal (Multi-File)</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- 1. TABS NAVIGASI JURUSAN (PILIHAN JURUSAN CEPAT) -->
        <div class="px-6 pt-4 pb-2 border-b border-slate-100 bg-slate-50/70">
            <div class="flex items-center gap-2 overflow-x-auto pb-2" id="majorTabsContainer">
                <!-- Tab Semua Jurusan -->
                <button type="button" 
                    onclick="filterMajor('all')" 
                    id="tab-major-all"
                    class="major-tab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $selectedMajor === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Semua Jurusan</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $selectedMajor === 'all' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $totalAllSchedules }}</span>
                </button>

                <!-- Tab Per Jurusan (Dinamis dari Kelas Sekolah) -->
                @foreach($majors as $mCode => $m)
                    <button type="button" 
                        onclick="filterMajor('{{ $mCode }}')" 
                        id="tab-major-{{ $mCode }}"
                        class="major-tab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $selectedMajor === $mCode ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                        <span>{{ $mCode }}</span>
                        <span class="text-[11px] font-normal opacity-80 hidden sm:inline">• {{ $m['name'] }}</span>
                        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $selectedMajor === $mCode ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700' }}">{{ $m['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <!-- 2. TOOLBAR FILTER KELAS, SIKLUS, HARI & PENCARIAN -->
        <div class="p-4 bg-white border-b border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Filter Kelas (Sesuai Jurusan Terpilih) -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Filter Kelas:</label>
                <select id="filterClass" onchange="applyFilters()" class="w-full text-xs p-2 border rounded-lg border-slate-200 bg-white text-slate-800 font-semibold focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="all">Semua Kelas</option>
                    @foreach($majors as $mCode => $m)
                        <optgroup label="Jurusan {{ $mCode }} ({{ $m['name'] }})" data-major-group="{{ $mCode }}">
                            @foreach($m['classes'] as $cls)
                                <option value="{{ $cls['id'] }}" data-major="{{ $mCode }}" {{ $selectedClassId == $cls['id'] ? 'selected' : '' }}>
                                    {{ $cls['name'] }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <!-- Filter Siklus/Putaran -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Putaran:</label>
                <select id="filterCycle" onchange="applyFilters()" class="w-full text-xs p-2 border rounded-lg border-slate-200 bg-white text-slate-800 font-semibold focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="all" {{ $selectedCycle === 'all' ? 'selected' : '' }}>Semua Putaran</option>
                    <option value="putaran_1" {{ $selectedCycle === 'putaran_1' ? 'selected' : '' }}>Putaran 1 (Produktif)</option>
                    <option value="putaran_2" {{ $selectedCycle === 'putaran_2' ? 'selected' : '' }}>Putaran 2 (Normada)</option>
                </select>
            </div>

            <!-- Filter Hari -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Hari:</label>
                <select id="filterDay" onchange="applyFilters()" class="w-full text-xs p-2 border rounded-lg border-slate-200 bg-white text-slate-800 font-semibold focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="all" {{ $selectedDay === 'all' ? 'selected' : '' }}>Semua Hari</option>
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $h)
                        <option value="{{ $h }}" {{ $selectedDay === $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Pencarian Cepat (Live Search) -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cari Mapel / Guru / Ruang:</label>
                <div class="relative">
                    <input type="text" id="filterSearch" oninput="applyFilters()" placeholder="Ketik nama mapel/guru..." value="{{ $search }}" class="w-full text-xs p-2 pl-8 border rounded-lg border-slate-200 bg-white text-slate-800 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Banner Info Filter Aktif -->
        <div id="filterInfoBanner" class="px-6 py-2.5 bg-blue-50/70 border-b border-blue-100 flex items-center justify-between text-xs text-blue-900">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <span id="filterInfoText">Menampilkan seluruh jadwal semua jurusan</span>
            </div>
            <button type="button" onclick="resetAllFilters()" class="text-blue-700 hover:text-blue-900 font-bold hover:underline">
                Reset Filter
            </button>
        </div>

        <!-- Tabel Jadwal -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm" id="schedulesTable">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 px-4">Siklus</th>
                        <th class="py-3 px-4">Hari & Jam</th>
                        <th class="py-3 px-4">Mata Pelajaran</th>
                        <th class="py-3 px-4">Guru Pengampu</th>
                        <th class="py-3 px-4">Jurusan, Kelas & Ruang</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="schedulesTableBody">
                    @forelse($schedules as $s)
                        @php
                            $majorCode = $s->schoolClass->major ?? 'UMUM';
                            $majorColor = $s->schoolClass->major_badge_color ?? 'bg-slate-100 text-slate-700 border-slate-200';
                        @endphp
                        <tr class="schedule-row hover:bg-blue-50/40 transition"
                            data-major="{{ $majorCode }}"
                            data-class-id="{{ $s->class_id }}"
                            data-cycle="{{ $s->cycle }}"
                            data-day="{{ $s->day }}"
                            data-search="{{ strtolower($s->subject . ' ' . ($s->teacher->name ?? '') . ' ' . ($s->room->name ?? '') . ' ' . ($s->schoolClass->name ?? '') . ' ' . $majorCode) }}">
                            
                            <!-- Siklus -->
                            <td class="py-3 px-4 font-semibold">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $s->cycle == 'putaran_1' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-800 border border-slate-200' }}">
                                    {{ $s->cycle == 'putaran_1' ? 'Putaran 1' : 'Putaran 2' }}
                                </span>
                            </td>

                            <!-- Hari & Jam -->
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 block">{{ $s->day }}</span>
                                <span class="text-xs text-slate-500 font-mono">{{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}</span>
                            </td>

                            <!-- Mata Pelajaran -->
                            <td class="py-3 px-4 font-semibold text-slate-900">
                                <span>{{ $s->subject }}</span>
                            </td>

                            <!-- Guru -->
                            <td class="py-3 px-4 text-slate-700">
                                <span class="font-medium text-slate-900 block">{{ $s->teacher->name ?? '-' }}</span>
                                <span class="text-xs text-slate-500">NIP/ID: {{ $s->teacher->identifier ?? '-' }}</span>
                            </td>

                            <!-- Jurusan, Kelas & Ruang -->
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-1.5 mb-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $majorColor }}">
                                        {{ $majorCode }}
                                    </span>
                                    <span class="font-bold text-slate-900 text-sm">{{ $s->schoolClass->name ?? '-' }}</span>
                                </div>
                                <span class="text-xs text-slate-500 inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                    {{ $s->room->name ?? '-' }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 text-center">
                                @if(!$setting->is_schedule_locked)
                                    <form action="{{ route('waka.schedules.destroy', $s->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" 
                                                onclick="confirmDelete(this.form, 'Yakin ingin menghapus jadwal ini?')" 
                                                class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" 
                                                title="Hapus Jadwal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400 italic">Terkunci</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyTableRow">
                            <td colspan="6" class="p-8 text-center text-slate-500">Belum ada data jadwal pelajaran yang diinputkan.</td>
                        </tr>
                    @endforelse

                    <!-- Pesan saat filter menghasilkan 0 baris -->
                    <tr id="noResultsFilterRow" class="hidden">
                        <td colspan="6" class="py-12 text-center text-slate-500">
                            <div class="max-w-sm mx-auto space-y-2">
                                <svg class="w-10 h-10 mx-auto text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <p class="font-semibold text-slate-800 text-sm">Tidak ada jadwal yang sesuai filter!</p>
                                <p class="text-xs text-slate-500">Coba pilih jurusan lain, kelas lain, atau reset filter Anda.</p>
                                <button type="button" onclick="resetAllFilters()" class="mt-2 px-3.5 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg text-xs font-bold transition">
                                    Tampilkan Semua Jadwal
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Import Jadwal -->
<div id="importScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs hidden p-4 sm:p-6">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full border border-slate-200 overflow-hidden flex flex-col max-h-[92vh]">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center space-x-3">
                <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl border border-blue-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h3 class="font-heading font-bold text-slate-900 text-base">Import Jadwal Pelajaran (Multi-File)</h3>
                    <p class="text-xs text-slate-500">Auto-Detect Jadwal Guru, Jadwal Kelas, & Jadwal Ruangan</p>
                </div>
            </div>
            <button type="button" onclick="closeImportScheduleModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form action="{{ route('waka.schedules.import') }}" method="POST" enctype="multipart/form-data" class="flex flex-col flex-1 overflow-hidden min-h-0">
            @csrf

            <!-- Form Content (Scrollable) -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                <!-- Format Badges & Info Auto-Detect -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="text-xs font-bold text-slate-800 mb-1.5 flex items-center justify-between">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>Sistem Cross-Matching Jadwal (Guru & Ruangan):</span>
                        </span>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-bold rounded-full">FET Ready</span>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded text-[11px] font-semibold">PDF Document (.pdf)</span>
                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[11px] font-semibold">HTML Table (.html)</span>
                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[11px] font-semibold">Excel (.xlsx, .xls)</span>
                        <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[11px] font-semibold">CSV / TSV / Text</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        Sistem otomatis mendeteksi jadwal guru/kelas dan mencocokkan ruangan dari file ruangan secara cerdas tanpa perlu mengubah susunan file FET.
                    </p>
                </div>

                <!-- Pilihan Putaran -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Target Putaran / Siklus *</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center gap-2 p-2 border rounded-xl cursor-pointer transition hover:bg-slate-50 border-slate-200">
                            <input type="radio" name="cycle" value="putaran_1" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-semibold text-slate-800">Putaran 1</span>
                                <span class="block text-[10px] text-slate-400">Produktif</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2 border rounded-xl cursor-pointer transition bg-blue-50/70 border-blue-500 text-blue-900 shadow-xs">
                            <input type="radio" name="cycle" value="putaran_2" checked class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-bold text-blue-900">Putaran 2</span>
                                <span class="block text-[10px] text-blue-600 font-medium">Normatif & Adaptif</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2 border rounded-xl cursor-pointer transition hover:bg-slate-50 border-slate-200">
                            <input type="radio" name="cycle" value="auto" class="text-blue-600 focus:ring-blue-500">
                            <div>
                                <span class="block text-xs font-semibold text-slate-800">Otomatis</span>
                                <span class="block text-[10px] text-slate-400">Ikuti Siklus Aktif</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Input File 1: Jadwal Guru atau Jadwal Kelas (Wajib) -->
                <div class="p-3 border border-blue-200 bg-blue-50/30 rounded-xl space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-blue-950 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span>File 1: Jadwal Guru / Jadwal Kelas * (Utama)</span>
                        </label>
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-bold rounded">Wajib</span>
                    </div>
                    <input type="file" name="file" accept=".pdf, .xlsx, .xls, .csv, .txt, .tsv, .json, .html" required class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 border border-slate-200 rounded-lg p-1.5 bg-white cursor-pointer">
                    <p class="text-[11px] text-slate-500">Unggah file jadwal guru atau jadwal per kelas FET (PDF, HTML, Excel, atau CSV).</p>
                </div>

                <!-- Input File 2: Jadwal Ruangan (Opsional tapi Sangat Dianjurkan) -->
                <div class="p-3 border border-emerald-200 bg-emerald-50/30 rounded-xl space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-emerald-950 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            <span>File 2: Jadwal Ruangan (Pencocokan Otomatis)</span>
                        </label>
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-bold rounded">Pendukung</span>
                    </div>
                    <input type="file" name="room_file" accept=".pdf, .xlsx, .xls, .csv, .txt, .tsv, .json, .html" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 border border-slate-200 rounded-lg p-1.5 bg-white cursor-pointer">
                    <p class="text-[11px] text-slate-500">Opsional: Jika mapel teori di jadwal guru tidak ada nama ruangnya, sistem akan otomatis mengambil nama ruangan dari file ini berdasarkan jam dan kelas.</p>
                </div>

                <!-- Download Template Fallback -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-bold text-slate-800">Template Kolom Standar:</div>
                        <div class="text-[11px] text-slate-500">Jika ingin mengimpor format tabel Excel/CSV biasa.</div>
                    </div>
                    <a href="{{ route('waka.schedules.template') }}" class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg transition inline-flex items-center space-x-1.5 shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Unduh CSV</span>
                    </a>
                </div>

                <!-- Ketentuan Anti-Tabrakan -->
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 space-y-1">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Validasi Otomatis SCP:</span>
                    </div>
                    <ul class="list-disc pl-4 space-y-0.5 text-[11px] text-slate-600">
                        <li>Sistem otomatis mendeteksi bentrok jam, guru, kelas, dan lab/ruang.</li>
                        <li>Jika ada mapel yang tidak memiliki ruangan di kedua file, otomatis diarahkan ke <code>Ruang [Nama Kelas]</code>.</li>
                        <li>Sesi mengajar multi-jam (blok praktik/teori) otomatis digabungkan menjadi 1 jadwal utuh.</li>
                    </ul>
                </div>
            </div>

            <!-- Footer Tombol (Fixed Pinned di Bawah Modal) -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end space-x-3 flex-shrink-0">
                <button type="button" onclick="closeImportScheduleModal()" class="px-4 py-2.5 border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100 transition">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md hover:shadow-lg transition inline-flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Mulai Proses & Import Jadwal</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentMajor = '{{ $selectedMajor }}';

    function filterMajor(major) {
        currentMajor = major;
        
        // 1. Update style tabs
        document.querySelectorAll('.major-tab-btn').forEach(btn => {
            btn.className = 'major-tab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap bg-white text-slate-600 hover:bg-slate-100 border border-slate-200';
            const badge = btn.querySelector('span:last-child');
            if (badge) badge.className = 'px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-700';
        });

        const activeBtn = document.getElementById('tab-major-' + major);
        if (activeBtn) {
            activeBtn.className = 'major-tab-btn inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap bg-blue-600 text-white shadow-xs';
            const activeBadge = activeBtn.querySelector('span:last-child');
            if (activeBadge) activeBadge.className = 'px-1.5 py-0.5 rounded-full text-[10px] bg-white/20 text-white';
        }

        // 2. Sesuaikan opsi di dropdown kelas
        const classSelect = document.getElementById('filterClass');
        const optgroups = classSelect.querySelectorAll('optgroup');
        optgroups.forEach(og => {
            const ogMajor = og.getAttribute('data-major-group');
            if (major === 'all' || ogMajor === major) {
                og.style.display = '';
            } else {
                og.style.display = 'none';
            }
        });

        // Jika kelas yang dipilih saat ini tidak termasuk jurusan baru, kembalikan ke 'all'
        const selectedOption = classSelect.options[classSelect.selectedIndex];
        if (selectedOption && selectedOption.value !== 'all') {
            const optMajor = selectedOption.getAttribute('data-major');
            if (major !== 'all' && optMajor !== major) {
                classSelect.value = 'all';
            }
        }

        applyFilters();
    }

    function applyFilters() {
        const classSelect = document.getElementById('filterClass');
        const classId = classSelect ? classSelect.value : 'all';
        const cycle = document.getElementById('filterCycle') ? document.getElementById('filterCycle').value : 'all';
        const day = document.getElementById('filterDay') ? document.getElementById('filterDay').value : 'all';
        const searchInput = document.getElementById('filterSearch');
        const search = searchInput ? searchInput.value.toLowerCase().trim() : '';

        const rows = document.querySelectorAll('.schedule-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const rMajor = row.getAttribute('data-major');
            const rClassId = row.getAttribute('data-class-id');
            const rCycle = row.getAttribute('data-cycle');
            const rDay = row.getAttribute('data-day');
            const rSearch = row.getAttribute('data-search') || '';

            const matchMajor = (currentMajor === 'all' || rMajor === currentMajor);
            const matchClass = (classId === 'all' || rClassId === classId);
            const matchCycle = (cycle === 'all' || rCycle === cycle);
            const matchDay   = (day === 'all' || rDay === day);
            const matchSearch = (!search || rSearch.includes(search));

            if (matchMajor && matchClass && matchCycle && matchDay && matchSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update badge total
        const badge = document.getElementById('badgeTotalJadwal');
        if (badge) badge.innerText = visibleCount + ' Jadwal';

        // Update text banner
        const infoText = document.getElementById('filterInfoText');
        if (infoText) {
            let label = currentMajor === 'all' ? 'Semua Jurusan' : 'Jurusan ' + currentMajor;
            if (classId !== 'all') {
                const className = classSelect.selectedOptions[0]?.text?.trim() || '';
                label += ' • ' + className;
            }
            if (cycle !== 'all') {
                label += ' • ' + (cycle === 'putaran_1' ? 'Putaran 1' : 'Putaran 2');
            }
            if (day !== 'all') {
                label += ' • ' + day;
            }
            if (search) {
                label += ' • Kata kunci: "' + search + '"';
            }
            infoText.innerText = 'Menampilkan: ' + label + ' (' + visibleCount + ' jadwal)';
        }

        // Tampilkan placeholder jika kosong
        const noResult = document.getElementById('noResultsFilterRow');
        if (noResult) {
            if (visibleCount === 0 && rows.length > 0) {
                noResult.classList.remove('hidden');
            } else {
                noResult.classList.add('hidden');
            }
        }
    }

    function resetAllFilters() {
        if (document.getElementById('filterClass')) document.getElementById('filterClass').value = 'all';
        if (document.getElementById('filterCycle')) document.getElementById('filterCycle').value = 'all';
        if (document.getElementById('filterDay')) document.getElementById('filterDay').value = 'all';
        if (document.getElementById('filterSearch')) document.getElementById('filterSearch').value = '';
        filterMajor('all');
    }

    // Modal Import Jadwal
    function openImportScheduleModal() {
        document.getElementById('importScheduleModal').classList.remove('hidden');
    }
    function closeImportScheduleModal() {
        document.getElementById('importScheduleModal').classList.add('hidden');
    }
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('importScheduleModal');
        if (e.target === modal) closeImportScheduleModal();
    });

    // Inisialisasi saat pertama kali load
    document.addEventListener('DOMContentLoaded', function() {
        if (currentMajor && currentMajor !== 'all') {
            filterMajor(currentMajor);
        } else {
            applyFilters();
        }
    });
</script>
@endsection
