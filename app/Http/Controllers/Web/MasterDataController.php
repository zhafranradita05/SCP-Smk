<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\Room;
use App\Models\User;
use App\Traits\MultiFileParserTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterDataController extends Controller
{
    use MultiFileParserTrait;

    /**
     * Menampilkan daftar kelas & ruangan dengan filter Jurusan, Tingkat, & Wali Kelas
     */
    public function index(Request $request)
    {
        $tingkat     = $request->query('tingkat', 'all');
        $jurusan     = $request->query('jurusan', 'all');
        $waliKelasId = $request->query('wali_kelas_id', 'all');
        $search      = $request->query('search');

        $query = SchoolClass::with(['homeroomTeacher', 'bkTeacher']);

        // 1. Filter Tingkat Kelas (X, XI, XII)
        if ($tingkat && $tingkat !== 'all') {
            $query->where('name', 'like', $tingkat . ' %');
        }

        // 2. Filter Jurusan (RPL, TKJ, DKV, MP)
        if ($jurusan && $jurusan !== 'all') {
            $query->where(function ($q) use ($jurusan) {
                $q->where('name', 'like', '% ' . $jurusan . ' %')
                  ->orWhere('name', 'like', '% ' . $jurusan);
            });
        }

        // 3. Filter Wali Kelas
        if ($waliKelasId && $waliKelasId !== 'all') {
            $query->where('homeroom_teacher_id', $waliKelasId);
        }

        // 4. Pencarian Bebas (Nama Kelas / Nama Wali Kelas / Nama Guru BK)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('homeroomTeacher', function ($qt) use ($search) {
                      $qt->where('name', 'like', "%{$search}%")
                        ->orWhere('identifier', 'like', "%{$search}%");
                  })
                  ->orWhereHas('bkTeacher', function ($qb) use ($search) {
                      $qb->where('name', 'like', "%{$search}%")
                        ->orWhere('identifier', 'like', "%{$search}%");
                  });
            });
        }

        $classes = $query->orderBy('name', 'asc')->get();

        // Ambil ID semua guru yang SUDAH menjadi wali kelas
        $assignedTeacherIds = SchoolClass::whereNotNull('homeroom_teacher_id')
            ->pluck('homeroom_teacher_id')
            ->toArray();

        // Ambil daftar seluruh guru
        $teachers = User::whereIn('role', ['guru', 'wali_kelas'])->orderBy('name', 'asc')->get();
        $bkTeachers = User::whereIn('role', ['guru_bk', 'guru'])->orderBy('name', 'asc')->get();

        // Ambil data ruangan dengan natural sorting agar urut (misal: Ruang 1, 2 ... 10, Lab Komputer 2, 3 ... 10)
        $rooms = Room::all()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        // Daftar jurusan aktif
        $availableJurusan = ['RPL', 'TKJ', 'DKV', 'MP', 'AK', 'LPS', 'BR', 'BD'];

        return view('waka.master', compact(
            'classes', 
            'rooms', 
            'teachers', 
            'bkTeachers',
            'assignedTeacherIds',
            'tingkat', 
            'jurusan', 
            'waliKelasId', 
            'search',
            'availableJurusan'
        ));
    }

    /**
     * Download template import CSV/Excel untuk Wali Kelas dan Ruangan
     */
    public function downloadTemplate(Request $request)
    {
        $type = $request->query('type', 'all');

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($type) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM untuk Excel

            if ($type === 'walikelas') {
                fputcsv($handle, ['Kelas', 'Nama Wali Kelas', 'Nama Guru BK', 'NIP / NUPTK', 'Email', 'No Telepon'], ';');
                fputcsv($handle, ['X RPL 1', 'Budi Santoso, M.Kom', 'RYAWIDYANINGTYAS, S.Pd.', '198201012005011002', 'budi.santoso@sekolah.com', '081234567890'], ';');
                fputcsv($handle, ['X TKJ 1', 'Nurul Fatimah, S.Pd', 'NURHIDAYAT SULISTIAWAN, S.Pd', '198705122010012005', 'nurul.fatimah@sekolah.com', '081234567891'], ';');
                fputcsv($handle, ['XI RPL 2', 'RUSDIYANTO, S.Pd', 'CANDRA RAHADIAN, S.Pd', '57', 'rusdiyanto@sekolah.com', '081234567892'], ';');
            } elseif ($type === 'ruangan') {
                fputcsv($handle, ['Ruangan'], ';');
                fputcsv($handle, ['Lab Komputer 1'], ';');
                fputcsv($handle, ['Lab Komputer 2'], ';');
                fputcsv($handle, ['LAB 19'], ';');
                fputcsv($handle, ['LAB 16'], ';');
                fputcsv($handle, ['Ruang 1'], ';');
                fputcsv($handle, ['Ruang 2'], ';');
            } else {
                // Template Terpadu
                fputcsv($handle, ['Jenis Data', 'Nama Kelas / Ruangan', 'Nama Wali Kelas', 'Nama Guru BK', 'NIP / Identifier', 'Email', 'No Telepon'], ';');
                fputcsv($handle, ['WALI_KELAS', 'X RPL 1', 'Budi Santoso, M.Kom', 'RYAWIDYANINGTYAS, S.Pd.', '198201012005011002', 'budi.santoso@sekolah.com', '081234567890'], ';');
                fputcsv($handle, ['WALI_KELAS', 'X TKJ 1', 'Nurul Fatimah, S.Pd', 'NURHIDAYAT SULISTIAWAN, S.Pd', '198705122010012005', 'nurul.fatimah@sekolah.com', '081234567891'], ';');
                fputcsv($handle, ['RUANGAN', 'Lab Komputer 1', '', '', '', '', ''], ';');
                fputcsv($handle, ['RUANGAN', 'Ruang 1', '', '', '', '', ''], ';');
            }

            fclose($handle);
        };

        $filename = "template_import_" . $type . ".csv";
        $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import Multi-File Master Data (Wali Kelas & Ruangan)
     * Format didukung: PDF, Excel (.xlsx, .xls), CSV, TSV, JSON, TXT
     * Sistem secara cerdas mendeteksi baris kelas & ruangan, memverifikasi, dan menyimpannya.
     */
    public function importMaster(Request $request)
    {
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploadedFiles = is_array($files) ? $files : [$files];
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        }

        if (empty($uploadedFiles)) {
            return back()->with('error', 'Pilih minimal 1 file (PDF, Excel, CSV, JSON, atau Teks) untuk diimport!');
        }

        $totalWaliLinked   = 0;
        $totalWaliCreated  = 0;
        $totalBkLinked     = 0;
        $totalBkCreated    = 0;
        $totalClassCreated = 0;
        $totalRoomsCreated = 0;
        $totalRoomsUpdated = 0;
        $processedFileTypes = [];

        foreach ($uploadedFiles as $file) {
            if (!$file->isValid()) continue;

            $origName = $file->getClientOriginalName();
            $parsed = $this->parseAnyFile($file->getPathname(), $origName);
            $formatName = $parsed['format'];
            $rows = $parsed['rows'];

            if (empty($rows)) continue;

            $processedFileTypes[] = "{$origName} ({$formatName})";

            // 1. Deteksi Baris Judul (Header)
            $colMap = [
                'jenis'      => false,
                'kelas'      => false,
                'wali'       => false,
                'bk'         => false,
                'nip'        => false,
                'email'      => false,
                'phone'      => false,
                'kode_ruang' => false,
                'nama_ruang' => false,
            ];

            $headerRowIndex = -1;
            foreach (array_slice($rows, 0, 10) as $rIdx => $r) {
                // Abaikan baris judul dokumen / kop
                $rowCombined = strtoupper(implode(' ', array_filter($r)));
                if (preg_match('/^(DAFTAR|SEMESTER|TAHUN AJARAN|REKAP|LAPORAN)\b/i', trim($rowCombined))) {
                    continue;
                }

                $matchedIndices = [];
                $tempColMap = [
                    'jenis'      => false,
                    'kelas'      => false,
                    'wali'       => false,
                    'bk'         => false,
                    'nip'        => false,
                    'email'      => false,
                    'phone'      => false,
                    'kode_ruang' => false,
                    'nama_ruang' => false,
                ];

                foreach ($r as $cIdx => $val) {
                    $cLower = strtolower(trim((string)$val));
                    $cClean = preg_replace('/[^a-z0-9_]/', ' ', $cLower);
                    $cClean = preg_replace('/\s+/', ' ', trim($cClean));

                    if (empty($cClean)) continue;

                    if ($tempColMap['jenis'] === false && preg_match('/^(jenis|tipe|kategori|type|data)$/i', $cClean)) {
                        $tempColMap['jenis'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['bk'] === false && preg_match('/\b(guru bk|bk|bimbingan konseling|nama bk|nama guru bk)\b/i', $cClean)) {
                        $tempColMap['bk'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['wali'] === false && preg_match('/\b(wali kelas|wali|nama wali|nama wali kelas|homeroom)\b/i', $cClean)) {
                        $tempColMap['wali'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['kelas'] === false && preg_match('/^(kelas|rombel|nama kelas|tingkat|class)$/i', $cClean)) {
                        $tempColMap['kelas'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['nip'] === false && preg_match('/\b(nip|nuptk|identifier|id guru|kode guru)\b/i', $cClean)) {
                        $tempColMap['nip'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['email'] === false && preg_match('/\b(email|surel|mail)\b/i', $cClean)) {
                        $tempColMap['email'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['phone'] === false && preg_match('/\b(phone|telepon|telp|hp|no hp|wa)\b/i', $cClean)) {
                        $tempColMap['phone'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['kode_ruang'] === false && preg_match('/\b(kode ruang|kode|room code|code)\b/i', $cClean)) {
                        $tempColMap['kode_ruang'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    } elseif ($tempColMap['nama_ruang'] === false && preg_match('/\b(nama ruang|nama ruangan|ruang|ruangan|lab|room|room name)\b/i', $cClean)) {
                        $tempColMap['nama_ruang'] = $cIdx;
                        $matchedIndices[$cIdx] = true;
                    }
                }

                if (count($matchedIndices) >= 2 || (count($matchedIndices) >= 1 && ($tempColMap['nama_ruang'] !== false || $tempColMap['kelas'] !== false))) {
                    $headerRowIndex = $rIdx;
                    $colMap = $tempColMap;
                    break;
                }
            }

            // 2. Loop Setiap Baris Data
            $dataRows = ($headerRowIndex !== -1) ? array_slice($rows, $headerRowIndex + 1) : $rows;

            foreach ($dataRows as $row) {
                if (empty($row) || empty(array_filter($row))) continue;

                $jenis = '';
                if ($colMap['jenis'] !== false && isset($row[$colMap['jenis']])) {
                    $jenis = strtoupper(trim((string)$row[$colMap['jenis']]));
                }

                // Ambil nilai kolom jika header terdeteksi
                $kelasVal = ($colMap['kelas'] !== false && isset($row[$colMap['kelas']])) ? trim((string)$row[$colMap['kelas']]) : '';
                $waliVal  = ($colMap['wali'] !== false && isset($row[$colMap['wali']])) ? trim((string)$row[$colMap['wali']]) : '';
                $bkVal    = ($colMap['bk'] !== false && isset($row[$colMap['bk']])) ? trim((string)$row[$colMap['bk']]) : '';
                $nipVal   = ($colMap['nip'] !== false && isset($row[$colMap['nip']])) ? trim((string)$row[$colMap['nip']]) : '';
                $emailVal = ($colMap['email'] !== false && isset($row[$colMap['email']])) ? trim((string)$row[$colMap['email']]) : '';
                $phoneVal = ($colMap['phone'] !== false && isset($row[$colMap['phone']])) ? trim((string)$row[$colMap['phone']]) : '';

                $kodeRuangVal = ($colMap['kode_ruang'] !== false && isset($row[$colMap['kode_ruang']])) ? trim((string)$row[$colMap['kode_ruang']]) : '';
                $namaRuangVal = ($colMap['nama_ruang'] !== false && isset($row[$colMap['nama_ruang']])) ? trim((string)$row[$colMap['nama_ruang']]) : '';

                // Validasi nama kelas: harus diawali X, XI, atau XII
                if (!empty($kelasVal) && !preg_match('/^(X|XI|XII)\b/i', $kelasVal)) {
                    // Bukan nama kelas valid (mungkin nomor urut atau header terlewat)
                    $kelasVal = '';
                }

                // Heuristik Fallback jika header tidak terpetakan dengan lengkap:
                if ($colMap['kelas'] === false && $colMap['nama_ruang'] === false) {
                    // Deteksi dari isi cell baris
                    foreach ($row as $ci => $cVal) {
                        $cVal = trim((string)$cVal);
                        if (preg_match('/\b(X|XI|XII)\s+[A-Za-z0-9\-]+/i', $cVal)) {
                            $kelasVal = $cVal;
                            // Cell teks berikutnya kemungkinan nama wali
                            if (isset($row[$ci + 1])) $waliVal = trim((string)$row[$ci + 1]);
                            if (isset($row[$ci + 2])) $nipVal = trim((string)$row[$ci + 2]);
                            break;
                        } elseif (preg_match('/\b(LAB|LABORATORIUM|RUANG|R)\s*[\d\-A-Za-z]+/i', $cVal)) {
                            $namaRuangVal = $cVal;
                            if (isset($row[$ci + 1]) && strlen(trim((string)$row[$ci + 1])) <= 12) {
                                $kodeRuangVal = trim((string)$row[$ci + 1]);
                            }
                            break;
                        }
                    }
                }

                // Deteksi Kategori: Wali Kelas vs Ruangan
                $isWaliKelas = false;
                $isRuangan = false;

                if (!empty($jenis)) {
                    if (str_contains($jenis, 'RUANG') || str_contains($jenis, 'ROOM')) {
                        $isRuangan = true;
                        // Pada template gabungan, kolom 1 adalah Nama Kelas / Ruangan
                        if (empty($namaRuangVal) && !empty($kelasVal)) $namaRuangVal = $kelasVal;
                        if (empty($namaRuangVal) && !empty($waliVal)) $namaRuangVal = $waliVal;
                    } elseif (str_contains($jenis, 'WALI') || str_contains($jenis, 'KELAS') || str_contains($jenis, 'GURU')) {
                        $isWaliKelas = true;
                    }
                } else {
                    // Jika tanpa kolom jenis: deteksi berdasarkan ada tidaknya nilai kolom
                    if (!empty($kelasVal)) {
                        $isWaliKelas = true;
                    }
                    if (!empty($namaRuangVal) || !empty($kodeRuangVal)) {
                        $isRuangan = true;
                    }
                }

                // PROSES 1: DATA WALI KELAS
                if ($isWaliKelas && !empty($kelasVal)) {
                    // Normalisasi nama kelas (misal: "xii rpl 2" -> "XII RPL 2")
                    $className = strtoupper(preg_replace('/\s+/', ' ', trim($kelasVal)));

                    // Cek atau buat kelas
                    $class = SchoolClass::where('name', $className)->first();
                    if (!$class) {
                        $class = SchoolClass::create([
                            'name'                => $className,
                            'homeroom_teacher_id' => null,
                        ]);
                        $totalClassCreated++;
                    }

                    // Jika ada data nama wali kelas
                    if (!empty($waliVal)) {
                        $cleanTeacherName = trim(preg_replace('/^[0-9]+[\.\s]+/', '', $waliVal));

                        // Tolak jika nama adalah ruangan, lab, fasilitas, atau header rekapan
                        $isRoomOrFacility = preg_match('/^(LAB|LABORATORIUM|RUANG|BENGKEL|PERPUSTAKAAN|AULA|KANTIN|GUDANG|LAPANGAN|R\.\s*[0-9]|R\s*[0-9])\b/i', $cleanTeacherName);
                        $isHeaderOrSummary = preg_match('/^(TOTAL|JUMLAH|KEPALA SEKOLAH|MENGETAHUI|DAFTAR|REKAPITULASI|TANGGAL|NAMA WALI|WALI KELAS|WALI|NO|TTD|DITETAPKAN|PADA TANGGAL|BR|MP|BD|AK|TKJ|RPL|DKV)\b/i', strtoupper($cleanTeacherName));

                        if (!$isRoomOrFacility && !$isHeaderOrSummary) {
                            // 1. Cari user yang sudah ada
                            $teacher = null;
                            if (!empty($nipVal)) {
                                $teacher = User::where('identifier', $nipVal)->first();
                            }
                            if (!$teacher && !empty($emailVal)) {
                                $teacher = User::where('email', $emailVal)->first();
                            }
                            if (!$teacher) {
                                $teacher = User::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($cleanTeacherName))])->first();
                            }
                            if (!$teacher) {
                                $cleanTarget = UserController::cleanTeacherNameForMatching($cleanTeacherName);
                                if (strlen($cleanTarget) >= 4) {
                                    foreach (User::whereIn('role', ['guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum'])->get() as $u) {
                                        if (UserController::cleanTeacherNameForMatching($u->name) === $cleanTarget) {
                                            $teacher = $u;
                                            break;
                                        }
                                    }
                                }
                            }
                            if (!$teacher) {
                                $teacher = User::where('name', 'like', "%{$cleanTeacherName}%")->first();
                            }

                            if ($teacher) {
                                // Update peran menjadi wali_kelas jika sebelumnya guru
                                if ($teacher->role === 'guru') {
                                    $teacher->update(['role' => 'wali_kelas']);
                                }
                                if (!empty($nipVal) && empty($teacher->identifier)) {
                                    $teacher->update(['identifier' => $nipVal]);
                                }
                                if (!empty($phoneVal) && empty($teacher->phone)) {
                                    $teacher->update(['phone' => $phoneVal]);
                                }

                                // Pasangkan ke kelas
                                $class->update(['homeroom_teacher_id' => $teacher->id]);
                                $totalWaliLinked++;
                            } else {
                                // Buat akun guru/wali_kelas baru dengan username 1 suku kata
                                $username = UserController::generateSingleWordUsername($cleanTeacherName);

                                $userEmail = !empty($emailVal) ? $emailVal : ($username . '@sekolah.com');
                                $eCounter = 1;
                                while (User::where('email', $userEmail)->exists()) {
                                    $userEmail = str_replace('@', "_{$eCounter}@", !empty($emailVal) ? $emailVal : ($username . '@sekolah.com'));
                                    $eCounter++;
                                }

                                $newTeacher = User::create([
                                    'name'       => $cleanTeacherName,
                                    'username'   => $username,
                                    'email'      => $userEmail,
                                    'password'   => Hash::make('password123'),
                                    'role'       => 'wali_kelas',
                                    'status'     => 'aktif',
                                    'identifier' => $nipVal ?: null,
                                    'phone'      => $phoneVal ?: null,
                                ]);

                                $class->update(['homeroom_teacher_id' => $newTeacher->id]);
                                $totalWaliCreated++;
                            }
                        }
                    }

                    // Jika ada data Guru BK yang ditugaskan pada kelas ini
                    if (!empty($bkVal)) {
                        $cleanBkName = trim(preg_replace('/^[0-9]+[\.\s]+/', '', $bkVal));

                        $isBkRoomOrFacility = preg_match('/^(LAB|LABORATORIUM|RUANG|BENGKEL|PERPUSTAKAAN|AULA|KANTIN|GUDANG|LAPANGAN|R\.\s*[0-9]|R\s*[0-9])\b/i', $cleanBkName);
                        $isBkHeaderOrSummary = preg_match('/^(TOTAL|JUMLAH|KEPALA SEKOLAH|MENGETAHUI|DAFTAR|REKAPITULASI|TANGGAL|NAMA GURU BK|BK|NO|TTD|DITETAPKAN|PADA TANGGAL|BR|MP|BD|AK|TKJ|RPL|DKV)\b/i', strtoupper($cleanBkName));

                        if (!$isBkRoomOrFacility && !$isBkHeaderOrSummary) {
                            // 1. Cari user Guru BK yang sudah ada
                            $bkTeacher = User::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($cleanBkName))])->first();
                            if (!$bkTeacher) {
                                $bkTeacher = User::where('name', 'like', "%{$cleanBkName}%")->first();
                            }
                            if (!$bkTeacher) {
                                $cleanTarget = UserController::cleanTeacherNameForMatching($cleanBkName);
                                foreach (User::all() as $u) {
                                    if (UserController::cleanTeacherNameForMatching($u->name) === $cleanTarget) {
                                        $bkTeacher = $u;
                                        break;
                                    }
                                }
                            }

                            if ($bkTeacher) {
                                if ($bkTeacher->role === 'guru') {
                                    $bkTeacher->update(['role' => 'guru_bk']);
                                }
                                $class->update(['bk_teacher_id' => $bkTeacher->id]);
                                $totalBkLinked++;
                            } else {
                                $bkUsername = UserController::generateSingleWordUsername($cleanBkName);
                                $bkEmail = $bkUsername . '@sekolah.com';
                                $eCounter = 1;
                                while (User::where('email', $bkEmail)->exists()) {
                                    $bkEmail = $bkUsername . $eCounter . '@sekolah.com';
                                    $eCounter++;
                                }

                                $newBk = User::create([
                                    'name'       => $cleanBkName,
                                    'username'   => $bkUsername,
                                    'email'      => $bkEmail,
                                    'password'   => Hash::make('password123'),
                                    'role'       => 'guru_bk',
                                    'status'     => 'aktif',
                                ]);

                                $class->update(['bk_teacher_id' => $newBk->id]);
                                $totalBkCreated++;
                            }
                        }
                    }
                }

                // PROSES 2: DATA RUANGAN
                if ($isRuangan && (!empty($namaRuangVal) || !empty($kodeRuangVal))) {
                    $roomName = !empty($namaRuangVal) ? trim($namaRuangVal) : trim($kodeRuangVal);
                    $roomCode = !empty($kodeRuangVal) ? strtoupper(trim($kodeRuangVal)) : '';

                    if (empty($roomCode)) {
                        // Generate kode otomatis dari nama (misal: "LAB 19" -> "LAB-19", "R10" -> "R-10", "Ruang 12" -> "R-12")
                        if (preg_match('/^(LAB|RUANG|R)\s*(.+)$/i', $roomName, $m)) {
                            $prefix = strtoupper($m[1]);
                            if ($prefix === 'RUANG') $prefix = 'R';
                            $suffix = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($m[2])));
                            $roomCode = $prefix . '-' . trim($suffix, '-');
                        } else {
                            $roomCode = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', $roomName));
                            $roomCode = trim($roomCode, '-');
                        }
                    }

                    // Cari apakah ruangan sudah ada berdasarkan nama atau kode
                    $room = Room::where('name', $roomName)->orWhere('code', $roomCode)->first();
                    if ($room) {
                        $room->update([
                            'name' => $roomName,
                            'code' => $room->code ?: $roomCode,
                        ]);
                        $totalRoomsUpdated++;
                    } else {
                        // Pastikan roomCode unik
                        $baseCode = $roomCode;
                        $c = 1;
                        while (Room::where('code', $roomCode)->exists()) {
                            $roomCode = $baseCode . '-' . $c;
                            $c++;
                        }

                        Room::create([
                            'name' => $roomName,
                            'code' => $roomCode,
                        ]);
                        $totalRoomsCreated++;
                    }
                }
            }
        }

        $filesDesc = implode(', ', $processedFileTypes);
        $summaryParts = [];
        if ($totalWaliLinked > 0) $summaryParts[] = "{$totalWaliLinked} wali kelas berhasil ditugaskan";
        if ($totalWaliCreated > 0) $summaryParts[] = "{$totalWaliCreated} akun wali kelas baru dibuat";
        if ($totalBkLinked > 0) $summaryParts[] = "{$totalBkLinked} kelas terhubung ke Guru BK";
        if ($totalBkCreated > 0) $summaryParts[] = "{$totalBkCreated} akun Guru BK baru dibuat";
        if ($totalClassCreated > 0) $summaryParts[] = "{$totalClassCreated} kelas baru didaftarkan";
        if ($totalRoomsCreated > 0) $summaryParts[] = "{$totalRoomsCreated} ruangan baru ditambahkan";
        if ($totalRoomsUpdated > 0) $summaryParts[] = "{$totalRoomsUpdated} data ruangan diperbarui";

        if (empty($summaryParts)) {
            return back()->with('warning', "File ({$filesDesc}) berhasil dibaca, namun sistem tidak menemukan baris data Kelas/Wali Kelas atau Ruangan yang valid.");
        }

        $msg = "Multi-Import Berhasil! (" . implode(' • ', $summaryParts) . ") dari file: {$filesDesc}.";
        return back()->with('success', $msg);
    }

    /**
     * Menyimpan data kelas baru (Aturan: 1 Guru = 1 Kelas)
     */
    public function storeClass(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:50|unique:classes,name',
            'homeroom_teacher_id' => 'nullable|exists:users,id|unique:classes,homeroom_teacher_id',
        ], [
            'name.unique'                => 'Nama kelas ini sudah terdaftar!',
            'homeroom_teacher_id.unique' => 'Guru ini sudah menjadi wali kelas di kelas lain! 1 guru hanya dapat membina 1 kelas.',
        ]);

        $class = SchoolClass::create([
            'name'                => strtoupper(trim($request->name)),
            'homeroom_teacher_id' => $request->homeroom_teacher_id ?: null,
        ]);

        if ($request->homeroom_teacher_id) {
            $teacher = User::find($request->homeroom_teacher_id);
            if ($teacher && $teacher->role === 'guru') {
                $teacher->update(['role' => 'wali_kelas']);
            }
        }

        return redirect()->back()->with('success', 'Kelas ' . $request->name . ' berhasil ditambahkan!');
    }

    /**
     * Memperbarui Wali Kelas / Nama Kelas yang sudah ada
     */
    public function updateClass(Request $request, SchoolClass $class)
    {
        $request->validate([
            'name'                => 'required|string|max:50|unique:classes,name,' . $class->id,
            'homeroom_teacher_id' => 'nullable|exists:users,id|unique:classes,homeroom_teacher_id,' . $class->id,
            'bk_teacher_id'       => 'nullable|exists:users,id',
        ], [
            'name.unique'                => 'Nama kelas ini sudah terdaftar!',
            'homeroom_teacher_id.unique' => 'Guru ini sudah menjadi wali kelas di kelas lain! 1 guru hanya dapat membina 1 kelas.',
        ]);

        $oldTeacherId = $class->homeroom_teacher_id;

        $class->update([
            'name'                => strtoupper(trim($request->name)),
            'homeroom_teacher_id' => $request->homeroom_teacher_id ?: null,
            'bk_teacher_id'       => $request->bk_teacher_id ?: null,
        ]);

        if ($oldTeacherId && $oldTeacherId != $request->homeroom_teacher_id) {
            $hasOtherClass = SchoolClass::where('homeroom_teacher_id', $oldTeacherId)->exists();
            if (!$hasOtherClass) {
                $oldTeacher = User::find($oldTeacherId);
                if ($oldTeacher && $oldTeacher->role === 'wali_kelas') {
                    $oldTeacher->update(['role' => 'guru']);
                }
            }
        }

        if ($request->homeroom_teacher_id) {
            $newTeacher = User::find($request->homeroom_teacher_id);
            if ($newTeacher && in_array($newTeacher->role, ['guru', 'wali_kelas'])) {
                $newTeacher->update(['role' => 'wali_kelas']);
            }
        }

        if ($request->bk_teacher_id) {
            $newBk = User::find($request->bk_teacher_id);
            if ($newBk && in_array($newBk->role, ['guru', 'guru_bk'])) {
                $newBk->update(['role' => 'guru_bk']);
            }
        }

        return redirect()->back()->with('success', 'Data kelas ' . $class->name . ' (Wali Kelas & Guru BK) berhasil diperbarui!');
    }

    /**
     * Menyimpan data ruangan baru dari modal web
     */
    public function storeRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:20|unique:rooms,code',
        ]);

        $roomName = trim($request->name);
        $roomCode = !empty($request->code) ? strtoupper(trim($request->code)) : null;

        if (empty($roomCode)) {
            if (preg_match('/^(LAB|RUANG|R)\s*(.+)$/i', $roomName, $m)) {
                $prefix = strtoupper($m[1]);
                if ($prefix === 'RUANG') $prefix = 'R';
                $suffix = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($m[2])));
                $roomCode = $prefix . '-' . trim($suffix, '-');
            } else {
                $roomCode = strtoupper(preg_replace('/[^a-zA-Z0-9]+/', '-', $roomName));
                $roomCode = trim($roomCode, '-');
            }

            // Pastikan unik
            $baseCode = $roomCode;
            $c = 1;
            while (Room::where('code', $roomCode)->exists()) {
                $roomCode = $baseCode . '-' . $c;
                $c++;
            }
        }

        Room::create([
            'name' => $roomName,
            'code' => $roomCode,
        ]);

        return redirect()->back()->with('success', 'Ruangan ' . $roomName . ' berhasil ditambahkan!');
    }
}
