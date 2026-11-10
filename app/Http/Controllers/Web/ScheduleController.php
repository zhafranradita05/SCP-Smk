<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\Setting;

class ScheduleController extends Controller
{
    /**
     * Resolusi Cerdas Ruangan ke 60 Fasilitas Ruangan Resmi SMK Negeri 1 Bantul:
     * - 34 Ruang Teori (Ruang 1 s/d Ruang 34, mendukung angka langsung "10", "R10", "Ruang 10")
     * - 22 Lab Komputer (Lab Komputer 1 s/d Lab Komputer 22, mendukung format "LAB5", "LAB 5", "LK5")
     * - 1 Lab IPA ("LABIPA", "IPA", "Lab IPA")
     * - 1 Lab Penataan Barang ("LABPB", "PB", "Lab Penataan Barang")
     * - 1 Lapangan Olahraga ("OR", "OR1", "OR2", "Lapangan Olahraga")
     * - 1 HALL Seni/Menari ("HALL", "SENI", "MENARI")
     */
    public static function resolveCanonicalRoom($rawName, $rooms)
    {
        if (empty($rawName)) return null;
        $str = trim((string)$rawName, " \t\n\r\0\x0B'\"");
        if (empty($str) || $str === '-' || $str === '---') return null;

        $clean = preg_replace('/[^\p{L}\p{N}\s\.\-_]/u', '', $str);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if (empty($clean)) return null;

        // 1. Angka Langsung 1..34 (Catatan khusus: file jadwal sekolah langsung menggunakan angka tanpa huruf R)
        // Ataupun format Ruang 1..34, R-1..34, R. 1..34
        if ((is_numeric($clean) && (int)$clean >= 1 && (int)$clean <= 34) ||
            preg_match('/^(?:RUANG\s*TEORI|RUANG|R\.?|R\-)\s*([0-9]{1,2})$/i', $clean, $m)
        ) {
            $num = isset($m[1]) ? (int)$m[1] : (int)$clean;
            if ($num >= 1 && $num <= 34) {
                return $rooms->first(function ($r) use ($num) {
                    return $r->name === "Ruang {$num}" || $r->code === "R-{$num}" || strtolower($r->name) === "ruang {$num}";
                });
            }
        }

        // 2. Lab Komputer 1..22 (Mendukung: LAB1, LAB 1, LAB-1, LK1, L.1, L.16, Lab Komputer 1, dsb)
        if (preg_match('/^(?:LAB\s*KOMPUTER|LABORATORIUM|LAB|LK|L\.?)\s*([0-9]{1,2})$/i', $clean, $m)) {
            $num = (int)$m[1];
            if ($num >= 1 && $num <= 22) {
                return $rooms->first(function ($r) use ($num) {
                    return $r->name === "Lab Komputer {$num}" || $r->code === "LAB-KOMPUTER-{$num}" || $r->code === "LAB-{$num}" || str_contains(strtolower($r->name), "komputer {$num}");
                });
            }
        }

        // 3. Lab IPA
        if (preg_match('/^(?:LAB\s*IPA|LABIPA|LAB\s*ILMU\s*PENGETAHUAN\s*ALAM|IPA|L\.?\s*IPA)$/i', $clean)) {
            return $rooms->first(function ($r) {
                return $r->code === 'LAB-IPA' || $r->name === 'Lab IPA' || str_contains(strtolower($r->name), 'ilmu pengetahuan alam') || str_contains(strtolower($r->name), 'ipa');
            });
        }

        // 4. Lab Penataan Barang (PB)
        if (preg_match('/^(?:LAB\s*PB|LABPB|LAB\s*PENATAAN\s*BARANG|PB|L\.?\s*PB)$/i', $clean)) {
            return $rooms->first(function ($r) {
                return $r->code === 'LAB-PB' || str_contains(strtolower($r->name), 'penataan barang');
            });
        }

        // 5. Lapangan Olahraga (OR)
        if (preg_match('/^(?:OR|OR\s*[0-9]+|LAPANGAN|LAPANGAN\s*OLAHRAGA)$/i', $clean)) {
            return $rooms->first(function ($r) {
                return $r->code === 'LAP-OR' || str_contains(strtolower($r->name), 'olahraga');
            });
        }

        // 6. HALL (Ruang Seni/Menari)
        if (preg_match('/^(?:HALL|SENI|MENARI|RUANG\s*UNTUK\s*MENARI)$/i', $clean)) {
            return $rooms->first(function ($r) {
                return $r->code === 'HALL' || str_contains(strtolower($r->name), 'hall') || str_contains(strtolower($r->name), 'menari');
            });
        }

        // 7. Pencocokan langsung nama atau kode ruangan yang terdaftar
        $lower = strtolower($clean);
        $found = $rooms->first(function ($r) use ($lower) {
            $rName = strtolower(trim($r->name));
            $rCode = strtolower(trim($r->code));
            return $rName === $lower || $rCode === $lower || str_replace(['-', ' '], '', $rName) === str_replace(['-', ' '], '', $lower);
        });

        return $found;
    }

        /**
     * Download template Excel/CSV untuk import jadwal
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_jadwal.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        // Ambil data referensi agar contoh di template sesuai data nyata
        $sampleTeacher = User::whereIn('role', ['guru', 'wali_kelas'])->first()->name ?? 'Budi Hartono, S.Kom';
        $sampleClass = SchoolClass::first()->name ?? 'X RPL 1';
        $sampleRoom = Room::first()->name ?? 'Lab RPL 1';

        $callback = function () use ($sampleTeacher, $sampleClass, $sampleRoom) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM untuk Excel

            // Header kolom
            fputcsv($handle, ['guru', 'kelas', 'ruang', 'mapel', 'putaran', 'hari', 'jam_mulai', 'jam_selesai'], ';');

            // Baris Contoh 1 (Putaran 1)
            fputcsv($handle, [$sampleTeacher, $sampleClass, $sampleRoom, 'Pemrograman Web', 'putaran_1', 'Senin', '07:30', '10:00'], ';');
            // Baris Contoh 2 (Putaran 2)
            fputcsv($handle, [$sampleTeacher, $sampleClass, $sampleRoom, 'Basis Data', 'putaran_2', 'Selasa', '10:15', '12:45'], ';');

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import jadwal Multi-File (PDF, Excel XLSX/XLS, CSV, Text, JSON, HTML) dengan Auto-Detect & Auto Anti-Tabrakan
     */
    public function importSchedules(Request $request)
    {
        $setting = Setting::first();
        if ($setting && $setting->is_schedule_locked) {
            return back()->with('error', 'Gagal! Jadwal sedang di-KUNCI (Lock Schedule aktif). Buka kunci terlebih dahulu.');
        }

        $request->validate([
            'file'             => 'required|file|max:15360', // Maksimal 15MB
            'room_file'        => 'nullable|file|max:15360',
            'cycle'            => 'nullable|string|in:auto,putaran_1,putaran_2',
            'replace_existing' => 'nullable|boolean',
        ]);

        $replaceExisting = $request->boolean('replace_existing', false);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // 0. Tentukan Target Putaran / Siklus (Putaran 1 / Putaran 2 / Auto)
        $cycleInput = $request->input('cycle', 'auto');
        $activeCycle = $setting->active_cycle ?? 'putaran_1';

        $origLower = strtolower($originalName);
        $fileDetectedCycle = null;
        if (preg_match('/(putaran|put|p)[\s\-_]*2(?![0-9])/i', $origLower)) {
            $fileDetectedCycle = 'putaran_2';
        } elseif (preg_match('/(putaran|put|p)[\s\-_]*1(?![0-9])/i', $origLower)) {
            $fileDetectedCycle = 'putaran_1';
        }

        // Jika nama file jelas mencantumkan Putaran 1 atau Putaran 2 (contoh: JADWAL KELAS PUT 1.pdf), prioritaskan nama file
        if ($fileDetectedCycle) {
            $targetCycle = $fileDetectedCycle;
        } elseif ($cycleInput === 'putaran_1' || $cycleInput === 'putaran_2') {
            $targetCycle = $cycleInput;
        } else {
            $targetCycle = $activeCycle;
        }

        // 1. Ekstraksi Baris Data secara Otomatis dari File 1 (Jadwal Guru)
        $parsed = $this->parseAnyScheduleFile($file->getPathname(), $originalName);
        $formatName = $parsed['format'];
        $rows = $parsed['rows'];

        if (empty($rows) || count($rows) < 2) {
            return back()->with('error', "Gagal membaca isi jadwal dari file ({$formatName})! Pastikan file memiliki baris judul (header) dan baris jadwal.");
        }

        // 1b. Ekstraksi dan Pembuatan Kamus Pencocokan dari File 2 (Jadwal Ruangan, jika diunggah)
        $roomLookup = [];
        if ($request->hasFile('room_file')) {
            $roomFile = $request->file('room_file');
            $roomParsed = $this->parseAnyScheduleFile($roomFile->getPathname(), $roomFile->getClientOriginalName());
            $roomLookup = $this->buildRoomLookupFromGrid(
                $roomParsed['rows'] ?? [],
                $roomParsed['format'] ?? '',
                $roomFile->getPathname()
            );
        }

        // 2. DETEKSI OTOMATIS TEMPLATE KISI / MATRIX JADWAL (FET TIMETABLE)
        if ($this->isFetOrGridSchedule($rows, $formatName)) {
            return $this->processFetOrGridSchedules($rows, $formatName, $request, $targetCycle, $roomLookup, $replaceExisting);
        }

        // Ambil header dan bersihkan karakter aneh
        $header = array_map(function ($h) {
            return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', (string)$h)));
        }, $rows[0]);

        // Helper pencocokan kolom cerdas (tahan variasi spasi, underscore, bahasa inggris/indonesia)
        $findCol = function($keywords) use ($header) {
            foreach ($keywords as $kw) {
                $cleanKw = strtolower(str_replace(['_', '-', ' '], '', $kw));
                foreach ($header as $idx => $h) {
                    $cleanH = strtolower(str_replace(['_', '-', ' '], '', $h));
                    if ($cleanH === $cleanKw || str_contains($cleanH, $cleanKw)) {
                        return $idx;
                    }
                }
            }
            return false;
        };

        $colMap = [
            'guru'        => $findCol(['guru', 'teacher', 'pengajar', 'namaguru']),
            'kelas'       => $findCol(['kelas', 'class', 'namakelas']),
            'ruang'       => $findCol(['ruang', 'ruangan', 'room', 'lab']),
            'mapel'       => $findCol(['mapel', 'subject', 'matapelajaran', 'pelajaran']),
            'putaran'     => $findCol(['putaran', 'siklus', 'cycle', 'blok']),
            'hari'        => $findCol(['hari', 'day']),
            'jam_mulai'   => $findCol(['jammulai', 'mulai', 'start', 'waktumulai']),
            'jam_selesai' => $findCol(['jamselesai', 'selesai', 'end', 'waktuselesai']),
            'jam'         => $findCol(['jam', 'waktu', 'time']),
        ];

        // Fallback jika header tidak cocok sama sekali (gunakan urutan kolom standar)
        if ($colMap['guru'] === false && count($header) >= 6) {
            $colMap['guru']        = 0;
            $colMap['kelas']       = 1;
            $colMap['ruang']       = 2;
            $colMap['mapel']       = 3;
            $colMap['putaran']     = 4;
            $colMap['hari']        = 5;
            $colMap['jam_mulai']   = 6;
            $colMap['jam_selesai'] = 7;
        }

        // Preload master data ke memori untuk efisiensi
        $teacherList = User::whereIn('role', ['guru', 'wali_kelas'])->get();
        $teachers = collect();
        foreach ($teacherList as $u) {
            $teachers->put(strtolower(trim($u->name)), $u->id);
            if (!empty($u->username)) {
                $teachers->put(strtolower(trim($u->username)), $u->id);
            }
            if (!empty($u->identifier)) {
                $teachers->put(trim($u->identifier), $u->id);
            }
            // Bersihkan gelar (S.Kom, M.Kom, S.Pd, S.T, M.Ds, dll) dan sapaan
            $cleanName = preg_replace('/,.*$/', '', $u->name);
            $cleanName = preg_replace('/\b(bapak|ibu|pak|bu|drs|dra|ir|dr)\b\.?/i', '', $cleanName);
            $cleanName = strtolower(preg_replace('/\s+/', ' ', trim($cleanName)));
            if (!empty($cleanName)) {
                $teachers->put($cleanName, $u->id);
            }
        }

        $classes = SchoolClass::all()->mapWithKeys(function ($c) {
            return [strtolower(trim($c->name)) => $c->id];
        });

        $allRoomsModel = Room::all();
        $rooms = $allRoomsModel->mapWithKeys(function ($r) {
            $rName = strtolower(trim($r->name));
            $rCode = strtolower(trim($r->code));
            return [
                $rName => $r->id,
                $rCode => $r->id,
                str_replace(['-', ' '], '', $rName) => $r->id,
                str_replace(['-', ' '], '', $rCode) => $r->id,
            ];
        });

        // Helper parsing jam fleksibel (mendukung format desimal Excel, string, atau rentang)
        $parseTime = function($val) {
            if (empty($val) && $val !== '0' && $val !== 0) return '';
            $val = trim((string)$val);
            if (is_numeric($val) && (float)$val > 0 && (float)$val < 1) {
                $totalSeconds = round((float)$val * 86400);
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                return sprintf('%02d:%02d:00', $hours, $minutes);
            }
            $clean = str_replace('.', ':', $val);
            if (preg_match('/(\d{1,2}):(\d{2})/', $clean, $m)) {
                return sprintf('%02d:%02d:00', (int)$m[1], (int)$m[2]);
            }
            return '';
        };

        $successCount = 0;
        $conflictCount = 0;
        $skippedCount = 0;
        $conflictLogs = [];
        $skippedLogs = [];

        // Jika opsi Timpa/Bersihkan Putaran aktif, hapus dulu data lama di putaran ini
        if ($replaceExisting) {
            Schedule::where('cycle', $targetCycle)->delete();
        }

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty(array_filter($row))) {
                continue;
            }

