<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SchoolClass;
use App\Models\Schedule;
use App\Models\User;

return new class extends Migration
{
    /**
     * Deduplikasi dan gabungkan seluruh data kelas yang terduplikasi
     * (contoh: "XI RPL 1" vs "XI RPL1", "XII RPL 2" vs "XII RPL2")
     * agar hanya 48 kelas resmi SMKN 1 Bantul yang tersimpan.
     */
    public function up(): void
    {
        $allClasses = SchoolClass::all();

        $groups = [];
        $unmatched = [];

        foreach ($allClasses as $cls) {
            $canon = SchoolClass::canonicalName($cls->name);
            if ($canon) {
                $groups[$canon][] = $cls;
            } else {
                $unmatched[] = $cls;
            }
        }

        foreach ($groups as $canonicalName => $classRows) {
            // 1. Tentukan kelas primer / kanonikal
            $primary = null;

            // Prioritas 1: Record yang namanya sudah persis nama kanonikal
            foreach ($classRows as $c) {
                if ($c->name === $canonicalName) {
                    $primary = $c;
                    break;
                }
            }

            // Prioritas 2: Record yang memiliki wali kelas
            if (!$primary) {
                foreach ($classRows as $c) {
                    if (!empty($c->homeroom_teacher_id)) {
                        $primary = $c;
                        break;
                    }
                }
            }

            // Prioritas 3: Record yang memiliki siswa atau jadwal
            if (!$primary) {
                foreach ($classRows as $c) {
                    if (User::where('class_id', $c->id)->exists() || Schedule::where('class_id', $c->id)->exists()) {
                        $primary = $c;
                        break;
                    }
                }
            }

            // Fallback: Ambil record pertama
            if (!$primary) {
                $primary = $classRows[0];
            }

            // 2. Gabungkan data dari record duplikat ke record primer
            foreach ($classRows as $dup) {
                if ($dup->id === $primary->id) {
                    continue;
                }

                // Salin Wali Kelas jika primer belum punya
                if (empty($primary->homeroom_teacher_id) && !empty($dup->homeroom_teacher_id)) {
                    $primary->homeroom_teacher_id = $dup->homeroom_teacher_id;
                    $primary->save();
                }

                // Salin Guru BK jika primer belum punya
                if (empty($primary->bk_teacher_id) && !empty($dup->bk_teacher_id)) {
                    $primary->bk_teacher_id = $dup->bk_teacher_id;
                    $primary->save();
                }

                // Pindahkan seluruh siswa (users) ke kelas primer
                User::where('class_id', $dup->id)->update(['class_id' => $primary->id]);

                // Pindahkan atau bersihkan duplikat jadwal pelajaran (schedules)
                $dupSchedules = Schedule::where('class_id', $dup->id)->get();
                foreach ($dupSchedules as $ds) {
                    $conflict = Schedule::where('class_id', $primary->id)
                        ->where('cycle', $ds->cycle)
                        ->where('day', $ds->day)
                        ->where('start_time', $ds->start_time)
                        ->first();

                    if ($conflict) {
                        // Slot jadwal sudah ada pada kelas primer, hapus duplikatnya
                        $ds->delete();
                    } else {
                        // Re-point jadwal ke kelas primer
                        $ds->update(['class_id' => $primary->id]);
                    }
                }

                // Hapus baris kelas duplikat yang sudah dimerge
                $dup->delete();
            }

            // 3. Pastikan nama kelas primer tepat sesuai nama resmi dengan spasi
            if ($primary->name !== $canonicalName) {
                $primary->name = $canonicalName;
                $primary->save();
            }
        }

        // 4. Bersihkan kelas yang tidak dikenal dan tidak memiliki relasi
        foreach ($unmatched as $u) {
            $hasUsers = User::where('class_id', $u->id)->exists();
            $hasSchedules = Schedule::where('class_id', $u->id)->exists();
            if (!$hasUsers && !$hasSchedules) {
                $u->delete();
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
