@extends('layouts.waka')

@section('title', 'Edit Profil Waka Kurikulum')

@section('header_actions')
    <a href="{{ url('/waka/dashboard') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-semibold rounded-lg shadow-xs transition">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        <span>Dashboard</span>
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Notifikasi Sukses / Gagal -->
    @if(session('success'))
        <div class="p-4 bg-blue-50 border border-blue-200 text-blue-900 rounded-2xl shadow-xs flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-blue-800">Berhasil Disimpan</h4>
                    <p class="text-sm font-medium text-blue-900">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl shadow-xs">
            <div class="flex items-center space-x-3 mb-2">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <h4 class="text-xs font-bold uppercase tracking-wider text-rose-800">Terdapat Kesalahan Pengisian</h4>
            </div>
            <ul class="list-disc list-inside text-xs text-rose-700 space-y-1 ml-2">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Kolom Kiri: Kartu Ringkasan Akun Waka -->
        <div class="space-y-4">
            <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/90 text-center">
                <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white font-heading font-extrabold text-2xl flex items-center justify-center shadow-md mb-4 ring-4 ring-blue-50">
                    {{ strtoupper(substr($user->name ?? 'W', 0, 1)) }}
                </div>
                
                <h3 class="font-heading font-bold text-slate-900 text-base leading-tight mb-1">
                    {{ $user->name }}
                </h3>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200 mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-600"></span>
                    <span>Waka Kurikulum</span>
                </div>

                <div class="border-t border-slate-100 pt-3 text-left space-y-2 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Username</span>
                        <span class="text-slate-700 font-mono font-medium">{{ $user->username }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Email</span>
                        <span class="text-slate-700 font-medium truncate block">{{ $user->email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">NIP / Identifier</span>
                        <span class="text-slate-700 font-mono font-medium">{{ $user->identifier ?: '-' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">No. WhatsApp</span>
                        <span class="text-slate-700 font-mono font-medium">{{ $user->phone ?: '-' }}</span>
                    </div>
                </div>
            </div>

            <!-- Kartu Info -->
            <div class="bg-blue-50/60 p-4 rounded-2xl border border-blue-100/90 text-xs text-blue-900 space-y-2">
                <div class="flex items-center space-x-2 text-blue-800 font-bold">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Informasi Akun Waka</span>
                </div>
                <p class="text-slate-600 text-[11px] leading-relaxed">
                    Data profil ini adalah identitas resmi Waka Kurikulum di SCP. Jika Anda mengganti nama atau username, nama pada tanda tangan laporan dan panel atas akan otomatis diperbarui.
                </p>
            </div>
        </div>

        <!-- Kolom Kanan: Form Edit Profil Waka -->
        <div class="lg:col-span-2">
            <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/90">
                <div class="border-b border-slate-100 pb-4 mb-5">
                    <h2 class="text-base font-heading font-bold text-slate-900">Perbarui Data Waka Kurikulum</h2>
                    <p class="text-xs text-slate-500">Sesuaikan dengan data pribadi dan gelar Waka Kurikulum yang resmi</p>
                </div>

                <form action="{{ route('waka.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nama Lengkap & Gelar *
                        </label>
                        <input type="text" 
                               name="name" 
                               value="{{ old('name', $user->name) }}" 
                               placeholder="Contoh: Drs. H. Joko Santoso, M.Pd." 
                               class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                               required>
                        <p class="text-[11px] text-slate-400 mt-1">Nama ini akan ditampilkan pada kop laporan rekap jurnal & dasbor.</p>
                        @error('name') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <!-- NIP & WhatsApp -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                NIP / NUPTK (Opsional)
                            </label>
                            <input type="text" 
                                   name="identifier" 
                                   value="{{ old('identifier', $user->identifier) }}" 
                                   placeholder="Contoh: 19780512 200212 1 003" 
                                   class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono">
                            @error('identifier') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                No. WhatsApp / HP
                            </label>
                            <input type="text" 
                                   name="phone" 
                                   value="{{ old('phone', $user->phone) }}" 
                                   placeholder="Contoh: 081234567890" 
                                   class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono">
                            @error('phone') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Username & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Username Login *
                            </label>
                            <input type="text" 
                                   name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   placeholder="Username untuk login" 
                                   class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 font-mono" 
                                   required>
                            @error('username') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Alamat Email *
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}" 
                                   placeholder="waka@sekolah.sch.id" 
                                   class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" 
                                   required>
                            @error('email') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Divider Password -->
                    <div class="pt-4 border-t border-slate-100">
                        <div class="flex items-center space-x-2 mb-3">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Ganti Password (Opsional)</h4>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">Biarkan kedua kolom password di bawah ini kosong jika Anda tidak ingin mengubah password.</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Password Baru
                                </label>
                                <input type="password" 
                                       name="password" 
                                       placeholder="Minimal 6 karakter" 
                                       class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                @error('password') <span class="text-xs text-rose-600">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">
                                    Konfirmasi Password Baru
                                </label>
                                <input type="password" 
                                       name="password_confirmation" 
                                       placeholder="Ulangi password baru" 
                                       class="w-full border-slate-200 rounded-lg p-2.5 border text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                        <a href="{{ url('/waka/dashboard') }}" class="px-4 py-2 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center space-x-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>

</div>
@endsection

