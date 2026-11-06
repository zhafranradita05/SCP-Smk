@extends('layouts.waka')

@section('title', 'Kelola Jadwal & Siklus Blok')

@section('content')
<div class="space-y-8">

    <!-- Notifikasi -->
    @if(session('success'))
        <div class="p-4 bg-[#EBF3ED] border-l-4 border-[#4E6E58] text-[#2D4534] rounded-xl shadow-sm flex items-center space-x-2">
            <svg class="w-5 h-5 text-[#4E6E58]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-[#FDF0ED] border-l-4 border-[#A93B2C] text-[#7A2418] rounded-xl shadow-sm font-semibold flex items-center space-x-2">
            <svg class="w-5 h-5 text-[#A93B2C]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- 1. PANEL PENGATURAN PUTARAN BLOK & LOCK SCHEDULE -->
    <div class="bg-white rounded-2xl shadow-sm border border-[#E8E0D5] p-6">
        <h3 class="text-lg font-bold text-[#2C1D11] mb-1 flex items-center gap-2">
            <svg class="w-5 h-5 text-[#B57338]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            Pengaturan Siklus Putaran & Kunci Jadwal
        </h3>
        <p class="text-sm text-[#795548] mb-6">Tentukan siklus blok yang sedang aktif serta kunci jadwal agar tidak bisa diubah.</p>

        <form action="{{ route('waka.settings.update') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center mb-5">
                <!-- Tahun Ajaran Sistem -->
                <div>
                    <label class="block text-xs font-bold text-[#5D4037] uppercase tracking-wider mb-1.5">Tahun Ajaran Aktif:</label>
                    <select name="academic_year" class="w-full text-xs p-2.5 border rounded-lg border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037] font-semibold">
                        <option value="2024/2025" {{ ($setting->academic_year ?? '2024/2025') == '2024/2025' ? 'selected' : '' }}>2024/2025</option>
                        <option value="2025/2026" {{ ($setting->academic_year ?? '') == '2025/2026' ? 'selected' : '' }}>2025/2026</option>
                        <option value="2026/2027" {{ ($setting->academic_year ?? '') == '2026/2027' ? 'selected' : '' }}>2026/2027</option>
                        <option value="2027/2028" {{ ($setting->academic_year ?? '') == '2027/2028' ? 'selected' : '' }}>2027/2028</option>
                    </select>
                </div>

                <!-- Semester Sistem -->
                <div>
                    <label class="block text-xs font-bold text-[#5D4037] uppercase tracking-wider mb-1.5">Semester Aktif:</label>
                    <select name="active_semester" class="w-full text-xs p-2.5 border rounded-lg border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037] font-semibold">
                        <option value="ganjil" {{ ($setting->active_semester ?? 'ganjil') == 'ganjil' ? 'selected' : '' }}>Semester Ganjil (Juli - Des)</option>
                        <option value="genap" {{ ($setting->active_semester ?? '') == 'genap' ? 'selected' : '' }}>Semester Genap (Jan - Jun)</option>
                    </select>
                </div>

                <!-- Pilihan Putaran -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-[#5D4037] uppercase tracking-wider mb-1.5">Putaran Aktif Minggu Ini:</label>
                    <div class="flex gap-3">
                        <label class="flex items-center gap-2 p-2.5 border rounded-lg cursor-pointer flex-1 transition {{ $setting->active_cycle == 'putaran_1' ? 'border-[#B57338] bg-[#FAF7F2] text-[#2C1D11] font-bold shadow-sm' : 'border-[#E8E0D5] hover:bg-[#FAF7F2]' }}">
                            <input type="radio" name="active_cycle" value="putaran_1" {{ $setting->active_cycle == 'putaran_1' ? 'checked' : '' }} class="w-4 h-4 text-[#5D4037] focus:ring-[#5D4037]">
                            <span class="text-xs">Putaran 1</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 border rounded-lg cursor-pointer flex-1 transition {{ $setting->active_cycle == 'putaran_2' ? 'border-[#B57338] bg-[#FAF7F2] text-[#2C1D11] font-bold shadow-sm' : 'border-[#E8E0D5] hover:bg-[#FAF7F2]' }}">
                            <input type="radio" name="active_cycle" value="putaran_2" {{ $setting->active_cycle == 'putaran_2' ? 'checked' : '' }} class="w-4 h-4 text-[#5D4037] focus:ring-[#5D4037]">
                            <span class="text-xs">Putaran 2</span>
                        </label>
                    </div>
                </div>
            </div>

                <!-- Lock Schedule Checkbox -->
                <div class="p-4 bg-[#FBF9F5] border border-[#E8E0D5] rounded-xl">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="is_schedule_locked" value="1" {{ $setting->is_schedule_locked ? 'checked' : '' }} class="w-5 h-5 mt-0.5 text-[#A93B2C] border-[#E8E0D5] rounded focus:ring-[#A93B2C]">
                        <div>
                            <span class="block font-bold text-[#2C1D11] text-sm">Kunci Jadwal (Lock Schedule)</span>
                            <span class="block text-xs text-[#795548] mt-0.5">Jika dicentang, penambahan dan penghapusan jadwal dinonaktifkan.</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-5 py-2.5 bg-[#B57338] hover:bg-[#9E602A] text-white text-sm font-bold rounded-xl shadow-sm transition">
                    Simpan Perubahan Siklus & Kunci
                </button>
            </div>
        </form>
    </div>

    <!-- 2. FORM INPUT JADWAL BARU -->
    <div class="bg-white rounded-2xl shadow-sm border border-[#E8E0D5] p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
            <div>
                <h3 class="text-lg font-bold text-[#2C1D11]">Tambah Jadwal Pelajaran</h3>
                <p class="text-sm text-[#795548]">Sistem dilengkapi validasi anti-tabrakan waktu, ruang, dan kelas.</p>
            </div>
            <div class="flex items-center gap-2.5">
                @if(!$setting->is_schedule_locked)
                    <button type="button" onclick="openImportScheduleModal()" class="inline-flex items-center px-4 py-2.5 bg-[#EBF3ED] hover:bg-[#D5E5D9] text-[#2D4534] border border-[#C2DAC8] text-xs font-bold rounded-xl shadow-sm transition space-x-2" title="Import jadwal dari file PDF, Excel, CSV, atau JSON">
                        <svg class="w-4 h-4 text-[#4E6E58]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Import Jadwal (Multi-File)</span>
                    </button>
                @else
                    <span class="px-3 py-1 bg-[#FDF0ED] text-[#A93B2C] text-xs font-bold rounded-full border border-[#F5D5D0]">
                        🔒 Kunci Aktif (Tidak Bisa Tambah)
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
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Guru *</label>
                        <select name="teacher_id" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                            <option value="">-- Pilih Guru --</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ old('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Kelas -->
                    <div>
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Kelas *</label>
                        <select name="class_id" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Ruangan -->
                    <div>
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Ruangan *</label>
                        <select name="room_id" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                            <option value="">-- Pilih Ruang --</option>
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ old('room_id') == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Mata Pelajaran -->
                    <div>
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Mata Pelajaran *</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Contoh: Pemrograman Web" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                    </div>

                    <!-- Putaran -->
                    <div>
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Putaran *</label>
                        <select name="cycle" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                            <option value="putaran_1" {{ old('cycle') == 'putaran_1' ? 'selected' : '' }}>Putaran 1</option>
                            <option value="putaran_2" {{ old('cycle') == 'putaran_2' ? 'selected' : '' }}>Putaran 2</option>
                        </select>
                    </div>

                    <!-- Hari -->
                    <div>
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Hari *</label>
                        <select name="day" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $hari)
                                <option value="{{ $hari }}" {{ old('day') == $hari ? 'selected' : '' }}>{{ $hari }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Jam Mulai -->
                    <div>
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Jam Mulai *</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                    </div>

                    <!-- Jam Selesai -->
                    <div>
                        <label class="block text-xs font-bold text-[#5D4037] mb-1 uppercase">Jam Selesai *</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" class="w-full text-sm p-2.5 border rounded-xl border-[#E8E0D5] bg-[#FDFCFB] text-[#2C1D11] focus:border-[#5D4037] focus:ring-1 focus:ring-[#5D4037]" required>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="submit" class="px-6 py-2.5 bg-[#5D4037] hover:bg-[#4E342E] text-white font-bold text-sm rounded-xl shadow-sm transition">
                        + Simpan Jadwal Pelajaran
                    </button>
                    <button type="button" onclick="openImportScheduleModal()" class="px-5 py-2.5 bg-[#EBF3ED] hover:bg-[#D5E5D9] text-[#2D4534] border border-[#C2DAC8] text-sm font-bold rounded-xl shadow-sm transition inline-flex items-center space-x-2" title="Import jadwal dari file PDF, Excel, CSV, atau JSON">
                        <svg class="w-4 h-4 text-[#4E6E58]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Import Jadwal (Multi-File)</span>
                    </button>
                </div>
            </fieldset>
        </form>
    </div>

    <!-- 3. TABEL DAFTAR JADWAL TERDAFTAR -->
    <div class="bg-white rounded-2xl shadow-sm border border-[#E8E0D5] overflow-hidden">
        <div class="p-5 border-b border-[#E8E0D5] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="font-bold text-[#2C1D11]">Daftar Seluruh Jadwal (Total: {{ $schedules->count() }})</h3>
            @if(!$setting->is_schedule_locked)
                <button type="button" onclick="openImportScheduleModal()" class="inline-flex items-center px-3.5 py-2 bg-[#EBF3ED] hover:bg-[#D5E5D9] text-[#2D4534] border border-[#C2DAC8] text-xs font-bold rounded-xl shadow-sm transition space-x-2" title="Import jadwal dari file PDF, Excel, CSV, atau JSON">
                    <svg class="w-4 h-4 text-[#4E6E58]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Import Jadwal (Multi-File)</span>
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-[#FAF7F2] text-[#5D4037] text-xs font-bold uppercase tracking-wider border-b border-[#E8E0D5]">
                        <th class="py-3 px-4">Siklus</th>
                        <th class="py-3 px-4">Hari & Jam</th>
                        <th class="py-3 px-4">Mata Pelajaran</th>
                        <th class="py-3 px-4">Guru</th>
                        <th class="py-3 px-4">Kelas & Ruang</th>
                        <th class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E8E0D5]">
                    @forelse($schedules as $s)
                        <tr class="hover:bg-[#FAF7F2] transition">
                            <td class="py-3 px-4 font-semibold">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $s->cycle == 'putaran_1' ? 'bg-[#FAF7F2] text-[#5D4037] border border-[#E8E0D5]' : 'bg-[#FDF4E7] text-[#B57338] border border-[#E8D0B5]' }}">
                                    {{ $s->cycle == 'putaran_1' ? 'Putaran 1' : 'Putaran 2' }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-[#2C1D11]">{{ $s->day }}</span>
                                <span class="block text-xs text-[#795548] font-mono">{{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}</span>
                            </td>
                            <td class="py-3 px-4 font-semibold text-[#2C1D11]">{{ $s->subject }}</td>
                            <td class="py-3 px-4 text-[#795548] font-medium">{{ $s->teacher->name ?? '-' }}</td>
                            <td class="py-3 px-4 text-[#795548]">
                                <span class="font-bold text-[#2C1D11]">{{ $s->schoolClass->name ?? '-' }}</span>
                                <span class="text-xs text-[#A1887F] block">{{ $s->room->name ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if(!$setting->is_schedule_locked)
                                    <form action="{{ route('waka.schedules.destroy', $s->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" 
                                                onclick="confirmDelete(this.form, 'Yakin ingin menghapus jadwal ini?')" 
                                                class="p-1.5 text-[#A93B2C] hover:bg-[#FDF0ED] rounded-lg transition" 
                                                title="Hapus Jadwal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-[#A1887F] italic">Terkunci</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-[#A1887F]">Belum ada data jadwal pelajaran yang diinputkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
<!-- Modal Import Jadwal -->
<div id="importScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-[#E8E0D5] overflow-hidden">
        <div class="px-6 py-4 bg-[#FAF7F2] border-b border-[#E8E0D5] flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2.5 bg-[#EBF3ED] text-[#4E6E58] rounded-xl border border-[#D5E5D9]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                    <h3 class="font-bold text-[#2C1D11] text-base">Import Jadwal Pelajaran (Multi-File)</h3>
                    <p class="text-xs text-[#795548]">Auto-Detect PDF, Excel (.xlsx/.xls), CSV, Text, JSON</p>
                </div>
            </div>
            <button type="button" onclick="closeImportScheduleModal()" class="text-[#A1887F] hover:text-[#5D4037] p-1.5 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <form action="{{ route('waka.schedules.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Format Badges & Info Auto-Detect -->
            <div class="p-3.5 bg-[#FAF7F2] border border-[#E8E0D5] rounded-xl">
                <div class="text-xs font-bold text-[#2C1D11] mb-1.5 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-[#4E6E58]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Format Didukung Otomatis (Auto-Detect):</span>
                </div>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    <span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded text-[11px] font-semibold">PDF Document (.pdf)</span>
                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[11px] font-semibold">Excel (.xlsx, .xls)</span>
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[11px] font-semibold">CSV / TSV / TXT</span>
                    <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[11px] font-semibold">JSON (.json)</span>
                </div>
                <p class="text-[11px] text-[#795548] leading-relaxed">
                    Sistem otomatis mendeteksi format file yang Anda upload tanpa perlu konversi manual terlebih dahulu.
                </p>
            </div>

            <!-- Download Template -->
            <div class="p-3.5 bg-[#FAF7F2] border border-[#E8E0D5] rounded-xl flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-bold text-[#2C1D11]">Template Resmi Jadwal:</div>
                    <div class="text-[11px] text-[#795548]">Unduh format kolom guru, kelas, ruang, & jam.</div>
                </div>
                <a href="{{ route('waka.schedules.template') }}" class="px-3.5 py-2 bg-[#5D4037] hover:bg-[#4E342E] text-white text-xs font-bold rounded-lg transition inline-flex items-center space-x-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Unduh Template</span>
                </a>
            </div>

            <!-- Input File -->
            <div>
                <label class="block text-xs font-bold text-[#2C1D11] uppercase tracking-wider mb-2">Pilih File Jadwal *</label>
                <input type="file" name="file" accept=".pdf, .xlsx, .xls, .csv, .txt, .tsv, .json, .html" required class="w-full text-xs text-[#795548] file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#EBF3ED] file:text-[#4E6E58] hover:file:bg-[#D5E5D9] border border-[#E8E0D5] rounded-xl p-2 bg-[#FDFCFB] cursor-pointer">
                <p class="text-[11px] text-[#A1887F] mt-1.5">Mendukung file PDF, Excel, CSV, TSV, TXT, JSON (Maksimal 10MB).</p>
            </div>

            <!-- Ketentuan Anti-Tabrakan -->
            <div class="p-3 bg-[#FDF4E7] border border-[#E8D0B5] rounded-xl text-xs text-[#8A501F] space-y-1">
                <div class="font-bold">Keamanan Validasi Anti-Tabrakan:</div>
                <ul class="list-disc pl-4 space-y-0.5 text-[11px]">
                    <li>Sistem otomatis mendeteksi bentrok jam, guru, kelas, dan lab/ruang.</li>
                    <li>Jadwal yang bentrok akan dilewati tanpa merusak jadwal lain.</li>
                    <li>Format jam didukung: <code>07:30</code> atau <code>07.30</code> (mulai & selesai).</li>
                </ul>
            </div>

            <!-- Footer Tombol -->
            <div class="flex items-center justify-end space-x-3 pt-3 border-t border-[#E8E0D5]">
                <button type="button" onclick="closeImportScheduleModal()" class="px-4 py-2.5 border border-[#E8E0D5] rounded-xl text-xs font-semibold text-[#795548] hover:bg-[#FAF7F2] transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#4E6E58] hover:bg-[#3D5745] text-white text-xs font-bold rounded-xl shadow-sm transition inline-flex items-center space-x-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Unggah & Deteksi Otomatis</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
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
</script>
@endsection
