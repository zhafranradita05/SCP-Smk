@extends('layouts.waka')

@section('title', 'Tambah Pengguna Baru')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white p-7 rounded-2xl shadow-xs border border-slate-200/90">
        
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <div>
                <h2 class="text-lg font-heading font-bold text-slate-900">Form Pengguna Baru</h2>
                <p class="text-xs text-slate-500">Lengkapi data untuk menambahkan pengguna baru ke sistem</p>
            </div>
            <a href="{{ route('waka.users.index') }}" class="text-xs font-semibold text-slate-600 hover:text-blue-600 flex items-center space-x-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Kembali</span>
            </a>
        </div>

        <form action="{{ route('waka.users.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400" placeholder="Contoh: Budi Santoso, S.Kom" required>
                @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username *</label>
                    <input type="text" name="username" value="{{ old('username') }}" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400" placeholder="Contoh: budi_santoso" required>
                    @error('username') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400" placeholder="budi@sekolah.com" required>
                    @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Role Pengguna *</label>
                    <select id="role-select" name="role" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="guru" {{ in_array(old('role', request('role')), ['guru', 'wali_kelas']) ? 'selected' : '' }}>Guru Pegawai</option>
                        <option value="guru_bk" {{ old('role', request('role')) == 'guru_bk' ? 'selected' : '' }}>Guru BK</option>
                        <option value="siswa" {{ old('role', request('role')) == 'siswa' ? 'selected' : '' }}>Siswa</option>
                        <option value="waka_kurikulum" {{ old('role', request('role')) == 'waka_kurikulum' ? 'selected' : '' }}>Waka Kurikulum</option>
                    </select>
                    @error('role') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password Awal *</label>
                    <input type="password" name="password" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400" placeholder="Minimal 6 karakter" required>
                    @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Input Kelas (Khusus Siswa) -->
            <div id="class-container" class="transition-all duration-300">
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Kelas Siswa <span class="text-xs font-normal text-slate-500 lowercase">(wajib dipilih jika role Siswa)</span>
                </label>
                <select name="class_id" id="class-select" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">-- Pilih Kelas Siswa --</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ old('class_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
                @error('class_id') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">NIP / NISN (Identifier)</label>
                    <input type="text" name="identifier" value="{{ old('identifier') }}" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400" placeholder="Nomor Induk">
                    @error('identifier') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">No. WhatsApp / Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 placeholder:text-slate-400" placeholder="08xxxxxxxxxx">
                    @error('phone') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <a href="{{ route('waka.users.index') }}" class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-xs transition">
                    Simpan Pengguna
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role-select');
        const classContainer = document.getElementById('class-container');

        function updateClassVisibility() {
            if (roleSelect && classContainer) {
                if (roleSelect.value === 'siswa') {
                    classContainer.style.display = 'block';
                } else {
                    classContainer.style.display = 'none';
                }
            }
        }

        if (roleSelect) {
            roleSelect.addEventListener('change', updateClassVisibility);
            updateClassVisibility();
        }
    });
</script>
@endsection