            $guruStr  = ($colMap['guru'] !== false && isset($row[$colMap['guru']])) ? trim($row[$colMap['guru']]) : '';
            $kelasStr = ($colMap['kelas'] !== false && isset($row[$colMap['kelas']])) ? trim($row[$colMap['kelas']]) : '';
            $ruangStr = ($colMap['ruang'] !== false && isset($row[$colMap['ruang']])) ? trim($row[$colMap['ruang']]) : '';
            $mapel    = ($colMap['mapel'] !== false && isset($row[$colMap['mapel']])) ? trim($row[$colMap['mapel']]) : '';
            $putaran  = ($colMap['putaran'] !== false && isset($row[$colMap['putaran']])) ? trim($row[$colMap['putaran']]) : 'putaran_1';
            $hari     = ($colMap['hari'] !== false && isset($row[$colMap['hari']])) ? ucfirst(strtolower(trim($row[$colMap['hari']]))) : 'Senin';

            // Ekstraksi Jam
            $start = '';
            $end = '';
            if ($colMap['jam_mulai'] !== false && isset($row[$colMap['jam_mulai']])) {
                $start = $parseTime($row[$colMap['jam_mulai']]);
                $end   = ($colMap['jam_selesai'] !== false && isset($row[$colMap['jam_selesai']])) ? $parseTime($row[$colMap['jam_selesai']]) : '';
            }
            if ((empty($start) || empty($end)) && $colMap['jam'] !== false && isset($row[$colMap['jam']])) {
                $parts = explode('-', $row[$colMap['jam']]);
                $start = $parseTime($parts[0] ?? '');
                $end   = $parseTime($parts[1] ?? '');
            }

            // Normalisasi putaran (prioritaskan pilihan putaran di form jika bukan auto)
            $cycle = ($targetCycle !== 'auto' && !empty($targetCycle)) ? $targetCycle : ((str_contains(strtolower($putaran), '2')) ? 'putaran_2' : 'putaran_1');

            // 1. Cari ID Guru
            $rawGuru = strtolower(trim($guruStr));
            $cleanGuru = strtolower(preg_replace('/\s+/', ' ', trim(preg_replace('/\b(bapak|ibu|pak|bu)\b\.?/i', '', preg_replace('/,.*$/', '', $guruStr)))));
            $teacherId = $teachers->get($rawGuru) ?? $teachers->get($cleanGuru);

