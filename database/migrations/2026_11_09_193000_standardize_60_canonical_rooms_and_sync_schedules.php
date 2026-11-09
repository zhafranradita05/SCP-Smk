<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Room;
use App\Models\Schedule;

return new class extends Migration
{
    /**
     * Standardize canonical 60 rooms of SMK Negeri 1 Bantul:
     * - 34 Ruang Kelas Teori (Ruang 1 s/d Ruang 34)
     * - 22 Lab Komputer (Lab Komputer 1 s/d Lab Komputer 22)
     * - 1 Lab IPA
     * - 1 Lab Penataan Barang
     * - 1 Lapangan Olahraga (OR)
     * - 1 HALL (Ruang Seni/Menari)
     *
     * And synchronize all existing schedules referencing duplicate/obsolete room IDs.
     */
    public function up(): void
    {
        // 1. Siapkan / Pastikan 60 Ruangan Kanonikal Tersedia
        $canonicalRooms = [];

        // A. 22 Lab Komputer (Lab Komputer 1 s/d Lab Komputer 22)
        for ($i = 1; $i <= 22; $i++) {
            $code = "LAB-KOMPUTER-{$i}";
            $name = "Lab Komputer {$i}";
            $room = Room::where('code', $code)->orWhere('name', $name)->first();
            if (!$room) {
                $room = Room::create(['name' => $name, 'code' => $code]);
            } else {
                $room->update(['name' => $name, 'code' => $code]);
            }
            $canonicalRooms["lab_{$i}"] = $room;
        }

        // B. 34 Ruang Teori (Ruang 1 s/d Ruang 34)
        for ($i = 1; $i <= 34; $i++) {
            $code = "R-{$i}";
            $name = "Ruang {$i}";
            $room = Room::where('code', $code)->orWhere('name', $name)->first();
            if (!$room) {
                $room = Room::create(['name' => $name, 'code' => $code]);
            } else {
                $room->update(['name' => $name, 'code' => $code]);
            }
            $canonicalRooms["ruang_{$i}"] = $room;
        }

        // C. Lab IPA
        $labIpa = Room::where('name', 'Lab IPA')
            ->orWhere('code', 'LAB-IPA')
            ->orWhere('name', 'like', '%ilmu pengetahuan alam%')
            ->orWhere('name', 'LABIPA')
            ->first();
        if (!$labIpa) {
            $labIpa = Room::create(['name' => 'Lab IPA', 'code' => 'LAB-IPA']);
        } else {
            $labIpa->update(['name' => 'Lab IPA', 'code' => 'LAB-IPA']);
        }
        $canonicalRooms['lab_ipa'] = $labIpa;

        // D. Lab Penataan Barang
        $labPb = Room::where('name', 'Lab Penataan Barang')
            ->orWhere('code', 'LAB-PB')
            ->orWhere('name', 'like', '%penataan barang%')
            ->first();
        if (!$labPb) {
            $labPb = Room::create(['name' => 'Lab Penataan Barang', 'code' => 'LAB-PB']);
        } else {
            $labPb->update(['name' => 'Lab Penataan Barang', 'code' => 'LAB-PB']);
        }
        $canonicalRooms['lab_pb'] = $labPb;

        // E. Lapangan Olahraga (OR)
        $or = Room::where('name', 'Lapangan Olahraga (OR)')
            ->orWhere('code', 'LAP-OR')
            ->orWhere('name', 'like', '%olahraga%')
            ->first();
        if (!$or) {
            $or = Room::create(['name' => 'Lapangan Olahraga (OR)', 'code' => 'LAP-OR']);
        } else {
            $or->update(['name' => 'Lapangan Olahraga (OR)', 'code' => 'LAP-OR']);
        }
        $canonicalRooms['or'] = $or;

        // F. HALL (Ruang Seni/Menari)
        $hall = Room::where('name', 'HALL (Ruang Seni/Menari)')
            ->orWhere('code', 'HALL')
            ->orWhere('name', 'like', '%menari%')
            ->orWhere('name', 'like', '%hall%')
            ->first();
        if (!$hall) {
            $hall = Room::create(['name' => 'HALL (Ruang Seni/Menari)', 'code' => 'HALL']);
        } else {
            $hall->update(['name' => 'HALL (Ruang Seni/Menari)', 'code' => 'HALL']);
        }
        $canonicalRooms['hall'] = $hall;

        // Kumpulkan 60 ID Ruangan Kanonikal yang Sah
        $canonicalIds = collect($canonicalRooms)->pluck('id')->all();

        // 2. Ambil seluruh ruangan yang ada di database saat ini
        $allRooms = Room::all();

        // Helper fungsi pemetaan ruangan lama ke ruangan kanonikal
        $resolveTargetId = function($r) use ($canonicalRooms) {
            $raw = trim($r->name);
            $code = trim($r->code);

            // A. Lab Komputer 1..22
            if (preg_match('/^(?:LAB\s*KOMPUTER|LAB|LK)[\s\-_]*([0-9]{1,2})$/i', $raw, $m) || preg_match('/LAB-KOMPUTER-([0-9]{1,2})/i', $code, $m)) {
                $num = (int)$m[1];
                if ($num >= 1 && $num <= 22 && isset($canonicalRooms["lab_{$num}"])) {
                    return $canonicalRooms["lab_{$num}"]->id;
                }
            }

            // B. Lab IPA
            if (preg_match('/^(?:LAB\s*IPA|LABIPA|LAB\s*ILMU\s*PENGETAHUAN\s*ALAM|IPA)$/i', $raw) || preg_match('/LAB-ILMU-PENGETAHUAN-ALAM|LABIPA|LAB-IPA/i', $code)) {
                return $canonicalRooms['lab_ipa']->id;
            }

            // C. Lab Penataan Barang
            if (preg_match('/^(?:LAB\s*PB|LABPB|LAB\s*PENATAAN\s*BARANG|PB)$/i', $raw) || preg_match('/LAB-PENATAAN-BARANG|LAB-PB/i', $code)) {
                return $canonicalRooms['lab_pb']->id;
            }

            // D. Lapangan Olahraga
            if (preg_match('/^(?:OR|OR\s*[0-9]+|LAPANGAN|LAPANGAN\s*OLAHRAGA)$/i', $raw) || preg_match('/LAPANGAN-OLAHRAGA|LAP-OR|OR/i', $code)) {
                return $canonicalRooms['or']->id;
            }

            // E. HALL
            if (preg_match('/^(?:HALL|SENI|MENARI|RUANG\s*UNTUK\s*MENARI)$/i', $raw) || preg_match('/HALL|R-UNTUK-MENARI/i', $code)) {
                return $canonicalRooms['hall']->id;
            }

            // F. Ruang Teori 1..34 (Mendukung format: Angka langsung "10", "R10", "Ruang 10", "R-10", "R. 10")
            if (preg_match('/^(?:RUANG\s*TEORI|RUANG|R\.?|R\-)\s*([0-9]{1,2})$/i', $raw, $m) || (is_numeric($raw) && (int)$raw >= 1 && (int)$raw <= 34)) {
                $num = isset($m[1]) ? (int)$m[1] : (int)$raw;
                if ($num >= 1 && $num <= 34 && isset($canonicalRooms["ruang_{$num}"])) {
                    return $canonicalRooms["ruang_{$num}"]->id;
                }
            }

            // G. Seeder awal dummy
            if (preg_match('/LAB\s*RPL/i', $raw)) return $canonicalRooms['lab_1']->id;
            if (preg_match('/LAB\s*TKJ/i', $raw)) return $canonicalRooms['lab_2']->id;
            if (preg_match('/LAB\s*(DKV|MULTIMEDIA)/i', $raw)) return $canonicalRooms['lab_3']->id;
            if (preg_match('/RUANG\s*TEORI\s*1/i', $raw)) return $canonicalRooms['ruang_1']->id;
            if (preg_match('/RUANG\s*TEORI\s*2/i', $raw)) return $canonicalRooms['ruang_2']->id;

            // H. Fallback Class Rooms (misal: "Ruang X RPL 1", "Ruang XI BR 2")
            // Petakan ke ruang kelas teori 1..34 secara stabil
            if (preg_match('/^(?:RUANG\s*)?(X|XI|XII)\s*([A-Z]+)\s*([0-9]*)$/i', $raw)) {
                $hashNum = (abs(crc32(strtolower(trim($raw)))) % 34) + 1;
                return $canonicalRooms["ruang_{$hashNum}"]->id;
            }

            // Fallback default: Ruang 1
            return $canonicalRooms['ruang_1']->id;
        };

        // 3. Singkronkan seluruh Jadwal Pelajaran (schedules) yang sedang memanggil ruangan lama
        foreach ($allRooms as $oldRoom) {
            $targetId = $resolveTargetId($oldRoom);
            if ($oldRoom->id !== $targetId) {
                // Update jadwal yang memanggil room lama ke room kanonikal yang sah
                DB::table('schedules')->where('room_id', $oldRoom->id)->update(['room_id' => $targetId]);
            }
        }

        // 4. Hapus seluruh ruangan yang BUKAN 60 Ruangan Kanonikal Resmi
        Room::whereNotIn('id', $canonicalIds)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversible if needed
    }
};
