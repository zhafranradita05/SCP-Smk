<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\User;

return new class extends Migration
{
    /**
     * Hapus seluruh kelas & jurusan fiktif / tidak terdata yang bukan 8 kejuruan resmi SMKN 1 Bantul.
     */
    public function up(): void
    {
        $validMajors = ['RPL', 'TKJ', 'DKV', 'AK', 'MP', 'BD', 'BR', 'LPS'];

        $classes = SchoolClass::all();

        foreach ($classes as $cls) {
            $name = trim($cls->name);
            $cleanUpper = strtoupper($name);

            // Cek apakah format nama kelas valid: harus diawali tingkat Romawi sah (X, XI, XII)
            $hasValidGrade = preg_match('/^(X|XI|XII)\s+/i', $cleanUpper);

            // Cek apakah memuat salah satu dari 8 kejuruan resmi
            $hasValidMajor = false;
            foreach ($validMajors as $vm) {
                if (preg_match('/\b' . $vm . '(?![A-Za-z])/i', $cleanUpper)) {
                    $hasValidMajor = true;
                    break;
                }
            }

            // Juga dukung alias kurikulum merdeka (PPLG, TJKT, AKL, MPLB, BDP)
            if (!$hasValidMajor && preg_match('/\b(PPLG|TJKT|AKL|MPLB|OTKP|BDP|MM)\b/i', $cleanUpper)) {
                $hasValidMajor = true;
            }

            // Jika bukan kelas resmi (contoh: "X I", "XI I", "I", "Daftar Isi", atau nama fiktif lainnya)
            if (!$hasValidGrade || !$hasValidMajor) {
                // Hapus jadwal terkait kelas tidak valid ini
                Schedule::where('class_id', $cls->id)->delete();

                // Lepaskan relasi siswa jika ada
                User::where('class_id', $cls->id)->update(['class_id' => null]);

                // Hapus kelas tidak terdata tersebut
                $cls->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible
    }
};