            // 2. Cari ID Kelas
            $classId = null;
            if (!empty($kelasStr)) {
                $rawK = trim($kelasStr);
                $cleanK = str_ireplace(['kelas', 'kls'], '', $rawK);
                $cleanK = preg_replace('/[-._]/', ' ', $cleanK);
                $cleanK = strtolower(preg_replace('/\s+/', ' ', trim($cleanK)));

                if (!empty($cleanK)) {
                    $cleanWithRoman = preg_replace('/^10\s+/', 'x ', $cleanK);
                    $cleanWithRoman = preg_replace('/^11\s+/', 'xi ', $cleanWithRoman);
                    $cleanWithRoman = preg_replace('/^12\s+/', 'xii ', $cleanWithRoman);

                    $candidates = [$cleanWithRoman, $cleanK];
                    if (!preg_match('/^(x|xi|xii)\s+/i', $cleanWithRoman)) {
                        $candidates[] = 'x ' . $cleanWithRoman;
                    }

                    $allVariations = [];
                    foreach ($candidates as $cand) {
                        $allVariations[] = $cand;
                        $alias = str_ireplace(['tjkt', 'pplg', 'mplb', 'otkp', 'akl'], ['tkj', 'rpl', 'mp', 'mp', 'mp'], $cand);
                        if ($alias !== $cand) {
                            $allVariations[] = $alias;
                        }
                    }

                    foreach ($allVariations as $var) {
                        if ($id = $classes->get($var)) {
                            $classId = $id;
                            break;
                        }
                    }

                    if (!$classId) {
                        foreach ($allVariations as $var) {
                            $noSpace = str_replace(' ', '', $var);
                            foreach ($classes as $dbName => $id) {
                                if (str_replace(' ', '', $dbName) === $noSpace) {
                                    $classId = $id;
                                    break 2;
                                }
                            }
                        }
                    }

                    if (!$classId) {
                        foreach ($allVariations as $var) {
                            foreach ($classes as $dbName => $id) {
                                if (str_ends_with($dbName, ' ' . $var) || $dbName === $var) {
                                    $classId = $id;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            // 3. Cari ID Ruangan (Mendukung 60 Ruang Kanonikal, angka langsung 1..34, LAB1..22, OR, HALL, IPA, PB)
            $canonicalRoom = self::resolveCanonicalRoom($ruangStr, $allRoomsModel);
            $roomId = $canonicalRoom ? $canonicalRoom->id : ($rooms->get(strtolower(trim($ruangStr))) ?? $rooms->get(str_replace(['-', ' '], '', strtolower(trim($ruangStr)))));

            // Validasi kelengkapan data per baris
            if (!$teacherId || !$classId || !$roomId || empty($mapel) || empty($start) || empty($end)) {
                $skippedCount++;
                $missingFields = [];
                if (!$teacherId) $missingFields[] = "Guru '{$guruStr}' tidak cocok";
                if (!$classId)   $missingFields[] = "Kelas '{$kelasStr}' belum terdaftar";
                if (!$roomId)    $missingFields[] = "Ruang '{$ruangStr}' tidak cocok";
                if (empty($mapel)) $missingFields[] = "Mapel kosong";
                if (empty($start) || empty($end)) $missingFields[] = "Jam tidak valid";
                $skippedLogs[] = "Baris " . ($i + 1) . " (" . implode(', ', $missingFields) . ")";
                continue;
            }

            // ====== LOGIKA ANTI-TABRAKAN OTOMATIS ======
            $conflict = Schedule::where('day', $hari)
                ->where('cycle', $cycle)
                ->where(function ($query) use ($teacherId, $classId, $roomId) {
                    $query->where('teacher_id', $teacherId)
                          ->orWhere('class_id', $classId)
                          ->orWhere('room_id', $roomId);
                })
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->first();

            if ($conflict) {
                $conflictCount++;
                $conflictReason = ($conflict->teacher_id == $teacherId) ? "Guru {$guruStr} sudah mengajar" : 
                                 (($conflict->class_id == $classId) ? "Kelas {$kelasStr} sudah ada jadwal" : "Ruang {$ruangStr} sudah terpakai");
                $conflictLogs[] = "Baris " . ($i + 1) . " ($conflictReason pada $hari $start-$end)";
                continue;
            }

            // Simpan Jadwal
            Schedule::create([
                'teacher_id' => $teacherId,
                'class_id'   => $classId,
                'room_id'    => $roomId,
                'subject'    => $mapel,
                'cycle'      => $cycle,
                'day'        => $hari,
                'start_time' => $start,
                'end_time'   => $end,
            ]);
            $successCount++;
        }

        if ($successCount === 0) {
            $errMsg = "Gagal mengimpor jadwal (0 jadwal ditambahkan)!";
            if (!empty($skippedLogs)) {
                $errMsg .= " Catatan kendala: " . implode('; ', array_slice($skippedLogs, 0, 3));
            } elseif (!empty($conflictLogs)) {
                $errMsg .= " Semua jadwal bentrok: " . implode('; ', array_slice($conflictLogs, 0, 3));
            }
            return back()->with('error', $errMsg);
        }

        $cycleLabel = ($targetCycle === 'putaran_2') ? 'Putaran 2' : 'Putaran 1';
        $msg = "Import Jadwal selesai ({$formatName}) untuk {$cycleLabel}! {$successCount} jadwal berhasil ditambahkan.";
        if ($replaceExisting) {
            $msg .= " (Jadwal lama {$cycleLabel} telah dibersihkan sebelum import).";
        }
        if ($conflictCount > 0) {
            $msg .= " {$conflictCount} jadwal dilewati karena tabrakan: " . implode('; ', array_slice($conflictLogs, 0, 2));
        }
        if ($skippedCount > 0) {
            $msg .= " {$skippedCount} jadwal dilewati: " . implode('; ', array_slice($skippedLogs, 0, 2));
        }

        return back()->with('success', $msg);
    }

    /**
     * Cek apakah struktur file adalah format Kisi / Grid / Matriks Jadwal (seperti ekspor FET Timetable)
     */
    private function isFetOrGridSchedule(array $rows, string $formatName = ''): bool
    {
        if (empty($rows)) return false;

        $daysList = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        // Cek baris header hari pada semua baris (melewati daftar isi PDF/panjang)
        foreach ($rows as $r) {
            $rowText = strtolower(implode(' ', $r));
            $dayCount = 0;
            foreach ($daysList as $d) {
                if (preg_match('/\b' . $d . '\b/i', $rowText)) {
                    $dayCount++;
                }
            }
            if ($dayCount >= 3) {
                return true;
            }
        }

        // Cek teks signature FET pada seluruh baris
        foreach ($rows as $r) {
            $rowText = implode(' ', $r);
            if (preg_match('/(Jadwal dihasilkan dengan FET|FET\s*\d+\.\d+|Timetable generated with FET)/i', $rowText)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parsing file PDF Matriks Jadwal Ruangan resmi SMK Negeri 1 Bantul (seperti JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf)
     * Format:
     * - Kolom: 48 Rombel / Kelas (Tingkat 10, 11, 12 x Jurusan RPL, TKJ, DKV, AK, LPS, MP, BR, BD)
     * - Baris: Jam ke-1 s/d Jam ke-10 untuk seluruh hari (Senin s/d Jumat)
     * - Sel: Kode Ruangan Kanonikal (1..34, L.1..22, OR, HALL, L.IPA, PB, dll)
     */
    private function parseRoomMatrixFromPdf(string $filePath, $rooms): array
    {
        $lookup = ['by_class' => [], 'by_teacher' => [], 'by_period' => []];
        $content = @file_get_contents($filePath);
        if (!$content) return $lookup;

        if (!preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            return $lookup;
        }

        // 1. Ekstraksi ToUnicode CMap
        $cMap = [];
        foreach ($matches[1] as $s) {
            $dec = @gzuncompress($s);
            if ($dec === false) $dec = @gzinflate($s);
            if ($dec === false && strlen($s) > 2) $dec = @gzinflate(substr($s, 2));
            if ($dec === false) continue;
            if (str_contains($dec, 'beginbfchar') || str_contains($dec, 'beginbfrange')) {
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/', $dec, $bf, PREG_SET_ORDER)) {
                    foreach ($bf as $m) $cMap[hexdec($m[1])] = mb_chr(hexdec($m[2]), 'UTF-8');
                }
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/', $dec, $bfr, PREG_SET_ORDER)) {
                    foreach ($bfr as $m) {
                        $st = hexdec($m[1]); $en = hexdec($m[2]); $dst = hexdec($m[3]);
                        for ($c = $st; $c <= $en; $c++) $cMap[$c] = mb_chr($dst + ($c - $st), 'UTF-8');
                    }
                }
            }
        }

        // 2. Ekstraksi teks per stream / page
        $allPages = [];
        foreach ($matches[1] as $s) {
            $dec = @gzuncompress($s);
            if ($dec === false) $dec = @gzinflate($s);
            if ($dec === false && strlen($s) > 2) $dec = @gzinflate(substr($s, 2));
            if ($dec === false || !str_contains($dec, 'BT')) continue;

            preg_match_all('/(?:(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+Tm|(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+T[dD]|\[(.*?)\]\s*TJ|\((.*?)(?<!\\\\)\)\s*(?:Tj|\'|\")|<([0-9A-Fa-f]+)>\s*(?:Tj|\'|\"))/s', $dec, $tokens, PREG_SET_ORDER);

            $tlmX = 0; $tlmY = 0; $tmX = 0; $tmY = 0;
            $items = [];
            foreach ($tokens as $t) {
                if (isset($t[6]) && $t[6] !== '') {
                    $tlmX = (float)$t[5]; $tlmY = (float)$t[6];
                    $tmX = $tlmX; $tmY = $tlmY;
                } elseif (isset($t[8]) && $t[8] !== '') {
                    $tlmX += (float)$t[7]; $tlmY += (float)$t[8];
                    $tmX = $tlmX; $tmY = $tlmY;
                } elseif (!empty($t[9])) {
                    preg_match_all('/(?:\((.*?)(?<!\\\\)\)|<([0-9A-Fa-f]+)>)/s', $t[9], $parts, PREG_SET_ORDER);
                    $str = '';
                    foreach ($parts as $p) {
                        if (!empty($p[1])) $str .= $p[1];
                        elseif (!empty($p[2])) {
                            for ($k = 0; $k < strlen($p[2]); $k += 4) {
                                $code = hexdec(substr($p[2], $k, 4));
                                $str .= $cMap[$code] ?? '';
                            }
                        }
                    }
                    if ($str !== '') $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $str];
                } elseif (isset($t[10]) && $t[10] !== '') {
                    $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $t[10]];
                } elseif (isset($t[11]) && $t[11] !== '') {
                    $str = '';
                    for ($k = 0; $k < strlen($t[11]); $k += 4) {
                        $code = hexdec(substr($t[11], $k, 4));
                        $str .= $cMap[$code] ?? '';
                    }
                    if ($str !== '') $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $str];
                }
            }
            if (!empty($items)) $allPages[] = $items;
        }

        if (empty($allPages)) return $lookup;

        $pageDays = [
            0 => ['Senin', 'Selasa'],
            1 => ['Rabu', 'Kamis'],
            2 => ['Jumat']
        ];

        $periodToTimes = [
            1 => ['07:15', '07:30', '08:00'],
            2 => ['08:00', '08:10', '08:35', '08:45'],
            3 => ['08:45', '08:50', '09:10', '09:30'],
            4 => ['09:45', '10:00'],
            5 => ['10:30', '10:35'],
            6 => ['11:15', '11:10'],
            7 => ['12:30', '12:45'],
            8 => ['13:15', '13:25'],
            9 => ['14:00'],
            10 => ['14:45'],
        ];

        $globalColMap = [];

        foreach ($allPages as $pIdx => $pItems) {
            if (!isset($pageDays[$pIdx])) continue;
            $possibleDays = $pageDays[$pIdx];
            $dayIndex = 0;
            $currentDay = $possibleDays[$dayIndex];

            $rows = [];
            foreach ($pItems as $it) {
                $placed = false;
                foreach ($rows as $y => &$grp) {
                    if (abs((float)$y - $it['y']) <= 3.0) {
                        $grp[] = $it; $placed = true; break;
                    }
                }
                unset($grp);
                if (!$placed) $rows[sprintf('%.1f', $it['y'])] = [$it];
            }
            krsort($rows, SORT_NUMERIC);

            // Deteksi baris header kolom
            $headerY = null;
            foreach ($rows as $y => $grp) {
                $rowStr = implode(' ', array_map(fn($it) => trim($it['text']), $grp));
                if (preg_match('/\bHari\b/i', $rowStr) && preg_match('/\bJam\b/i', $rowStr)) {
                    $headerY = (float)$y;
                    break;
                }
            }
            if (!$headerY) continue;

            // Kumpulkan token-token judul kelas di header
            $headerTokens = [];
            foreach ($pItems as $it) {
                if ($it['y'] >= $headerY - 15.0 && $it['y'] <= $headerY + 25.0 && $it['x'] > 100) {
                    $headerTokens[] = $it;
                }
            }
            usort($headerTokens, fn($a, $b) => $a['x'] <=> $b['x']);
            $colCenters = [];
            foreach ($headerTokens as $t) {
                $found = false;
                foreach ($colCenters as &$c) {
                    if (abs($c['x'] - $t['x']) <= 6.0) {
                        $c['tokens'][] = $t;
                        $sum = array_sum(array_column($c['tokens'], 'x'));
                        $c['x'] = $sum / count($c['tokens']);
                        $found = true;
                        break;
                    }
                }
                if (!$found) $colCenters[] = ['x' => $t['x'], 'tokens' => [$t]];
            }

            $pageColMap = [];
            foreach ($colCenters as $c) {
                $tokTexts = array_map(fn($t) => trim($t['text']), $c['tokens']);
                $grade = null; $major = null; $rombel = '';
                foreach ($tokTexts as $tt) {
                    if (in_array($tt, ['10', '11', '12'])) {
                        $grade = $tt === '10' ? 'X' : ($tt === '11' ? 'XI' : 'XII');
                    }
                    if (preg_match('/^(RPL|TKJ|DKV|AK|LPS|MP|BR|BD)\s*([0-9]*)$/i', $tt, $mm)) {
                        $major = strtoupper($mm[1]);
                        if ($mm[2] !== '') $rombel = $mm[2];
                    }
                    if (is_numeric($tt) && (int)$tt >= 1 && (int)$tt <= 4 && $tt !== '10' && $tt !== '11' && $tt !== '12') {
                        $rombel = $tt;
                    }
                }
                if ($grade && $major) {
                    $clsName = trim("$grade $major $rombel");
                    $cleanKey = strtolower(str_replace(' ', '', $clsName));
                    $pageColMap[] = [
                        'x' => $c['x'],
                        'class' => $clsName,
                        'key' => $cleanKey
                    ];
                }
            }

            $activeColMap = !empty($pageColMap) ? $pageColMap : $globalColMap;
            if (!empty($pageColMap)) $globalColMap = $pageColMap;

            $lastPeriod = 0;

            foreach ($rows as $y => $grp) {
                if ((float)$y >= $headerY - 10.0) continue;
                usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);

                // Cek subheader pergantian hari (misal baris Hari Jam Waktu di tengah halaman)
                $headerCheck = array_filter($grp, fn($it) => $it['x'] < 110 && preg_match('/\bHari\b/i', $it['text']));
                if (!empty($headerCheck)) {
                    if (isset($possibleDays[$dayIndex + 1])) {
                        $dayIndex++;
                        $currentDay = $possibleDays[$dayIndex];
                        $lastPeriod = 0;
                    }
                    continue;
                }

                // Cek nomor jam pelajaran secara presisi (kolom jam ada pada x 45 s/d 63)
                $periodTokens = array_filter($grp, fn($it) => $it['x'] >= 45 && $it['x'] <= 63);
                $periodNum = null;
                foreach ($periodTokens as $pt) {
                    $txt = trim($pt['text']);
                    if (is_numeric($txt) && (int)$txt >= 1 && (int)$txt <= 10) {
                        $periodNum = (int)$txt;
                        break;
                    }
                }
                if (!$periodNum) continue;

                // Pergantian hari jika nomor jam turun drastis (misal dari 10 ke 1)
                if ($lastPeriod >= 7 && $periodNum <= 2 && isset($possibleDays[$dayIndex + 1])) {
                    $dayIndex++;
                    $currentDay = $possibleDays[$dayIndex];
                }
                $lastPeriod = $periodNum;

                // Ekstraksi nilai ruangan untuk tiap kolom kelas
                foreach ($grp as $it) {
                    if ($it['x'] < 105) continue;
                    $val = trim($it['text']);
                    if (empty($val) || $val === '-' || $val === '---') continue;

                    $bestCol = null;
                    $minDist = 999;
                    foreach ($activeColMap as $c) {
                        $dist = abs($c['x'] - $it['x']);
                        if ($dist < $minDist && $dist <= 8.0) {
                            $minDist = $dist;
                            $bestCol = $c;
                        }
                    }

                    if ($bestCol) {
                        $clsKey = $bestCol['key'];
                        $roomObj = self::resolveCanonicalRoom($val, $rooms);
                        if ($roomObj) {
                            // Simpan pencocokan berdasarkan periode jam
                            $lookup['by_period'][$currentDay][$periodNum][$clsKey] = $roomObj;
                            $lookup['by_period'][$currentDay][$clsKey][$periodNum] = $roomObj;

                            // Simpan pencocokan berdasarkan waktu untuk kedua arah
                            if (isset($periodToTimes[$periodNum])) {
                                foreach ($periodToTimes[$periodNum] as $tStr) {
                                    $lookup['by_class'][$currentDay][$tStr][$clsKey] = $roomObj;
                                    $lookup['by_class'][$currentDay][$clsKey][$tStr] = $roomObj;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $lookup;
    }

    /**
     * Membangun kamus pencocokan ruangan dari file Jadwal Ruangan (PDF Matriks SMKN 1 Bantul / FET / HTML / XLSX / CSV)
     * Struktur output:
     * - ['by_class'][$day][$time][$normClass] => Room
     * - ['by_period'][$day][$period][$normClass] => Room
     * - ['by_teacher'][$day][$time][$cleanTeacher] => Room
     */
    private function buildRoomLookupFromGrid(array $rows, string $formatName = '', string $filePath = ''): array
    {
        $lookup = ['by_class' => [], 'by_teacher' => [], 'by_period' => []];
        $rooms = Room::all();

        // 0. Cek format Matriks Jadwal Ruangan PDF resmi SMK Negeri 1 Bantul (seperti JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf)
        if (!empty($filePath) && file_exists($filePath)) {
            $matrixLookup = $this->parseRoomMatrixFromPdf($filePath, $rooms);
            if (!empty($matrixLookup['by_class'])) {
                return $matrixLookup;
            }
        }

        if (empty($rows)) return $lookup;

        $dayMap = [
            'senin' => 'Senin', 'monday' => 'Senin',
            'selasa' => 'Selasa', 'tuesday' => 'Selasa',
            'rabu' => 'Rabu', 'wednesday' => 'Rabu',
            'kamis' => 'Kamis', 'thursday' => 'Kamis',
            'jumat' => 'Jumat', 'friday' => 'Jumat',
            'sabtu' => 'Sabtu', 'saturday' => 'Sabtu',
        ];

        $isRoomPattern = function($str) {
            if (empty($str) || strlen($str) > 50) return false;
            if (!mb_check_encoding($str, 'UTF-8')) return false;
            $clean = trim((string)$str);
            if (empty($clean)) return false;
            if (preg_match('/^(smk\s*negeri|daftar\s*isi|komentar|halaman|jadwal\s*dihasilkan|kembali|nama\s*institusi)/i', $clean)) {
                return false;
            }
            if (is_numeric($clean) && (int)$clean >= 1 && (int)$clean <= 34) {
                return true;
            }
            return (bool) preg_match('/^(LAB|LK|RUANG|R\.?|OR|HALL|IPA|PB|BENGKEL|STUDIO|AULA|TEORI)\b/i', $clean);
        };

        $getOrCreateRoom = function($rawName) use (&$rooms) {
            return self::resolveCanonicalRoom($rawName, $rooms);
        };

        $currentRoom = null;
        $currentDayCols = [];

        foreach ($rows as $rIdx => $row) {
            if (empty(array_filter($row))) continue;
            $rowClean = array_map(fn($v) => trim((string)$v), $row);

            // 1. Cek Header Hari
            $foundDays = [];
            foreach ($rowClean as $cIdx => $cVal) {
                $cLower = strtolower($cVal);
                if (isset($dayMap[$cLower])) {
                    $foundDays[$cIdx] = $dayMap[$cLower];
                }
            }
            if (count($foundDays) >= 3) {
                $currentDayCols = $foundDays;
                if (!$currentRoom) {
                    for ($back = $rIdx - 1; $back >= max(0, $rIdx - 5); $back--) {
                        if (!isset($rows[$back])) continue;
                        foreach ($rows[$back] as $pVal) {
                            if ($isRoomPattern($pVal)) {
                                $rCand = $getOrCreateRoom($pVal);
                                if ($rCand) {
                                    $currentRoom = $rCand;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                continue;
            }

            // 2. Cek Nama Ruangan (Header Tabel Ruang FET)
            if (count(array_filter($rowClean)) <= 3) {
                foreach ($rowClean as $cVal) {
                    if ($isRoomPattern($cVal)) {
                        $rCand = $getOrCreateRoom($cVal);
                        if ($rCand) {
                            $currentRoom = $rCand;
                            break;
                        }
                    }
                }
            }

            // 3. Cek Baris Jam
            if (!empty($currentDayCols) && $currentRoom) {
                $firstCell = $rowClean[0] ?? ($rowClean[1] ?? '');
                if (preg_match('/^(\d{1,2})\s*[:\.]?\s*(\d{2})/', $firstCell, $tm)) {
                    $timeStr = sprintf('%02d:%02d', (int)$tm[1], (int)$tm[2]);
                    $offset = (isset($currentDayCols[0]) && preg_match('/^(\d{1,2})\s*[:\.]?\s*(\d{2})/', $rowClean[0])) ? 1 : 0;

                    foreach ($currentDayCols as $cIdx => $day) {
                        $cellText = $rowClean[$cIdx + $offset] ?? '';
                        if (empty($cellText) || $cellText === '---' || $cellText === '-') continue;

                        $lines = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+|<br\s*\/?>/i', (string)$cellText))));
                        foreach ($lines as $line) {
                            // Cek nama kelas (mendukung format XIIBR1 maupun XII BR 1, minimal 2 huruf jurusan agar angka romawi XI tidak terpecah jadi X I)
                            if (preg_match('/^(XII|XI|X)[\s\-_]*([A-Za-z]{2,})\s*([0-9]*)$/i', $line, $m)) {
                                $cleanK = strtolower(trim($m[1] . ' ' . $m[2] . ($m[3] !== '' ? ' ' . $m[3] : '')));
                                $normK = str_replace(' ', '', $cleanK);
                                $lookup['by_class'][$day][$timeStr][$normK] = $currentRoom;
                                $lookup['by_class'][$day][$timeStr][$cleanK] = $currentRoom;
                            }
                            // Cek nama guru
                            $cleanT = \App\Http\Controllers\Web\UserController::cleanTeacherNameForMatching($line);
                            if (strlen($cleanT) >= 4) {
                                $lookup['by_teacher'][$day][$timeStr][$cleanT] = $currentRoom;
                            }
                        }
                    }
                }
            }
        }

        return $lookup;
    }

    /**
     * Pemrosesan File Jadwal Format Matriks / Kisi FET (Free Timetabling Software)
     * Mengelompokkan slot waktu mengajar, blok tatap muka, pencocokan ruangan silang, dan mencegah tabrakan jadwal
     */
    private function processFetOrGridSchedules(array $rows, string $formatName, Request $request, string $targetCycle = 'putaran_1', array $roomLookup = [], bool $replaceExisting = false)
    {
        // Jika opsi Timpa/Bersihkan Putaran aktif, hapus dulu data lama di target putaran ini
        if ($replaceExisting) {
            Schedule::where('cycle', $targetCycle)->delete();
        }

        // Preload master data
        $teacherList = User::whereIn('role', ['guru', 'wali_kelas', 'guru_bk'])->get();
        $classes = SchoolClass::all();
        $rooms = Room::all();

        // Pemetaan hari standar bahasa Indonesia
        $dayMap = [
            'senin' => 'Senin', 'monday' => 'Senin',
            'selasa' => 'Selasa', 'tuesday' => 'Selasa',
            'rabu' => 'Rabu', 'wednesday' => 'Rabu',
            'kamis' => 'Kamis', 'thursday' => 'Kamis',
            'jumat' => 'Jumat', 'friday' => 'Jumat',
            'sabtu' => 'Sabtu', 'saturday' => 'Sabtu',
        ];

        // Durasi & batas akhir standar jam pelajaran SMK (45 menit per jam)
        $slotEndsMap = [
            '07:15' => '08:00',
            '08:00' => '08:45',
            '08:45' => '09:30', // Istirahat 09:30 - 09:45
            '09:45' => '10:30',
            '10:30' => '11:15',
            '11:15' => '12:00', // Istirahat / Ishoma 12:00 - 12:30
            '12:30' => '13:15',
            '13:15' => '14:00',
            '14:00' => '14:45',
            '14:45' => '15:30',
        ];

        // Helper cari Guru
        $findTeacher = function($rawStr, $teacherCode = null) use ($teacherList) {
            $str = trim((string)$rawStr, " \t\n\r\0\x0B'\"“”");
            if (!$teacherCode && preg_match('/^([0-9]{1,3})[\.\s\-]+/', $str, $cm)) {
                $teacherCode = $cm[1];
            }
            $clean = preg_replace('/^[0-9]+[\.\s\-]+/', '', $str);
            $clean = trim($clean, " \t\n\r\0\x0B'\"“”");
            if (empty($clean) || strlen($clean) < 3) return null;

            // 1. Cocokkan langsung (case-insensitive)
            $lower = strtolower($clean);
            $t = $teacherList->first(fn($u) => strtolower(trim($u->name)) === $lower || strtolower(trim($u->username)) === $lower);
            if ($t) return $t;

            // 2. Cocokkan nama bersih tanpa gelar & tanda baca (cleanTeacherNameForMatching)
            $cTarget = \App\Http\Controllers\Web\UserController::cleanTeacherNameForMatching($clean);
            if (strlen($cTarget) >= 4) {
                $cTargetCompressed = str_replace(' ', '', $cTarget);
                $t = $teacherList->first(function($u) use ($cTarget, $cTargetCompressed) {
                    $uClean = \App\Http\Controllers\Web\UserController::cleanTeacherNameForMatching($u->name);
                    return $uClean === $cTarget || str_replace(' ', '', $uClean) === $cTargetCompressed;
                });
                if ($t) return $t;
            }

            // 3. Cocokkan kode nomor FET jika terdaftar di akun guru (identifier)
            if ($teacherCode) {
                $t = $teacherList->first(fn($u) => trim((string)$u->identifier) === (string)$teacherCode);
                if ($t) return $t;
            }

            // 4. Substring match
            $t = $teacherList->first(function($u) use ($clean) {
                return str_contains(strtolower($u->name), strtolower($clean)) || str_contains(strtolower($clean), strtolower($u->name));
            });
            if ($t) return $t;

            // 5. Token match (kesamaan kata kunci nama tanpa gelar, misal Anastya vs Anastasia)
            $tokens = array_filter(explode(' ', preg_replace('/[^a-z]/', ' ', strtolower($clean))));
            if (count($tokens) >= 2) {
                $t = $teacherList->first(function($u) use ($tokens) {
                    $uTokens = array_filter(explode(' ', preg_replace('/[^a-z]/', ' ', strtolower($u->name))));
                    $common = array_intersect($tokens, $uTokens);
                    return count($common) >= 2;
                });
                if ($t) return $t;
            }

            return null;
        };

        // Helper cari Kelas
        $findClass = function($classStr) use (&$classes) {
            if (empty($classStr)) return null;
            $raw = trim((string)$classStr, " \t\n\r\0\x0B'\"");
            // Bersihkan nomor urut FET (misal: "1..XII BR 1" -> "XII BR 1")
            $raw = preg_replace('/^[0-9]+[\.\s\-]+/', '', $raw);
            $raw = trim($raw, " \t\n\r\0\x0B'\"");
            if (empty($raw) || strlen($raw) < 2) return null;

            if (preg_match('/^(smk\s*negeri|daftar\s*isi|komentar|halaman|jadwal\s*dihasilkan|kembali)/i', $raw)) {
                return null;
            }

            $clean = strtolower(preg_replace('/\s+/', ' ', trim(str_ireplace(['kelas', 'kls'], '', $raw))));
            
            // Normalisasi Romawi
            $clean = preg_replace('/^10\s+/', 'x ', $clean);
            $clean = preg_replace('/^11\s+/', 'xi ', $clean);
            $clean = preg_replace('/^12\s+/', 'xii ', $clean);

            // Pisahkan spasi nomor rombel (misal: "XII BR1" -> "xii br 1", minimal 2 huruf jurusan)
            if (preg_match('/^(xii|xi|x)\s*([a-z]{2,})\s*([0-9]*)$/i', $clean, $m)) {
                $clean = trim($m[1] . ' ' . $m[2] . ($m[3] !== '' ? ' ' . $m[3] : ''));
            }

            foreach ($classes as $c) {
                $cLower = strtolower(trim($c->name));
                if ($cLower === $clean || str_replace(' ', '', $cLower) === str_replace(' ', '', $clean)) {
                    return $c->id;
                }
            }

            // Alias Kurikulum Merdeka
            $alias = str_ireplace(['tjkt', 'pplg', 'mplb', 'akl', 'bdp'], ['tkj', 'rpl', 'mp', 'ak', 'bd'], $clean);
            foreach ($classes as $c) {
                $cLower = strtolower(trim($c->name));
                if ($cLower === $alias || str_replace(' ', '', $cLower) === str_replace(' ', '', $alias)) {
                    return $c->id;
                }
            }

            // Jika belum ada dan merupakan nama kelas sah (harus memuat tingkat romawi dan jurusan minimal 2 huruf)
            if (preg_match('/^(x|xi|xii)\s+[a-z]{2,}\b/i', $clean)) {
                $created = SchoolClass::create(['name' => strtoupper($clean)]);
                $classes->push($created);
                return $created->id;
            }

            return null;
        };

        // Helper cari Ruang Kanonikal
        $findRoom = function($roomStr) use (&$rooms) {
            if (empty($roomStr)) return null;
            $canonical = self::resolveCanonicalRoom($roomStr, $rooms);
            return $canonical ? $canonical->id : null;
        };

        // Helper parse isi sel jadwal FET (Jadwal Guru: sel berisi Kelas, Mapel, Ruang)
        $parseCell = function($cellText) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+|<br\s*\/?>/i', (string)$cellText))));
            $class = null;
            $room = null;
            $subjectParts = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line === '---' || $line === '-') continue;

                // Cek Kelas (mendukung format XIIBR1 maupun XII BR 1, minimal 2 huruf jurusan)
                if (!$class && preg_match('/^(XII|XI|X)[\s\-_]*([A-Za-z]{2,})\s*([0-9]*)$/i', $line, $m)) {
                    $class = trim($m[1] . ' ' . $m[2] . ($m[3] !== '' ? ' ' . $m[3] : ''));
                    continue;
                }

                // Cek Ruang (Mendukung angka langsung 1..34, LAB1..22, OR, HALL, IPA, PB, dll)
                if (!$room && (
                    (is_numeric($line) && (int)$line >= 1 && (int)$line <= 34) ||
                    preg_match('/^(?:LAB|LK|LABORATORIUM)[\s\-_]*[0-9]{1,2}$|^(?:RUANG|R\.?|R\-)[\s\-_]*[0-9]{1,2}$|^(?:OR|OR\s*[0-9]+|LAPANGAN|LAPANGAN\s*OLAHRAGA)$|^(?:HALL|SENI|MENARI)$|^(?:LABIPA|IPA|LAB\s*IPA)$|^(?:PB|LAB\s*PB|PENATAAN\s*BARANG)$/i', $line)
                )) {
                    $room = trim($line);
                    continue;
                }

                $subjectParts[] = $line;
            }

            $subject = implode(' ', $subjectParts);
            return [
                'class'   => $class,
                'room'    => $room,
                'subject' => $subject,
            ];
        };

        // Helper parse isi sel jadwal FET (Jadwal Per Kelas: sel berisi Guru, Mapel, Ruang)
        $parseClassCell = function($cellText) use ($findTeacher) {
            $raw = trim((string)$cellText);
            if ($raw === '' || $raw === '---' || $raw === '-' || $raw === '-x-') {
                return null;
            }

            $lines = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+|<br\s*\/?>/i', $raw))));
            if (empty($lines)) return null;

            $roomPattern = '/^(?:LAB|LK|LABORATORIUM|L\.?)\s*[0-9]{1,2}$|^(?:RUANG|R\.?|R\-)[\s\-_]*[0-9]{1,2}$|^(?:OR|OR\s*[0-9]+|LAPANGAN|LAPANGAN\s*OLAHRAGA)$|^(?:HALL|SENI|MENARI)$|^(?:LABIPA|IPA|LAB\s*IPA|L\.?\s*IPA)$|^(?:PB|LAB\s*PB|PENATAAN\s*BARANG|L\.?\s*PB)$/i';
            $trailingRoomPattern = '/\b(LAB\s*\d+|LK\s*\d+|L\.?\s*\d+|LAB\s*IPA|L\.?\s*IPA|LAB\s*PB|L\.?\s*PB|OR\s*\d*|RUANG\s*\d+|R\s*\d+)$/i';

            $teacher = null;
            $teacherStr = null;
            $teacherCode = null;
            $room = null;
            $subjectParts = [];

            // Gabungkan baris bertingkat di dalam tanda kutip (misal "34.DWI \n LESMIYATI, S.Pd.")
            $mergedLines = [];
            $inQuote = false;
            $quoteBuf = '';

            foreach ($lines as $line) {
                $hasLeadingQuote = str_starts_with($line, '"') || preg_match('/^"[0-9]+[\.\s\-]/', $line);
                $hasTrailingQuote = str_ends_with($line, '"');

                if (!$inQuote) {
                    if ($hasLeadingQuote && !$hasTrailingQuote) {
                        $inQuote = true;
                        $quoteBuf = $line;
                    } else {
                        $mergedLines[] = $line;
                    }
                } else {
                    $quoteBuf .= ' ' . $line;
                    if ($hasTrailingQuote || str_contains($line, '"')) {
                        $inQuote = false;
                        $mergedLines[] = trim($quoteBuf);
                        $quoteBuf = '';
                    }
                }
            }
            if ($inQuote && !empty($quoteBuf)) {
                $mergedLines[] = trim($quoteBuf);
            }

            foreach ($mergedLines as $line) {
                $cleanLine = trim($line, " \t\n\r\0\x0B");
                if ($cleanLine === '' || $cleanLine === '---' || $cleanLine === '-' || $cleanLine === '-x-') continue;

                // 1. Cek Ruang mandiri (Mendukung angka langsung 1..34, LAB1..22, OR, HALL, IPA, PB, dll)
                if (!$room && (
                    (is_numeric($cleanLine) && (int)$cleanLine >= 1 && (int)$cleanLine <= 34) ||
                    preg_match($roomPattern, $cleanLine)
                )) {
                    $room = $cleanLine;
                    continue;
                }

                // 2. Cek apakah ini baris Guru:
                // Sesuai format FET Timetable:
                // - Diberi tanda kutip " (seperti "82. RAHMAWATI YOGA PAMUNGKAS, M.Pd")
                // - Diawali kode nomor guru FET (seperti 57.RUSDIYANTO, S.Pd)
                // - Atau memuat gelar guru resmi
                $isTeacherLine = false;
                if (!$teacher) {
                    if (str_starts_with($cleanLine, '"') || str_ends_with($cleanLine, '"')) {
                        $isTeacherLine = true;
                    } elseif (preg_match('/^[0-9]{1,3}\s*[\.\-]\s*[A-Za-z]/', $cleanLine)) {
                        $isTeacherLine = true;
                    } elseif (preg_match('/\b(S\.Pd|M\.Pd|S\.T|S\.Kom|M\.Kom|S\.Sos|S\.Pd\.I|S\.Pd\.Si|SS|M\.Ds|S\.IP)\b/i', $cleanLine)) {
                        $isTeacherLine = true;
                    }
                }

                if ($isTeacherLine) {
                    // Deteksi jika ada kode ruangan di ujung baris guru (misal: 57.RUSDIY ANT O, S.Pd LAB 16)
                    if (preg_match($trailingRoomPattern, $cleanLine, $tRoom)) {
                        if (!$room) {
                            $room = trim($tRoom[1]);
                        }
                        $cleanLine = trim(substr($cleanLine, 0, -strlen($tRoom[0])));
                    }

                    if (preg_match('/^"?([0-9]{1,3})[\.\s\-]+/', $cleanLine, $cm)) {
                        $teacherCode = $cm[1];
                    }

                    $cleanLine = trim($cleanLine, " \t\n\r\0\x0B'\"“”");
                    $cleanLine = preg_replace('/^[0-9]+[\.\s\-]+/', '', $cleanLine);
                    $cleanLine = trim($cleanLine, " \t\n\r\0\x0B'\"“”");

                    // Defragment spasi pada nama guru hasil ekstraksi PDF
                    $cleanLine = preg_replace_callback('/\b([A-Z]{2,})\s+([A-Z])\s+([A-Z]{1,2})\b/u', fn($m) => $m[1].$m[2].$m[3], $cleanLine);
                    $cleanLine = preg_replace_callback('/\b([A-Z]{2,})\s+([A-Z]{1,2})\b/u', fn($m) => $m[1].$m[2], $cleanLine);

                    $teacherStr = $cleanLine;
                    $teacher = $findTeacher($cleanLine, $teacherCode);
                    continue;
                }

                // 3. Jika bukan ruang dan bukan guru, maka ini baris Mata Pelajaran
                $subjectParts[] = $cleanLine;
            }

            // Jika guru belum ditemukan tapi ada string calon guru, coba findTeacher lagi
            if (!$teacher && $teacherStr) {
                $teacher = $findTeacher($teacherStr, $teacherCode);
            }

            return [
                'teacher'      => $teacher,
                'teacher_name' => $teacherStr,
                'teacher_code' => $teacherCode,
                'room'         => $room,
                'subject'      => implode(' ', $subjectParts),
            ];
        };

        // Helper deteksi judul tabel: apakah mewakili Guru atau mewakili Kelas
        $detectEntity = function($val) use ($findTeacher, $findClass, $classes) {
            if (empty($val)) return null;
            $str = trim((string)$val, " \t\n\r\0\x0B'\"");
            if (strlen($str) < 2) return null;
            if (preg_match('/^(smk\s*negeri|daftar\s*isi|komentar|halaman|jadwal\s*dihasilkan|kembali)/i', $str)) {
                return null;
            }

            // 1. Cek Guru
            $teacher = $findTeacher($str);
            if ($teacher) {
                return ['type' => 'teacher', 'entity' => $teacher, 'name' => $teacher->name];
            }

            // 2. Cek Kelas
            $classId = $findClass($str);
            if ($classId) {
                $classModel = $classes->firstWhere('id', $classId);
                $className = $classModel ? $classModel->name : strtoupper(trim(preg_replace('/^[0-9]+[\.\s\-]+/', '', $str)));
                return ['type' => 'class', 'entity' => $classId, 'name' => $className];
            }

            return null;
        };

        // Algoritma Pemecahan Tabel Guru / Kelas dari Baris
        $tables = [];
        $currentEntity = null;
        $currentDayCols = [];
        $currentGrid = [];
        $currentTime = null;

        foreach ($rows as $rIdx => $row) {
            if (empty(array_filter($row))) continue;

            $rowClean = array_map(fn($v) => trim((string)$v), $row);
            $rowString = implode(' ', $rowClean);

            // Lewati baris navigasi / metadata FET
            if (preg_match('/(Kembali\s*ke\s*atas|Jadwal\s*dihasilkan|Daftar\s*isi|Komentar\s*baku)/i', $rowString)) {
                $currentTime = null;
                continue;
            }

            // 1. Cek apakah baris ini adalah baris Hari (Header Kolom)
            $foundDays = [];
            foreach ($rowClean as $cIdx => $cVal) {
                $cLower = strtolower($cVal);
                if (isset($dayMap[$cLower])) {
                    $foundDays[$cIdx] = $dayMap[$cLower];
                }
            }

            if (count($foundDays) >= 3) {
                // Jika tabel sebelumnya sudah ada isinya, simpan ke antrean tables
                if ($currentEntity && !empty($currentGrid)) {
                    $tables[] = [
                        'entity'   => $currentEntity,
                        'dayCols'  => $currentDayCols,
                        'grid'     => $currentGrid
                    ];
                    $currentGrid = [];
                }

                $currentDayCols = $foundDays;
                $currentTime = null;

                // Jika belum dapat entitas (guru atau kelas), cek mundur 1-5 baris ke atas
                if (!$currentEntity) {
                    for ($back = $rIdx - 1; $back >= max(0, $rIdx - 5); $back--) {
                        if (!isset($rows[$back])) continue;
                        $prevRow = $rows[$back];
                        foreach ($prevRow as $pVal) {
                            $eCand = $detectEntity($pVal);
                            if ($eCand) {
                                $currentEntity = $eCand;
                                break 2;
                            }
                        }
                    }
                }
                continue;
            }

            // 2. Cek apakah baris ini memuat nama Guru / Kelas (Header Tabel FET)
            if (count(array_filter($rowClean)) <= 3) {
                foreach ($rowClean as $cVal) {
                    $eCand = $detectEntity($cVal);
                    if ($eCand) {
                        if ($currentEntity && !empty($currentGrid)) {
                            $tables[] = [
                                'entity'   => $currentEntity,
                                'dayCols'  => $currentDayCols,
                                'grid'     => $currentGrid
                            ];
                            $currentGrid = [];
                            $currentDayCols = [];
                        }
                        $currentEntity = $eCand;
                        $currentTime = null;
                        break;
                    }
                }
            }

            // 3. Cek apakah baris ini adalah baris Jam (Slot Waktu: 07:15, 08:00, 07 15, 0715, dst)
            if (!empty($currentDayCols)) {
                $firstCell = $rowClean[0] ?? ($rowClean[1] ?? '');
                if (preg_match('/^(\d{1,2})\s*[:\.]?\s*(\d{2})/', $firstCell, $tm)) {
                    $currentTime = sprintf('%02d:%02d', (int)$tm[1], (int)$tm[2]);
                    $offset = (isset($currentDayCols[0]) && preg_match('/^(\d{1,2})\s*[:\.]?\s*(\d{2})/', $rowClean[0])) ? 1 : 0;
                    foreach ($currentDayCols as $cIdx => $day) {
                        $val = $rowClean[$cIdx + $offset] ?? '';
                        if ($val !== '') {
                            $currentGrid[$day][$currentTime] = $val;
                        }
                    }
                } elseif ($currentTime !== null) {
                    // Baris lanjutan untuk slot jam saat ini (mapel/ruang multi-line di dalam sel)
                    foreach ($currentDayCols as $cIdx => $day) {
                        if (isset($rowClean[$cIdx]) && $rowClean[$cIdx] !== '') {
                            $existing = $currentGrid[$day][$currentTime] ?? '';
                            $currentGrid[$day][$currentTime] = trim($existing . "\n" . $rowClean[$cIdx]);
                        }
                    }
                }
            }
        }

        // Simpan tabel terakhir jika ada
        if ($currentEntity && !empty($currentGrid)) {
            $tables[] = [
                'entity'   => $currentEntity,
                'dayCols'  => $currentDayCols,
                'grid'     => $currentGrid
            ];
        }

        if (empty($tables)) {
            return back()->with('error', "Gagal mengenali struktur jadwal FET! Pastikan terdapat baris nama guru atau kelas dan kolom hari (Senin, Selasa, dst).");
        }

        $successCount = 0;
        $conflictCount = 0;
        $conflictLogs = [];

        // Proses Setiap Tabel (Bisa berupa Tabel Guru atau Tabel Kelas)
        foreach ($tables as $tData) {
            $entity = $tData['entity'];
            $grid = $tData['grid'];

            foreach ($grid as $day => $daySlots) {
                $times = array_keys($daySlots);
                $slotCount = count($times);

                for ($i = 0; $i < $slotCount; $i++) {
                    $time = $times[$i];
                    $cellVal = $daySlots[$time] ?? '';

                    if (empty($cellVal) || $cellVal === '---' || $cellVal === '-' || $cellVal === '-x-') {
                        continue;
                    }

                    if ($entity['type'] === 'teacher') {
                        $teacher = $entity['entity'];
                        $parsed = $parseCell($cellVal);
                        $classId = $findClass($parsed['class']);
                        $className = $parsed['class'];
                        $subject = $parsed['subject'] ?: 'Mata Pelajaran';
                        $cellRoom = $parsed['room'];
                    } else {
                        // Tipe Tabel Per Kelas
                        $classId = $entity['entity'];
                        $className = $entity['name'];
                        $parsed = $parseClassCell($cellVal);
                        if (!$parsed) {
                            continue; // Sel kosong / jam selesai mengajar (Jumat siang)
                        }
                        $teacher = $parsed['teacher'];
                        $subject = $parsed['subject'] ?: 'Mata Pelajaran';
                        $cellRoom = $parsed['room'];
                    }

                    if (!$teacher || !$classId) {
                        continue;
                    }

                    $startTime = $time . ':00';
                    $lastSlot = $time;

                    // Gabungkan slot jam berurutan yang mengajar kelas & mapel yang sama (Blok Pelajaran)
                    // HANYA jika slot berikutnya memiliki guru dan mapel yang sama.
                    // Sel kosong atau '-x-' menandakan pelajaran selesai (misal Jumat selesai jam 14.00), JANGAN digabung!
                    while ($i + 1 < $slotCount) {
                        $nextTime = $times[$i + 1];
                        $nextVal = $daySlots[$nextTime] ?? '';

                        if (empty($nextVal) || $nextVal === '-x-' || $nextVal === '-' || $nextVal === '---') {
                            break;
                        }

                        if ($entity['type'] === 'teacher') {
                            $nextParsed = $parseCell($nextVal);
                            if ($nextParsed && $nextParsed['class'] === $parsed['class'] && strtolower(trim($nextParsed['subject'])) === strtolower(trim($subject))) {
                                if (!$cellRoom && !empty($nextParsed['room'])) {
                                    $cellRoom = $nextParsed['room'];
                                }
                                $lastSlot = $nextTime;
                                $i++;
                            } else {
                                break;
                            }
                        } else {
                            $nextParsed = $parseClassCell($nextVal);
                            if ($nextParsed &&
                                (($nextParsed['teacher'] && $teacher && $nextParsed['teacher']->id === $teacher->id) ||
                                 ($nextParsed['teacher_name'] && $nextParsed['teacher_name'] === ($parsed['teacher_name'] ?? ''))) &&
                                strtolower(trim($nextParsed['subject'])) === strtolower(trim($subject))
                            ) {
                                if (!$cellRoom && !empty($nextParsed['room'])) {
                                    $cellRoom = $nextParsed['room'];
                                }
                                $lastSlot = $nextTime;
                                $i++;
                            } else {
                                break;
                            }
                        }
                    }

                    $endTime = ($slotEndsMap[$lastSlot] ?? \Carbon\Carbon::parse($lastSlot)->addMinutes(45)->format('H:i')) . ':00';

                    // Resolusi Ruangan:
                    // 1. Cek ruang eksplisit di file/sel (contoh LAB 16, OR1, Ruang 12)
                    $roomId = null;
                    if (!empty($cellRoom)) {
                        $roomId = $findRoom($cellRoom);
                    }

                    // 2. Jika belum ada, cari dari kamus file jadwal ruangan (Cross-Matching)
                    if (!$roomId && !empty($roomLookup)) {
                        $cleanClassKey = strtolower(str_replace(' ', '', $className ?? ''));
                        if (!empty($cleanClassKey)) {
                            // Coba by_class dengan time string langsung (contoh: '07:15', '08:00')
                            if (isset($roomLookup['by_class'][$day][$time][$cleanClassKey])) {
                                $roomId = $roomLookup['by_class'][$day][$time][$cleanClassKey]->id;
                            } elseif (isset($roomLookup['by_class'][$day][$cleanClassKey][$time])) {
                                $roomId = $roomLookup['by_class'][$day][$cleanClassKey][$time]->id;
                            } else {
                                // Coba period slot (periode 1..10)
                                $timeSlotToPeriod = [
                                    '07:15' => 1, '07:30' => 1, '08:00' => 1,
                                    '08:45' => 2,
                                    '09:45' => 4, '10:00' => 4,
                                    '10:30' => 5, '10:35' => 5,
                                    '11:15' => 6,
                                    '12:30' => 7, '12:45' => 7,
                                    '13:15' => 8, '13:25' => 8,
                                    '14:00' => 9,
                                    '14:45' => 10,
                                ];
                                $pNum = $timeSlotToPeriod[$time] ?? null;
                                if ($pNum && isset($roomLookup['by_period'][$day][$pNum][$cleanClassKey])) {
                                    $roomId = $roomLookup['by_period'][$day][$pNum][$cleanClassKey]->id;
                                } elseif ($pNum && isset($roomLookup['by_period'][$day][$cleanClassKey][$pNum])) {
                                    $roomId = $roomLookup['by_period'][$day][$cleanClassKey][$pNum]->id;
                                } elseif ($pNum && isset($roomLookup['by_class'][$day][$pNum][$cleanClassKey])) {
                                    $roomId = $roomLookup['by_class'][$day][$pNum][$cleanClassKey]->id;
                                }
                            }
                        }
                        // Fallback: Cocokkan by teacher jika tersedia
                        if (!$roomId && $teacher) {
                            $cleanTeacherKey = \App\Http\Controllers\Web\UserController::cleanTeacherNameForMatching($teacher->name);
                            if (isset($roomLookup['by_teacher'][$day][$time][$cleanTeacherKey])) {
                                $roomId = $roomLookup['by_teacher'][$day][$time][$cleanTeacherKey]->id;
                            }
                        }
                    }

                    // 3. Jika tetap belum ada ruangan, fallback ke Ruang Teori Kanonikal 1..34 sesuai Kelas
                    if (!$roomId && $classId) {
                        $targetRoomNum = (($classId - 1) % 34) + 1;
                        $defaultRoom = $rooms->first(function($r) use ($targetRoomNum) {
                            return $r->code === "R-{$targetRoomNum}" || $r->name === "Ruang {$targetRoomNum}";
                        }) ?: $rooms->first(function($r) {
                            return $r->code === 'R-1' || $r->name === 'Ruang 1';
                        }) ?: $rooms->first();

                        $roomId = $defaultRoom ? $defaultRoom->id : null;
                    }

                    if (!$roomId) {
                        continue;
                    }

                    // Algoritma Anti-Tabrakan Otomatis berdasarkan target siklus
                    $conflict = Schedule::where('day', $day)
                        ->where('cycle', $targetCycle)
                        ->where(function ($q) use ($teacher, $classId, $roomId) {
                            $q->where('teacher_id', $teacher->id)
                              ->orWhere('class_id', $classId)
                              ->orWhere('room_id', $roomId);
                        })
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->first();

                    if ($conflict) {
                        $conflictCount++;
                        $conflictLogs[] = "Guru {$teacher->name} / Kelas {$className} pada $day $startTime-$endTime";
                        continue;
                    }

                    Schedule::create([
                        'teacher_id' => $teacher->id,
                        'class_id'   => $classId,
                        'room_id'    => $roomId,
                        'subject'    => $subject,
                        'cycle'      => $targetCycle,
                        'day'        => $day,
                        'start_time' => $startTime,
                        'end_time'   => $endTime,
                    ]);
                    $successCount++;
                }
            }
        }

        $cycleLabel = ($targetCycle === 'putaran_2') ? 'Putaran 2 (Normatif & Adaptif)' : 'Putaran 1 (Produktif)';
        $msg = "Import Jadwal FET selesai ({$formatName}) untuk {$cycleLabel}! {$successCount} sesi jadwal tatap muka otomatis disimpan.";
        if ($replaceExisting) {
            $msg .= " (Jadwal lama {$cycleLabel} telah dibersihkan sebelum import).";
        }
        if (!empty($roomLookup)) {
            $msg .= " Pencocokan silang ruangan aktif dari File Jadwal Ruangan.";
        }
        if ($conflictCount > 0) {
            $msg .= " ({$conflictCount} jadwal dilewati karena tabrakan: " . implode('; ', array_slice($conflictLogs, 0, 2)) . ")";
        }

        return back()->with('success', $msg);
    }

    /**
     * Deteksi dan parsing file jadwal dari berbagai format secara otomatis
     * Mendukung: PDF, Excel (.xlsx, .xls), CSV, TSV, TXT, JSON, HTML Table
     */
    private function parseAnyScheduleFile($filePath, $originalName = '')
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $handle = @fopen($filePath, 'rb');
        $magic = $handle ? fread($handle, 1024) : '';
        if ($handle) fclose($handle);

        // 1. Format PDF
        if (str_starts_with($magic, '%PDF-') || $ext === 'pdf') {
            $rows = $this->parsePdfRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'PDF', 'rows' => $rows];
            }
        }

        // 2. Format Excel Modern (XLSX / ZIP XML)
        if (str_starts_with($magic, "PK\x03\x04") || in_array($ext, ['xlsx', 'xlsm', 'xltx'])) {
            $rows = $this->parseXlsxRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'Excel (.xlsx)', 'rows' => $rows];
            }
        }

        // 3. Format JSON
        $trimmedMagic = ltrim($magic);
        if (str_starts_with($trimmedMagic, '[') || str_starts_with($trimmedMagic, '{') || $ext === 'json') {
            $rows = $this->parseJsonRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'JSON', 'rows' => $rows];
            }
        }

        // 4. Format HTML / Tabel Web (Sering dipakai ekspor sistem sekolah yang berakhiran .xls)
        if (stripos($magic, '<table') !== false || stripos($magic, '<html') !== false || in_array($ext, ['html', 'htm'])) {
            $rows = $this->parseHtmlRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'HTML Table / Excel Web', 'rows' => $rows];
            }
        }

        // 5. Format CSV / Delimited File (Comma, Semicolon, Tab, Pipe)
        $rows = $this->parseCsvRows($filePath);
        if (!empty($rows)) {
            $label = ($ext === 'tsv') ? 'TSV' : (($ext === 'txt') ? 'Text File' : 'CSV / Delimited');
            return ['format' => $label, 'rows' => $rows];
        }

        // 6. Generic Text Parsing Fallback
        $rawText = @file_get_contents($filePath);
        if ($rawText) {
            $rows = $this->convertRawTextToRows($rawText);
            if (!empty($rows)) {
                return ['format' => 'Teks Terstruktur', 'rows' => $rows];
            }
        }

        return ['format' => strtoupper($ext ?: 'Tidak Diketahui'), 'rows' => []];
    }

    /**
     * Parsing dokumen PDF murni PHP tanpa dependensi luar
     * Mendukung: ToUnicode CMap, Hex CID (<...>), Literal String ((...)), Ekstraksi Koordinat Vektor Baris & Kolom (BT...ET)
     * Kompatibel penuh dengan PDF cetakan Chrome/Edge (Skia), Cairo/Excel, dan ekspor langsung FET Timetable
     */
    private function parsePdfRows($filePath)
    {
        $content = @file_get_contents($filePath);
        if (!$content) return [];

        if (!preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            return [];
        }

        // 1. Ekstraksi ToUnicode CMap dari seluruh stream PDF
        $cMap = [];
        foreach ($matches[1] as $s) {
            $dec = @gzuncompress($s);
            if ($dec === false) $dec = @gzinflate($s);
            if ($dec === false && strlen($s) > 2) $dec = @gzinflate(substr($s, 2));
            if ($dec === false) continue;

            if (str_contains($dec, 'beginbfchar') || str_contains($dec, 'beginbfrange')) {
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/', $dec, $bfMatches, PREG_SET_ORDER)) {
                    foreach ($bfMatches as $m) {
                        $cMap[hexdec($m[1])] = mb_chr(hexdec($m[2]), 'UTF-8');
                    }
                }
                if (preg_match_all('/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/', $dec, $bfrMatches, PREG_SET_ORDER)) {
                    foreach ($bfrMatches as $m) {
                        $start = hexdec($m[1]);
                        $end   = hexdec($m[2]);
                        $dst   = hexdec($m[3]);
                        for ($code = $start; $code <= $end; $code++) {
                            $cMap[$code] = mb_chr($dst + ($code - $start), 'UTF-8');
                        }
                    }
                }
            }
        }

        $cleanText = function($str) {
            if ($str === null || $str === '') return '';
            $str = preg_replace('/[\x00-\x1F\x7F]/u', ' ', (string)$str);
            $str = mb_convert_encoding((string)$str, 'UTF-8', 'UTF-8');
            return trim(preg_replace('/\s+/', ' ', $str));
        };

        $decodeHex = function($hexStr) use ($cMap, $cleanText) {
            $len = strlen($hexStr);
            $res = '';
            for ($i = 0; $i < $len; $i += 4) {
                $glyphHex = substr($hexStr, $i, 4);
                $glyphId = hexdec($glyphHex);
                $res .= $cMap[$glyphId] ?? '';
            }
            return $cleanText($res);
        };

        $decodePdfStr = function($str) use ($cleanText) {
            $str = str_replace(
                ['\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)', '\\\\'],
                ["\n", "\r", "\t", "\x08", "\x0C", "(", ")", "\\"],
                $str
            );
            $str = preg_replace_callback('/\\\([0-7]{1,3})/', fn($m) => chr(octdec($m[1])), $str);
            return $cleanText($str);
        };

        $dayKeywords = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
        $allRows = [];

        // 2. Ekstraksi dan Rekonstruksi Koordinat Teks Vektor
        foreach ($matches[1] as $stream) {
            $dec = @gzuncompress($stream);
            if ($dec === false) $dec = @gzinflate($stream);
            if ($dec === false && strlen($stream) > 2) $dec = @gzinflate(substr($stream, 2));
            if ($dec === false || !str_contains($dec, 'BT')) continue;

            $sample = substr($dec, 0, 100);
            if (str_starts_with($sample, "\x00\x01\x00\x00") || str_starts_with($sample, 'OTTO') || str_starts_with($sample, 'wOFF')) {
                continue;
            }

            preg_match_all('/(?:BT\b|ET\b|\/F\w+\s+(\d+(?:\.\d+)?)\s+Tf|(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+Tm|(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+T[dD]|\[(.*?)\]\s*TJ|\((.*?)(?<!\\\\)\)\s*(?:Tj|\'|\")|<([0-9A-Fa-f]+)>\s*(?:Tj|\'|\")|\bT\*)/s', $dec, $tokens, PREG_SET_ORDER);

            $tlmX = 0; $tlmY = 0; $tmX = 0; $tmY = 0;
            $scaleX = 10.0; $scaleY = -10.0;
            $fontSize = 1.0;
            $items = [];

            foreach ($tokens as $t) {
                $raw = $t[0];
                if ($raw === 'BT') { $tlmX = 0; $tlmY = 0; $tmX = 0; $tmY = 0; continue; }
                if ($raw === 'ET') continue;

                if (isset($t[1]) && $t[1] !== '') {
                    $fontSize = (float)$t[1];
                    continue;
                }

                if (isset($t[7]) && $t[7] !== '') {
                    $scaleX = (float)$t[2]; $scaleY = (float)$t[5];
                    $tlmX   = (float)$t[6]; $tlmY   = (float)$t[7];
                    $tmX    = $tlmX;        $tmY    = $tlmY;
                    continue;
                }
                if (isset($t[9]) && $t[9] !== '') {
                    $tlmX += (float)$t[8] * $scaleX;
                    $tlmY += (float)$t[9] * $scaleY;
                    $tmX = $tlmX;
                    $tmY = $tlmY;
                    continue;
                }

                $fontScale = ($fontSize > 0 ? $fontSize : 1.0) * abs($scaleX);
                if ($fontScale < 1.0) $fontScale = 10.0;

                if (!empty($t[10])) {
                    preg_match_all('/(?:\((.*?)(?<!\\\\)\)|<([0-9A-Fa-f]+)>|(\-?\d+(?:\.\d+)?))/s', $t[10], $parts, PREG_SET_ORDER);
                    foreach ($parts as $p) {
                        if (!empty($p[1])) {
                            $txt = $decodePdfStr($p[1]);
                            if ($txt !== '') {
                                $w = mb_strlen($txt) * 0.55 * $fontScale;
                                $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $txt, 'w' => $w];
                                $tmX += $w;
                            }
                        } elseif (!empty($p[2])) {
                            $txt = $decodeHex($p[2]);
                            if ($txt !== '') {
                                $w = mb_strlen($txt) * 0.55 * $fontScale;
                                $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $txt, 'w' => $w];
                                $tmX += $w;
                            }
                        } elseif (isset($p[3])) {
                            $tmX -= ((float)$p[3] / 1000.0) * $fontScale;
                        }
                    }
                    continue;
                }
                if (isset($t[11]) && $t[11] !== '') {
                    $txt = $decodePdfStr($t[11]);
                    if ($txt !== '') {
                        $w = mb_strlen($txt) * 0.55 * $fontScale;
                        $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $txt, 'w' => $w];
                        $tmX += $w;
                    }
                    continue;
                }
                if (isset($t[12]) && $t[12] !== '') {
                    $txt = $decodeHex($t[12]);
                    if ($txt !== '') {
                        $w = mb_strlen($txt) * 0.55 * $fontScale;
                        $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $txt, 'w' => $w];
                        $tmX += $w;
                    }
                    continue;
                }
            }

            if (empty($items)) continue;

            // Kelompokkan item teks menjadi baris berdasarkan koordinat Y (toleransi 3.5px)
            $rowGroups = [];
            foreach ($items as $it) {
                $placed = false;
                foreach ($rowGroups as $yRef => &$grp) {
                    if (abs((float)$yRef - $it['y']) <= 3.5) {
                        $grp[] = $it; $placed = true; break;
                    }
                }
                unset($grp);
                if (!$placed) {
                    $rowGroups[sprintf('%.1f', $it['y'])] = [$it];
                }
            }

            $isTopDown = ($scaleY < 0);
            if ($isTopDown) {
                uksort($rowGroups, fn($a, $b) => (float)$a <=> (float)$b);
            } else {
                uksort($rowGroups, fn($a, $b) => (float)$b <=> (float)$a);
            }

            // Cari baris header nama-nama hari (Senin, Selasa, dst)
            $headerRow = null;
            $dayCols = [];
            $daysInRow = [];
            foreach ($rowGroups as $yRef => $grp) {
                usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);
                $foundDays = [];
                foreach ($grp as $it) {
                    $l = strtolower(trim($it['text']));
                    if (in_array($l, $dayKeywords)) {
                        $foundDays[] = ['day' => ucfirst($l), 'x' => $it['x']];
                    }
                }
                if (count($foundDays) >= 3) {
                    $headerRow = (float)$yRef;
                    $daysInRow = $foundDays;
                    break;
                }
            }

            // Jika bukan halaman tabel matriks hari (misal daftar isi), kembalikan baris teks berurutan
            if (!$headerRow) {
                foreach ($rowGroups as $grp) {
                    usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);
                    $line = [];
                    foreach ($grp as $it) $line[] = $it['text'];
                    $allRows[] = $line;
                }
                continue;
            }

            // Judul / Caption Tabel (Nama Guru atau Kelas)
            $captionParts = [];
            foreach ($rowGroups as $yRef => $grp) {
                if ($isTopDown ? ((float)$yRef >= $headerRow) : ((float)$yRef <= $headerRow)) break;
                usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);
                $rowStr = '';
                $lastEnd = null;
                foreach ($grp as $it) {
                    if ($lastEnd !== null && ($it['x'] - $lastEnd) > 3.0) {
                        $rowStr .= ' ';
                    }
                    $rowStr .= trim($it['text']);
                    $lastEnd = $it['x'] + $it['w'];
                }
                $rowStr = trim($rowStr);
                if (empty($rowStr) || preg_match('/^(smk\s*negeri|komentar|daftar\s*isi)/i', $rowStr)) continue;
                $captionParts[] = $rowStr;
            }
            $caption = end($captionParts) ?: 'Jadwal';

            // Ekstraksi garis batas vertikal kolom
            preg_match_all('/(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+re/s', $dec, $rects, PREG_SET_ORDER);
            $vlines = [];
            foreach ($rects as $r) {
                $w = (float)$r[3]; $h = (float)$r[4];
                if ($w <= 2.0 && $h > 30.0) $vlines[] = (float)$r[1];
            }
            if (preg_match_all('/(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+m\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+l/s', $dec, $pathLines, PREG_SET_ORDER)) {
                foreach ($pathLines as $pl) {
                    if (abs((float)$pl[1] - (float)$pl[3]) <= 2.0 && abs((float)$pl[2] - (float)$pl[4]) > 30.0) {
                        $vlines[] = (float)$pl[1];
                    }
                }
            }

            sort($vlines);
            $uniqueV = [];
            foreach ($vlines as $v) {
                if (empty($uniqueV) || ($v - end($uniqueV)) > 5.0) $uniqueV[] = $v;
            }

            if (count($uniqueV) >= count($daysInRow) + 1) {
                foreach ($daysInRow as $dayInfo) {
                    $dx = $dayInfo['x'];
                    for ($k = 0; $k < count($uniqueV) - 1; $k++) {
                        if ($dx >= $uniqueV[$k] && $dx <= $uniqueV[$k+1]) {
                            $dayCols[$dayInfo['day']] = [
                                'minX' => $uniqueV[$k],
                                'maxX' => $uniqueV[$k+1],
                                'x'    => $dx,
                            ];
                            break;
                        }
                    }
                }
            }

            if (count($dayCols) < count($daysInRow)) {
                $dayCols = [];
                for ($i = 0; $i < count($daysInRow); $i++) {
                    $curX = $daysInRow[$i]['x'];
                    if ($i === 0) {
                        $w = (count($daysInRow) > 1) ? ($daysInRow[1]['x'] - $curX) : 80.0;
                        $minX = $curX - $w * 0.45;
                        $maxX = (count($daysInRow) > 1) ? ($curX + $daysInRow[1]['x']) / 2.0 : ($curX + $w * 0.5);
                    } elseif ($i === count($daysInRow) - 1) {
                        $prevX = $daysInRow[$i - 1]['x'];
                        $minX = ($prevX + $curX) / 2.0;
                        $maxX = $curX + ($curX - $prevX) * 0.55;
                    } else {
                        $minX = ($daysInRow[$i - 1]['x'] + $curX) / 2.0;
                        $maxX = ($curX + $daysInRow[$i + 1]['x']) / 2.0;
                    }
                    $dayCols[$daysInRow[$i]['day']] = ['minX' => $minX, 'maxX' => $maxX, 'x' => $curX];
                }
            }

            // Ekstraksi slot waktu dari kolom jam (X < minX kolom hari pertama)
            $firstDayCol = reset($dayCols);
            $timeColMaxX = $firstDayCol ? $firstDayCol['minX'] : 999.0;
            $rawTimes = [];

            foreach ($rowGroups as $yRef => $grp) {
                if ($isTopDown ? ((float)$yRef <= $headerRow + 5.0) : ((float)$yRef >= $headerRow - 5.0)) continue;
                usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);
                $timeStr = '';
                $lastEnd = null;
                foreach ($grp as $it) {
                    if ($it['x'] < $timeColMaxX) {
                        if ($lastEnd !== null && ($it['x'] - $lastEnd) > 3.0) {
                            $timeStr .= ' ';
                        }
                        $timeStr .= trim($it['text']);
                        $lastEnd = $it['x'] + $it['w'];
                    }
                }
                $timeClean = preg_replace('/[^\d]+/', ':', trim($timeStr));
                if (preg_match('/(\d{1,2}):(\d{2})/', $timeClean, $tm)) {
                    $slotKey = sprintf('%02d:%02d', (int)$tm[1], (int)$tm[2]);
                    if (!isset($rawTimes[$slotKey])) $rawTimes[$slotKey] = (float)$yRef;
                }
            }

            if (empty($rawTimes)) continue;

            $times = array_keys($rawTimes);
            $timeYs = array_values($rawTimes);
            $slotCenters = [];
            for ($k = 0; $k < count($times); $k++) {
                $slotCenters[$times[$k]] = $timeYs[$k];
            }

            $slotHeight = (count($timeYs) >= 2) ? abs($timeYs[1] - $timeYs[0]) : 45.0;
            $minTableY = min($timeYs) - $slotHeight * 1.5;
            $maxTableY = max($timeYs) + $slotHeight * 1.5;

            // Inisialisasi kisi matriks
            $grid = [];
            foreach ($times as $tm) {
                foreach (array_keys($dayCols) as $d) {
                    $grid[$tm][$d] = '';
                }
            }

            // Ekstraksi sel per kolom hari menggunakan garis batas horizontal
            foreach ($dayCols as $day => $d) {
                $colWidth = $d['maxX'] - $d['minX'];
                $rawHLines = [];
                foreach ($rects as $r) {
                    $rx = (float)$r[1]; $ry = (float)$r[2]; $rw = (float)$r[3]; $rh = (float)$r[4];
                    if ($rw > 25.0 && $rh <= 2.0) {
                        $overlap = min($rx + $rw, $d['maxX']) - max($rx, $d['minX']);
                        if ($overlap >= 0.55 * $colWidth) {
                            if ($ry >= $minTableY && $ry <= $maxTableY) {
                                $rawHLines[] = $ry;
                            }
                        }
                    }
                }
                sort($rawHLines);

                $cleanLines = [];
                foreach ($rawHLines as $y) {
                    if (empty($cleanLines)) $cleanLines[] = $y;
                    else {
                        $last = end($cleanLines);
                        if (abs($y - $last) <= 2.5) $cleanLines[count($cleanLines) - 1] = ($last + $y) / 2.0;
                        else $cleanLines[] = $y;
                    }
                }

                if (count($cleanLines) >= 2) {
                    for ($i = 0; $i < count($cleanLines) - 1; $i++) {
                        $topY = min($cleanLines[$i], $cleanLines[$i + 1]);
                        $botY = max($cleanLines[$i], $cleanLines[$i + 1]);
                        if ($botY - $topY < 5.0) continue;

                        $cItems = array_filter($items, function($it) use ($d, $topY, $botY) {
                            return $it['x'] >= $d['minX'] && $it['x'] <= $d['maxX'] &&
                                   $it['y'] >= $topY - 1.0 && $it['y'] <= $botY + 1.0;
                        });

                        $lGroups = [];
                        foreach ($cItems as $it) {
                            $placed = false;
                            foreach ($lGroups as $yKey => &$grp) {
                                if (abs((float)$yKey - $it['y']) <= 3.5) {
                                    $grp[] = $it; $placed = true; break;
                                }
                            }
                            unset($grp);
                            if (!$placed) $lGroups[sprintf('%.1f', $it['y'])] = [$it];
                        }
                        if ($isTopDown) {
                            uksort($lGroups, fn($a, $b) => (float)$a <=> (float)$b);
                        } else {
                            uksort($lGroups, fn($a, $b) => (float)$b <=> (float)$a);
                        }

                        $cellLines = [];
                        foreach ($lGroups as $grp) {
                            usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);
                            $str = ''; $lastEnd = null;
                            foreach ($grp as $it) {
                                if ($lastEnd !== null && ($it['x'] - $lastEnd) > 2.5) $str .= ' ';
                                $str .= $it['text'];
                                $lastEnd = $it['x'] + $it['w'];
                            }
                            $clean = trim($str);
                            if ($clean !== '' && !preg_match('/(Jadwal dihasilkan|halaman|Kembali ke atas|file:\/\/\/)/i', $clean)) {
                                $cellLines[] = $clean;
                            }
                        }

                        // Normalisasi tanda kutip nama guru bertingkat (misal: "34.DWI \n LESMIYATI, S.Pd.")
                        $normLines = [];
                        $inQuote = false;
                        $quoteBuf = '';
                        foreach ($cellLines as $cl) {
                            $startQ = str_starts_with($cl, '"') || preg_match('/^"?[0-9]+[\.\s\-]/', $cl);
                            $endQ   = str_ends_with($cl, '"');
                            if (!$inQuote) {
                                if ($startQ && !$endQ) {
                                    $inQuote = true;
                                    $quoteBuf = $cl;
                                } else {
                                    $normLines[] = $cl;
                                }
                            } else {
                                $quoteBuf .= ' ' . $cl;
                                if ($endQ || str_contains($cl, '"')) {
                                    $inQuote = false;
                                    $normLines[] = trim($quoteBuf);
                                    $quoteBuf = '';
                                }
                            }
                        }
                        if ($inQuote && !empty($quoteBuf)) $normLines[] = trim($quoteBuf);

                        $cellText = implode("\n", $normLines);
                        if ($cellText === '') continue;

                        foreach ($slotCenters as $sTime => $cY) {
                            if ($cY >= $topY && $cY < $botY) {
                                $grid[$sTime][$day] = $cellText;
                            }
                        }
                    }
                } else {
                    // Fallback clustering berdasarkan titik tengah nominal slot jam
                    $colItems = array_filter($items, function($it) use ($d, $headerRow, $isTopDown) {
                        return $it['x'] >= $d['minX'] && $it['x'] <= $d['maxX'] && ($isTopDown ? ($it['y'] > $headerRow + 5.0) : ($it['y'] < $headerRow - 5.0));
                    });
                    if (empty($colItems)) continue;

                    $lGroups = [];
                    foreach ($colItems as $it) {
                        $placed = false;
                        foreach ($lGroups as $yKey => &$grp) {
                            if (abs((float)$yKey - $it['y']) <= 3.5) {
                                $grp[] = $it; $placed = true; break;
                            }
                        }
                        unset($grp);
                        if (!$placed) $lGroups[sprintf('%.1f', $it['y'])] = [$it];
                    }
                    if ($isTopDown) {
                        uksort($lGroups, fn($a, $b) => (float)$a <=> (float)$b);
                    } else {
                        uksort($lGroups, fn($a, $b) => (float)$b <=> (float)$a);
                    }

                    $textLines = [];
                    foreach ($lGroups as $yKey => $grp) {
                        usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);
                        $str = ''; $lastEnd = null;
                        foreach ($grp as $it) {
                            if ($lastEnd !== null && ($it['x'] - $lastEnd) > 2.5) $str .= ' ';
                            $str .= $it['text'];
                            $lastEnd = $it['x'] + $it['w'];
                        }
                        $clean = trim($str);
                        if ($clean !== '' && !preg_match('/(Jadwal dihasilkan|halaman|Kembali ke atas|file:\/\/\/)/i', $clean)) {
                            $textLines[] = ['y' => (float)$yKey, 'text' => $clean];
                        }
                    }

                    $blocks = []; $curBlock = [];
                    foreach ($textLines as $tl) {
                        if (empty($curBlock)) {
                            $curBlock[] = $tl;
                        } else {
                            $lastY = end($curBlock)['y'];
                            if (abs($tl['y'] - $lastY) <= 25.0) $curBlock[] = $tl;
                            else { $blocks[] = $curBlock; $curBlock = [$tl]; }
                        }
                    }
                    if (!empty($curBlock)) $blocks[] = $curBlock;

                    foreach ($blocks as $blk) {
                        $startY = min($blk[0]['y'], end($blk)['y']);
                        $endY   = max($blk[0]['y'], end($blk)['y']);
                        $cellText = implode("\n", array_column($blk, 'text'));
                        foreach ($slotCenters as $sTime => $cY) {
                            if ($cY >= $startY - 15.0 && $cY <= $endY + 15.0) {
                                $grid[$sTime][$day] = $cellText;
                            }
                        }
                    }
                }
            }

            // Emit Baris Judul Tabel
            $allRows[] = [$caption];

            // Emit Baris Header Kolom Hari
            $headerLine = ['Jam'];
            foreach (array_keys($dayCols) as $d) $headerLine[] = $d;
            $allRows[] = $headerLine;

            // Emit Baris-Baris Jam Pelajaran
            foreach ($times as $tm) {
                $row = [$tm];
                foreach (array_keys($dayCols) as $d) {
                    $row[] = $grid[$tm][$d];
                }
                $allRows[] = $row;
            }
        }

        return $allRows;
    }

    /**
     * Mengubah teks mentah atau teks hasil ekstraksi PDF menjadi baris & kolom data
     */
    private function convertRawTextToRows($rawText)
    {
        $rawLines = preg_split('/\r\n|\n|\r/', $rawText);
        $rows = [];
        $delimiters = [';', "\t", ',', '|'];

        foreach ($rawLines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $bestDelim = null;
            $maxParts = 1;
            foreach ($delimiters as $d) {
                $parts = str_getcsv($line, $d);
                if (count($parts) > $maxParts) {
                    $maxParts = count($parts);
                    $bestDelim = $d;
                }
            }

            if ($bestDelim !== null && $maxParts >= 3) {
                $row = array_map('trim', str_getcsv($line, $bestDelim));
            } else {
                // Deteksi khusus jika baris memuat minimal 3 nama hari (Header Hari FET)
                $daysRegex = '/\b(senin|selasa|rabu|kamis|jumat|sabtu|monday|tuesday|wednesday|thursday|friday)\b/i';
                if (preg_match_all($daysRegex, $line, $dayMatches) && count($dayMatches[0]) >= 3) {
                    $row = array_values(array_filter(array_map('trim', preg_split('/\s+/', $line))));
                } else {
                    // Pisahkan spasi ganda atau tab
                    $parts = preg_split('/\s{2,}|\t+/', $line);
                    if (count($parts) >= 3) {
                        $row = array_map('trim', $parts);
                    } elseif (preg_match('/^(\d{1,2}[:\.]\d{2})\s+(.*)$/', $line)) {
                        $row = array_values(array_filter(array_map('trim', preg_split('/\s+/', $line))));
                    } else {
                        $row = [$line];
                    }
                }
            }

            if (!empty(array_filter($row))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Parsing file JSON (Mendukung array of objects maupun array of arrays)
     */
    private function parseJsonRows($filePath)
    {
        $content = @file_get_contents($filePath);
        if (!$content) return [];

        $data = json_decode($content, true);
        if (!is_array($data)) return [];

        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        } elseif (isset($data['schedules']) && is_array($data['schedules'])) {
            $data = $data['schedules'];
        } elseif (isset($data['jadwal']) && is_array($data['jadwal'])) {
            $data = $data['jadwal'];
        }

        if (empty($data)) return [];

        $first = reset($data);
        if (is_array($first) && !isset($first[0])) {
            $headers = array_keys($first);
            $rows = [$headers];
            foreach ($data as $item) {
                if (!is_array($item)) continue;
                $row = [];
                foreach ($headers as $h) {
                    $row[] = (string)($item[$h] ?? '');
                }
                $rows[] = $row;
            }
            return $rows;
        }

        if (is_array($first)) {
            return $data;
        }

        return [];
    }

    /**
     * Parsing tabel HTML (Mendukung ekspor FET Timetable, tabel web, dan Excel-HTML legacy)
     */
    private function parseHtmlRows($filePath)
    {
        $content = @file_get_contents($filePath);
        if (!$content) return [];

        // Ubah <br> menjadi newline agar baris teks dalam sel tidak tergabung rapat
        $content = preg_replace('/<br\s*\/?>/i', "\n", $content);

        $rows = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            // Ambil teks caption jika ada (FET sering menaruh nama guru di <caption>)
            $captions = $table->getElementsByTagName('caption');
            if ($captions->length > 0) {
                $captionText = trim($captions->item(0)->textContent);
                if (!empty($captionText)) {
                    $rows[] = [$captionText];
                }
            }

            $trList = $table->getElementsByTagName('tr');
            $grid = [];
            $rowIdx = 0;

            foreach ($trList as $tr) {
                $colIdx = 0;
                $cells = [];
                foreach ($tr->childNodes as $node) {
                    if ($node instanceof \DOMElement && in_array(strtolower($node->nodeName), ['td', 'th'])) {
                        $cells[] = $node;
                    }
                }

                foreach ($cells as $cell) {
                    // Cari indeks kolom yang belum terisi rowspan dari baris sebelumnya
                    while (isset($grid[$rowIdx][$colIdx])) {
                        $colIdx++;
                    }

                    $cellText = trim($cell->textContent);
                    $rowspan = (int)$cell->getAttribute('rowspan');
                    if ($rowspan < 1) $rowspan = 1;
                    $colspan = (int)$cell->getAttribute('colspan');
                    if ($colspan < 1) $colspan = 1;
                    
                    for ($r = 0; $r < $rowspan; $r++) {
                        for ($c = 0; $c < $colspan; $c++) {
                            $grid[$rowIdx + $r][$colIdx + $c] = $cellText;
                        }
                    }

                    $colIdx += $colspan;
                }
                $rowIdx++;
            }

            // Normalisasi grid menjadi baris array urut
            ksort($grid);
            foreach ($grid as $rKey => $rowCols) {
                ksort($rowCols);
                $rowValues = array_values($rowCols);
                if (!empty(array_filter($rowValues))) {
                    $rows[] = $rowValues;
                }
            }
        }

        return $rows;
    }

    /**
     * Parsing file Excel modern (.xlsx) murni menggunakan ZipArchive & SimpleXML
     */
    private function parseXlsxRows($filePath)
    {
        $rows = [];
        if (!class_exists('\ZipArchive')) return [];

        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $sharedStrings = [];
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml) {
                $xmlStrings = @simplexml_load_string($sharedStringsXml);
                if ($xmlStrings && isset($xmlStrings->si)) {
                    foreach ($xmlStrings->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string)$si->t;
                        } elseif (isset($si->r)) {
                            $text = '';
                            foreach ($si->r as $rPart) {
                                $text .= (string)($rPart->t ?? '');
                            }
                            $sharedStrings[] = $text;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            }

            // Ambil semua sheet yang ada di workbook (mendukung multi-sheet per guru)
            $sheetEntries = [];
            for ($idx = 0; $idx < $zip->numFiles; $idx++) {
                $stat = $zip->statIndex($idx);
                if ($stat && preg_match('#^xl/worksheets/sheet\d+\.xml$#i', $stat['name'])) {
                    $sheetEntries[] = $stat['name'];
                }
            }
            if (empty($sheetEntries)) {
                $sheetEntries = ['xl/worksheets/sheet1.xml'];
            }

            foreach ($sheetEntries as $entryName) {
                $sheetXml = $zip->getFromName($entryName);
                if (!$sheetXml) continue;

                $xmlSheet = @simplexml_load_string($sheetXml);
                if ($xmlSheet && isset($xmlSheet->sheetData->row)) {
                    foreach ($xmlSheet->sheetData->row as $r) {
                        $cellValues = [];
                        $maxColIndex = 0;
                        foreach ($r->c as $c) {
                            $cellRef = (string)($c['r'] ?? '');
                            $colIdx = 0;
                            if (preg_match('/^([A-Z]+)/', $cellRef, $matches)) {
                                $colStr = $matches[1];
                                $len = strlen($colStr);
                                for ($ci = 0; $ci < $len; $ci++) {
                                    $colIdx = $colIdx * 26 + (ord($colStr[$ci]) - ord('A') + 1);
                                }
                                $colIdx -= 1;
                            }

                            $val = '';
                            $t = (string)($c['t'] ?? '');
                            if ($t === 's' && isset($c->v) && isset($sharedStrings[(int)$c->v])) {
                                $val = $sharedStrings[(int)$c->v];
                            } elseif ($t === 'inlineStr' && isset($c->is->t)) {
                                $val = (string)$c->is->t;
                            } elseif (isset($c->v)) {
                                $val = (string)$c->v;
                            }

                            $cellValues[$colIdx] = trim($val);
                            if ($colIdx > $maxColIndex) {
                                $maxColIndex = $colIdx;
                            }
                        }

                        $rowNormalized = [];
                        for ($ci = 0; $ci <= $maxColIndex; $ci++) {
                            $rowNormalized[$ci] = $cellValues[$ci] ?? '';
                        }

                        if (!empty(array_filter($rowNormalized))) {
                            $rows[] = $rowNormalized;
                        }
                    }
                }
            }
            $zip->close();
        }

        return $rows;
    }

    /**
     * Parsing file CSV, TSV, atau Delimited Text
     */
    private function parseCsvRows($filePath)
    {
        $rows = [];
        if (($handle = @fopen($filePath, 'r')) !== false) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                return [];
            }
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $delimiters = [';', ',', "\t", '|'];
            $bestDelim = ';';
            $maxCount = 0;
            foreach ($delimiters as $d) {
                $count = count(str_getcsv($firstLine, $d));
                if ($count > $maxCount) {
                    $maxCount = $count;
                    $bestDelim = $d;
                }
            }
            while (($data = fgetcsv($handle, 3000, $bestDelim)) !== false) {
                if (!empty(array_filter($data))) {
                    $rows[] = array_map('trim', $data);
                }
            }
            fclose($handle);
        }

        return $rows;
    }

    // Halaman Terpadu: Setting Putaran + Input Jadwal + Tabel Jadwal (Mendukung Filter Jurusan & Kelas)
    public function index(Request $request)
    {
        $setting = Setting::first();
        $teachers = User::whereIn('role', ['guru', 'wali_kelas'])->orderBy('name', 'asc')->get();
        $classes = SchoolClass::orderBy('name', 'asc')->get();
        $rooms = Room::all()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        // Parameter Filter
        $selectedMajor = $request->query('major', 'all');
        $selectedClassId = $request->query('class_id', 'all');
        $selectedCycle = $request->query('cycle', 'all');
        $selectedDay = $request->query('day', 'all');
        $search = trim($request->query('q', ''));

        // Query Dasar Jadwal
        $query = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->orderBy('cycle', 'asc')
            ->orderByRaw("FIELD(day, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')")
            ->orderBy('start_time', 'asc');

        // Filter Jurusan jika dipilih
        if ($selectedMajor && $selectedMajor !== 'all') {
            $query->whereHas('schoolClass', function ($q) use ($selectedMajor) {
                $q->where('name', 'LIKE', "%{$selectedMajor}%");
            });
        }

        // Filter Kelas Spesifik
        if ($selectedClassId && $selectedClassId !== 'all') {
            $query->where('class_id', $selectedClassId);
        }

        // Filter Siklus / Putaran
        if ($selectedCycle && $selectedCycle !== 'all') {
            $query->where('cycle', $selectedCycle);
        }

        // Filter Hari
        if ($selectedDay && $selectedDay !== 'all') {
            $query->where('day', $selectedDay);
        }

        // Pencarian Teks (Nama Mapel atau Guru atau Ruang)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'LIKE', "%{$search}%")
                  ->orWhereHas('teacher', function ($t) use ($search) {
                      $t->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('room', function ($r) use ($search) {
                      $r->where('name', 'LIKE', "%{$search}%")->orWhere('code', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('schoolClass', function ($c) use ($search) {
                      $c->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $schedules = $query->get();

        // Ambil semua jadwal untuk menghitung statistik per Jurusan
        $allSchedules = Schedule::with('schoolClass')->get();
        $totalAllSchedules = $allSchedules->count();
        $totalPutaran1 = $allSchedules->where('cycle', 'putaran_1')->count();
        $totalPutaran2 = $allSchedules->where('cycle', 'putaran_2')->count();

        // Buat daftar Jurusan yang ada di sekolah secara dinamis
        $majors = [];
        foreach ($classes as $c) {
            $mCode = $c->major;
            if (!isset($majors[$mCode])) {
                $majors[$mCode] = [
                    'code'      => $mCode,
                    'name'      => $c->major_full_name,
                    'badge'     => $c->major_badge_color,
                    'count'     => 0,
                    'classes'   => [],
                ];
            }
            $majors[$mCode]['classes'][] = [
                'id'   => $c->id,
                'name' => $c->name,
            ];
        }

        // Urutkan tab jurusan agar konsisten dan rapi sesuai 8 kejuruan SMKN 1 Bantul
        $standardOrder = ['RPL', 'TKJ', 'DKV', 'AK', 'MP', 'BD', 'BR', 'LPS'];
        uksort($majors, function($a, $b) use ($standardOrder) {
            $posA = array_search($a, $standardOrder);
            $posB = array_search($b, $standardOrder);
            if ($posA !== false && $posB !== false) return $posA <=> $posB;
            if ($posA !== false) return -1;
            if ($posB !== false) return 1;
            return strcmp($a, $b);
        });

        // Hitung total jadwal per Jurusan
        foreach ($allSchedules as $s) {
            $sMajor = $s->schoolClass?->major ?? 'UMUM';
            if (isset($majors[$sMajor])) {
                $majors[$sMajor]['count']++;
            }
        }

        return view('waka.schedules', compact(
            'setting',
            'teachers',
            'classes',
            'rooms',
            'schedules',
            'majors',
            'selectedMajor',
            'selectedClassId',
            'selectedCycle',
            'selectedDay',
            'search',
            'totalAllSchedules',
            'totalPutaran1',
            'totalPutaran2'
        ));
    }

    // Simpan Jadwal Baru
    public function store(Request $request)
    {
        $setting = Setting::first();
        if ($setting && $setting->is_schedule_locked) {
            return back()->with('error', 'Gagal! Jadwal sedang di-KUNCI (Lock Schedule aktif). Buka kunci terlebih dahulu.');
        }

        $request->validate([
            'teacher_id' => 'required',
            'class_id'   => 'required',
            'room_id'    => 'required',
            'subject'    => 'required',
            'cycle'      => 'required',
            'day'        => 'required',
            'start_time' => 'required',
            'end_time'   => 'required',
        ]);

        // Algoritma Anti-Tabrakan (Conflict Prevention Sesuai Soal UKK)
        $conflict = Schedule::where('day', $request->day)
            ->where('cycle', $request->cycle)
            ->where(function ($query) use ($request) {
                $query->where('teacher_id', $request->teacher_id)
                      ->orWhere('class_id', $request->class_id)
                      ->orWhere('room_id', $request->room_id);
            })
            ->where('start_time', '<', $request->end_time)
            ->where('end_time', '>', $request->start_time)
            ->first();

        if ($conflict) {
            return back()->with('error', 'Bentrok! Jadwal bertabrakan dengan mapel ' . $conflict->subject . ' (' . $conflict->start_time . ' - ' . $conflict->end_time . ')')->withInput();
        }
        Schedule::create($request->only([
            'teacher_id',
            'class_id',
            'room_id',
            'subject',
            'cycle',
            'day',
            'start_time',
            'end_time',
        ]));

        return back()->with('success', 'Jadwal pelajaran berhasil ditambahkan!');
    }

    // Hapus Jadwal
    public function destroy($id)
    {
        $setting = Setting::first();
        if ($setting && $setting->is_schedule_locked) {
            return back()->with('error', 'Gagal menghapus! Jadwal sedang di-KUNCI (Lock Schedule aktif).');
        }

        $schedule = Schedule::findOrFail($id);

        // Data yang berelasi dilarang dihapus: Cek apakah jadwal memiliki Jurnal KBM atau Presensi Siswa
        $journalCount = \App\Models\Journal::where('schedule_id', $schedule->id)->count();
        if ($journalCount > 0) {
            return back()->with('error', "Gagal menghapus! Jadwal ini tidak dapat dihapus karena sudah memiliki {$journalCount} data riwayat Jurnal KBM / Presensi Siswa terkait.");
        }

        $schedule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus!');
    }

    /**
     * Hapus Seluruh Jadwal untuk Keperluan Simulasi & Reset Pengujian
     * Mendukung penghapusan seluruh putaran sekaligus atau putaran tertentu (Putaran 1 / Putaran 2)
     */
    public function destroyAll(Request $request)
    {
        $targetScope = $request->input('scope', 'all'); // 'all', 'putaran_1', 'putaran_2'

        try {
            $deletedCount = 0;

            \Illuminate\Support\Facades\DB::transaction(function () use ($targetScope, &$deletedCount) {
                \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

                if ($targetScope === 'putaran_1') {
                    $cycleVariants = ['putaran_1', '1', 'Putaran 1', 'PUTARAN 1', 'PUT 1'];
                    $schedIds = Schedule::whereIn('cycle', $cycleVariants)->pluck('id');
                    $deletedCount = $schedIds->count();
                    \App\Models\Journal::whereIn('schedule_id', $schedIds)->delete();
                    Schedule::whereIn('id', $schedIds)->delete();
                } elseif ($targetScope === 'putaran_2') {
                    $cycleVariants = ['putaran_2', '2', 'Putaran 2', 'PUTARAN 2', 'PUT 2'];
                    $schedIds = Schedule::whereIn('cycle', $cycleVariants)->pluck('id');
                    $deletedCount = $schedIds->count();
                    \App\Models\Journal::whereIn('schedule_id', $schedIds)->delete();
                    Schedule::whereIn('id', $schedIds)->delete();
                } else {
                    $deletedCount = Schedule::count();
                    \App\Models\Attendance::truncate();
                    \App\Models\Journal::truncate();
                    \App\Models\Reminder::truncate();
                    Schedule::truncate();
                }

                // Otomatis buka kunci jadwal jika sedang terkunci agar Waka dapat bebas mengimpor jadwal simulasi baru
                $setting = Setting::first();
                if ($setting && $setting->is_schedule_locked) {
                    $setting->update(['is_schedule_locked' => false]);
                }

                \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
            });

            $scopeLabel = ($targetScope === 'putaran_1') ? 'Putaran 1' : (($targetScope === 'putaran_2') ? 'Putaran 2' : 'Semua Putaran (Putaran 1 & Putaran 2)');
            return back()->with('success', "Berhasil! Data jadwal KBM ({$scopeLabel}: {$deletedCount} jadwal) dan catatan simulasi terkait berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan saat menghapus seluruh jadwal: ' . $e->getMessage());
        }
    }
}
