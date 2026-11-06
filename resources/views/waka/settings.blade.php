@extends('layouts.waka')

@section('title', 'Pengaturan Siklus Blok & Kunci Jadwal')

@section('content')
    <div class="max-w-2xl bg-white rounded-2xl shadow-sm border border-[#E8E0D5] p-8">
        
        @if(session('success'))
            <div class="bg-[#EBF3ED] text-[#2D4534] p-4 rounded-xl mb-6 border border-[#D5E5D9] flex items-center space-x-2">
                <svg class="w-5 h-5 text-[#4E6E58]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ url('/waka/settings') }}" method="POST">
            @csrf
            
            <!-- Pengaturan Putaran -->
            <div class="mb-6">
                <label class="block text-[#2C1D11] font-bold mb-1">Siklus Putaran Aktif</label>
                <p class="text-sm text-[#795548] mb-4">Pilih putaran mana yang sedang berjalan minggu ini.</p>
                
                <div class="flex gap-4">
                    <label class="flex items-center gap-3 cursor-pointer border p-4 rounded-xl transition duration-150 flex-1 {{ $setting->active_cycle == 'putaran_1' ? 'border-[#B57338] bg-[#FAF7F2] text-[#2C1D11] font-bold shadow-sm' : 'border-[#E8E0D5] hover:bg-[#FAF7F2]' }}">
                        <input type="radio" name="active_cycle" value="putaran_1" {{ $setting->active_cycle == 'putaran_1' ? 'checked' : '' }} class="w-5 h-5 text-[#5D4037] focus:ring-[#5D4037]">
                        <span class="font-medium text-[#2C1D11]">Putaran 1</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer border p-4 rounded-xl transition duration-150 flex-1 {{ $setting->active_cycle == 'putaran_2' ? 'border-[#B57338] bg-[#FAF7F2] text-[#2C1D11] font-bold shadow-sm' : 'border-[#E8E0D5] hover:bg-[#FAF7F2]' }}">
                        <input type="radio" name="active_cycle" value="putaran_2" {{ $setting->active_cycle == 'putaran_2' ? 'checked' : '' }} class="w-5 h-5 text-[#5D4037] focus:ring-[#5D4037]">
                        <span class="font-medium text-[#2C1D11]">Putaran 2</span>
                    </label>
                </div>
            </div>

            <hr class="my-6 border-[#E8E0D5]">

            <!-- Lock Schedule -->
            <div class="mb-8">
                <label class="flex items-start gap-3 cursor-pointer p-4 bg-[#FBF9F5] border border-[#E8E0D5] rounded-xl">
                    <div class="flex items-center h-6">
                        <input type="checkbox" name="is_schedule_locked" value="1" {{ $setting->is_schedule_locked ? 'checked' : '' }} class="w-5 h-5 text-[#A93B2C] border-[#E8E0D5] rounded focus:ring-[#A93B2C]">
                    </div>
                    <div>
                        <span class="block font-bold text-[#2C1D11]">Kunci Jadwal (Lock Schedule)</span>
                        <span class="block text-sm text-[#795548] mt-1">Jika diaktifkan, maka jadwal tidak dapat ditambah atau dihapus. Guru tetap dapat mengisi jurnal harian untuk jadwal yang aktif.</span>
                    </div>
                </label>
            </div>

            <button type="submit" class="bg-[#5D4037] hover:bg-[#4E342E] text-white font-bold py-3 px-6 rounded-xl transition duration-200 shadow-sm">
                Simpan Pengaturan
            </button>
        </form>
    </div>
@endsection
