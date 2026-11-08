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

        if ($cycleInput === 'putaran_1' || $cycleInput === 'putaran_2') {
            $targetCycle = $cycleInput;
        } else {
            $origLower = strtolower($originalName);
            if (preg_match('/(putaran\s*2|p[\-_]?2|siklus\s*2|blok\s*2)/i', $origLower)) {
                $targetCycle = 'putaran_2';
            } elseif (preg_match('/(putaran\s*1|p[\-_]?1|siklus\s*1|blok\s*1)/i', $origLower)) {
                $targetCycle = 'putaran_1';
            } else {
                $targetCycle = $activeCycle;
            }
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
            if (!empty($roomParsed['rows'])) {
                $roomLookup = $this->buildRoomLookupFromGrid($roomParsed['rows'], $roomParsed['format']);
            }
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

        $rooms = Room::all()->mapWithKeys(function ($r) {
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

            // 3. Cari ID Ruangan
            $cleanRoom = strtolower(trim($ruangStr));
            $roomId = $rooms->get($cleanRoom) ?? $rooms->get(str_replace(['-', ' '], '', $cleanRoom));

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
     * Membangun kamus pencocokan ruangan dari file Jadwal Ruangan FET (PDF / HTML / XLSX / CSV)
     * Struktur output:
     * - ['by_class'][$day][$time][$normClass] => Room
     * - ['by_teacher'][$day][$time][$cleanTeacher] => Room
     */
    private function buildRoomLookupFromGrid(array $rows, string $formatName = ''): array
    {
        $lookup = ['by_class' => [], 'by_teacher' => []];
        if (empty($rows)) return $lookup;

        $rooms = Room::all();
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
            $clean = trim(preg_replace('/^[0-9]+[\.\s\-]+/', '', $str));
            if (empty($clean) || strlen($clean) < 2) return false;
            if (preg_match('/^(smk\s*negeri|daftar\s*isi|komentar|halaman|jadwal\s*dihasilkan|kembali|nama\s*institusi)/i', $clean)) {
                return false;
            }
            return (bool) preg_match('/^(LAB|RUANG|R\.|R\s*[0-9]|OR\s*[0-9]|BENGKEL|STUDIO|AULA|TEORI)\b/i', $clean);
        };

        $getOrCreateRoom = function($rawName) use (&$rooms, $isRoomPattern) {
            $str = trim((string)$rawName, " \t\n\r\0\x0B'\"");
            $str = preg_replace('/^[0-9]+[\.\s\-]+/', '', $str);
            $clean = trim($str, " \t\n\r\0\x0B'\"");
            if (!$isRoomPattern($clean)) return null;

            $clean = preg_replace('/[^\p{L}\p{N}\s\.\-_]/u', '', $clean);
            $clean = trim(preg_replace('/\s+/', ' ', $clean));
            if (empty($clean) || strlen($clean) < 2 || strlen($clean) > 50) return null;

            $lower = strtolower($clean);
            $code = strtoupper(str_replace([' ', '.'], '-', $clean));

            $found = $rooms->first(function($r) use ($lower, $code) {
                $rName = strtolower(trim($r->name));
                $rCode = strtoupper(trim($r->code));
                return $rName === $lower || $rCode === $code || str_replace(['-', ' '], '', $rName) === str_replace(['-', ' '], '', $lower);
            });
            if ($found) return $found;

            $foundDb = Room::where('code', $code)->orWhere('name', $clean)->first();
            if ($foundDb) {
                $rooms->push($foundDb);
                return $foundDb;
            }

            $newRoom = Room::create([
                'name' => strtoupper($clean),
                'code' => $code,
            ]);
            $rooms->push($newRoom);
            return $newRoom;
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
                            // Cek nama kelas (mendukung format XIIBR1 maupun XII BR 1)
                            if (preg_match('/^(XII|XI|X)[\s\-_]*([A-Za-z]+)\s*([0-9]*)$/i', $line, $m)) {
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
        $findTeacher = function($rawStr) use ($teacherList) {
            $str = trim((string)$rawStr, " \t\n\r\0\x0B'\"");
            // Bersihkan nomor urut FET (misal: "2..ARIS PURWANTINAH, S.Pd." -> "ARIS PURWANTINAH, S.Pd.")
            $clean = preg_replace('/^[0-9]+[\.\s\-]+/', '', $str);
            $clean = trim($clean, " \t\n\r\0\x0B'\"");
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

            // 3. Substring match
            $t = $teacherList->first(function($u) use ($clean) {
                return str_contains(strtolower($u->name), strtolower($clean)) || str_contains(strtolower($clean), strtolower($u->name));
            });

            return $t;
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

            // Pisahkan spasi nomor rombel (misal: "XII BR1" -> "xii br 1")
            if (preg_match('/^(xii|xi|x)\s*([a-z]+)\s*([0-9]*)$/i', $clean, $m)) {
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

            // Jika belum ada dan merupakan nama kelas sah, otomatis buat kelas baru
            if (preg_match('/^(x|xi|xii)\b/i', $clean)) {
                $created = SchoolClass::create(['name' => strtoupper($clean)]);
                $classes->push($created);
                return $created->id;
            }

            return null;
        };

        // Helper cari Ruang
        $findRoom = function($roomStr) use (&$rooms) {
            if (empty($roomStr)) return null;
            $clean = strtolower(trim($roomStr));

            foreach ($rooms as $r) {
                $rName = strtolower(trim($r->name));
                $rCode = strtolower(trim($r->code));
                if ($rName === $clean || $rCode === $clean || str_replace(['-', ' '], '', $rName) === str_replace(['-', ' '], '', $clean)) {
                    return $r->id;
                }
            }

            // Ekstensi nomor Lab (misal: "LAB 6" cocok ke "Lab Komputer 6")
            if (preg_match('/\b(lab|laboratorium)\s*([0-9]+)\b/i', $clean, $m)) {
                $num = $m[2];
                foreach ($rooms as $r) {
                    $rName = strtolower(trim($r->name));
                    if (str_contains($rName, "komputer $num") || str_contains($rName, "lab $num") || str_ends_with($rName, " $num")) {
                        return $r->id;
                    }
                }
            }

            // Ekstensi nomor Ruang (misal: "R. 10" / "R10" cocok ke "Ruang 10")
            if (preg_match('/\b(ruang|r\.?)\s*([0-9]+)\b/i', $clean, $m)) {
                $num = $m[2];
                foreach ($rooms as $r) {
                    $rName = strtolower(trim($r->name));
                    if ($rName === "ruang $num" || $rName === "r $num" || str_ends_with($rName, " $num")) {
                        return $r->id;
                    }
                }
            }

            // Validasi pola nama ruang sebelum membuat ruang baru
            $cleanSanitized = preg_replace('/[^\p{L}\p{N}\s\.\-_]/u', '', (string)$roomStr);
            $cleanSanitized = trim(preg_replace('/\s+/', ' ', $cleanSanitized));
            if (empty($cleanSanitized) || strlen($cleanSanitized) < 2 || strlen($cleanSanitized) > 50) return null;
            if (!preg_match('/^(LAB|RUANG|R\.|R\s*[0-9]|OR\s*[0-9]|BENGKEL|STUDIO|AULA|TEORI)\b/i', $cleanSanitized)) {
                return null;
            }

            // Jika ruang baru belum terdaftar, buat otomatis
            $code = strtoupper(str_replace([' ', '.'], '-', $cleanSanitized));
            $foundDb = Room::where('code', $code)->orWhere('name', $cleanSanitized)->first();
            if ($foundDb) {
                $rooms->push($foundDb);
                return $foundDb->id;
            }

            $newRoom = Room::create([
                'name' => strtoupper($cleanSanitized),
                'code' => $code,
            ]);
            $rooms->push($newRoom);
            return $newRoom->id;
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

                // Cek Kelas (mendukung format XIIBR1 maupun XII BR 1)
                if (!$class && preg_match('/^(XII|XI|X)[\s\-_]*([A-Za-z]+)\s*([0-9]*)$/i', $line, $m)) {
                    $class = trim($m[1] . ' ' . $m[2] . ($m[3] !== '' ? ' ' . $m[3] : ''));
                    continue;
                }

                // Cek Ruang
                if (!$room && preg_match('/^(LAB|LABORATORIUM)\s*[\d\-A-Za-z]+|^(RUANG|R\.)\s*[\d\-A-Za-z]+|^R\s*([0-9]+)$|^OR\s*([0-9]+)$/i', $line)) {
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
            $lines = array_values(array_filter(array_map('trim', preg_split('/[\r\n]+|<br\s*\/?>/i', (string)$cellText))));
            $teacher = null;
            $room = null;
            $subjectParts = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line === '---' || $line === '-') continue;

                // 1. Cek Ruang
                if (!$room && preg_match('/^(LAB|LABORATORIUM)\s*[\d\-A-Za-z]+|^(RUANG|R\.)\s*[\d\-A-Za-z]+|^R\s*([0-9]+)$|^OR\s*([0-9]+)$/i', $line)) {
                    $room = trim($line);
                    continue;
                }

                // 2. Cek Guru
                if (!$teacher) {
                    $t = $findTeacher($line);
                    if ($t) {
                        $teacher = $t;
                        continue;
                    }
                }

                $subjectParts[] = $line;
            }

            return [
                'teacher' => $teacher,
                'room'    => $room,
                'subject' => implode(' ', $subjectParts),
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

                    if (empty($cellVal) || $cellVal === '---' || $cellVal === '-') {
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
                        $teacher = $parsed['teacher'];
                        $subject = $parsed['subject'] ?: 'Mata Pelajaran';
                        $cellRoom = $parsed['room'];
                    }

                    if (!$teacher || !$classId) {
                        continue;
                    }
                    
                    // Resolusi Ruangan:
                    // 1. Cek ruang eksplisit di file/sel (contoh LAB 6, OR1)
                    $roomId = null;
                    if (!empty($cellRoom)) {
                        $roomId = $findRoom($cellRoom);
                    }

                    // 2. Jika belum ada, cari dari kamus file jadwal ruangan (Cross-Matching)
                    if (!$roomId && !empty($roomLookup)) {
                        $cleanClassKey = strtolower(str_replace(' ', '', $className ?? ''));
                        if (!empty($cleanClassKey) && isset($roomLookup['by_class'][$day][$time][$cleanClassKey])) {
                            $roomId = $roomLookup['by_class'][$day][$time][$cleanClassKey]->id;
                        } elseif ($teacher) {
                            $cleanTeacherKey = \App\Http\Controllers\Web\UserController::cleanTeacherNameForMatching($teacher->name);
                            if (isset($roomLookup['by_teacher'][$day][$time][$cleanTeacherKey])) {
                                $roomId = $roomLookup['by_teacher'][$day][$time][$cleanTeacherKey]->id;
                            }
                        }
                    }

                    // 3. Jika tetap belum ada ruangan, fallback otomatis ke Ruang Home Base Kelas
                    if (!$roomId && $classId) {
                        $classModel = $classes->firstWhere('id', $classId);
                        $cName = $classModel ? $classModel->name : ($className ?: 'Umum');

                        $defaultRoom = $rooms->first(function($r) use ($cName) {
                            $rNorm = strtolower(str_replace([' ', '-', '.'], '', $r->name));
                            $cNorm = strtolower(str_replace([' ', '-', '.'], '', $cName));
                            return str_contains($rNorm, $cNorm) || $rNorm === $cNorm;
                        });

                        if (!$defaultRoom) {
                            $cleanCName = preg_replace('/[^\p{L}\p{N}\s\.\-_]/u', '', (string)$cName);
                            $cleanCName = trim(preg_replace('/\s+/', ' ', $cleanCName)) ?: 'UMUM';
                            $fallbackCode = 'R-' . strtoupper(str_replace([' ', '.'], '-', $cleanCName));
                            $defaultRoom = Room::where('code', $fallbackCode)->orWhere('name', 'Ruang ' . strtoupper($cleanCName))->first();
                            if (!$defaultRoom) {
                                $defaultRoom = Room::create([
                                    'name' => 'Ruang ' . strtoupper($cleanCName),
                                    'code' => $fallbackCode,
                                ]);
                            }
                            $rooms->push($defaultRoom);
                        }
                        $roomId = $defaultRoom->id;
                    }

                    if (!$roomId) {
                        continue;
                    }

                    $startTime = $time . ':00';
                    $lastSlot = $time;

                    // Gabungkan slot jam berurutan yang mengajar kelas & mapel yang sama (Blok Pelajaran)
                    while ($i + 1 < $slotCount) {
                        $nextTime = $times[$i + 1];
                        $nextVal = $daySlots[$nextTime] ?? '';

                        // Di format FET/Excel, sel yang di-merge bisa berupa string kosong atau teks yang sama
                        if ($nextVal === '' || $nextVal === $cellVal) {
                            $lastSlot = $nextTime;
                            $i++;
                        } else {
                            break;
                        }
                    }

                    $endTime = ($slotEndsMap[$lastSlot] ?? \Carbon\Carbon::parse($lastSlot)->addMinutes(45)->format('H:i')) . ':00';

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
            'totalAllSchedules'
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
            return back()->with('error', 'Gagal menghapus! Jadwal sedang di-KUNCI.');
        }

        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return back()->with('success', 'Jadwal berhasil dihapus!');
    }
}
