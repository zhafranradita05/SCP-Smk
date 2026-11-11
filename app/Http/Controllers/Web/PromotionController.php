<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PromotionController extends Controller
{
    /**
     * Menampilkan Halaman Kenaikan Kelas & Kelulusan Massal Siswa Seluruh Sekolah
     */
    public function index(Request $request)
    {
        $hasStatusCol = Schema::hasColumn('users', 'status');

        // Ambil semua daftar 48 kelas beserta jumlah siswa aktifnya
        $classes = SchoolClass::orderBy('name', 'asc')
            ->withCount(['students' => function ($q) use ($hasStatusCol) {
                if ($hasStatusCol) {
                    $q->where(function ($sq) {
                        $sq->where('status', 'aktif')->orWhereNull('status');
                    });
                }
            }])
            ->get();

        // Statistik Cepat Siswa
        $totalSiswaAktif = User::where('role', 'siswa');
        if ($hasStatusCol) {
            $totalSiswaAktif->where(function ($q) {
                $q->where('status', 'aktif')->orWhereNull('status');
            });
        }
        $totalSiswaAktifCount = $totalSiswaAktif->count();

        $totalAlumniCount = $hasStatusCol 
            ? User::where('role', 'siswa')->where('status', 'lulus')->count() 
            : 0;

        // Ambil daftar alumni terbaru untuk riwayat / pemulihan status
        $recentAlumni = collect();
        if ($hasStatusCol) {
            $recentAlumni = User::where('role', 'siswa')
                ->where('status', 'lulus')
                ->orderBy('updated_at', 'desc')
                ->take(15)
                ->get();
        }

        // Data seluruh siswa aktif untuk daftar pengecualian (Tinggal Kelas)
        $activeStudentsQuery = User::where('role', 'siswa')
            ->whereNotNull('class_id')
            ->with('schoolClass');
        if ($hasStatusCol) {
            $activeStudentsQuery->where(function ($q) {
                $q->where('status', 'aktif')->orWhereNull('status');
            });
        }
        $allActiveStudents = $activeStudentsQuery->orderBy('name', 'asc')->get();

        // Hitung perkiraan per tingkat & Pratinjau Alur Otomatis Seluruh Rombel
        $countX = 0;
        $countXI = 0;
        $countXII = 0;
        $bulkMappingPreview = [];

        foreach ($classes as $c) {
            $cName = trim($c->name);
            $studentCount = $c->students_count;
            if (preg_match('/^(XII|12)\b/i', $cName)) {
                $countXII += $studentCount;
                $bulkMappingPreview[] = [
                    'source' => $cName,
                    'target' => '🎓 Lulus (Status Alumni)',
                    'count'  => $studentCount,
                    'status' => 'graduate'
                ];
            } else {
                $targetClass = $this->resolveTargetClass($c, $classes);
                if (preg_match('/^(XI|11)\b/i', $cName)) {
                    $countXI += $studentCount;
                } else {
                    $countX += $studentCount;
                }
                $bulkMappingPreview[] = [
                    'source' => $cName,
                    'target' => $targetClass ? $targetClass->name : '⚠️ Rombel Tujuan Belum Ada',
                    'count'  => $studentCount,
                    'status' => $targetClass ? 'promote' : 'unmapped'
                ];
            }
        }

        return view('waka.promotions.index', compact(
            'classes',
            'totalSiswaAktifCount',
            'totalAlumniCount',
            'recentAlumni',
            'hasStatusCol',
            'allActiveStudents',
            'countX',
            'countXI',
            'countXII',
            'bulkMappingPreview'
        ));
    }

    /**
     * Memproses Kenaikan Massal Seluruh Sekolah Sekali Klik
     * - Siswa Kelas XII diluluskan menjadi Alumni
     * - Siswa Kelas XI naik ke Kelas XII
     * - Siswa Kelas X naik ke Kelas XI
     * - Siswa Tinggal Kelas: TETAP di JURUSAN YANG SAMA, dialokasikan ke rombel dengan kursi kosong (< 36 siswa)
     */
    public function bulkProcess(Request $request)
    {
        $hasStatusCol = Schema::hasColumn('users', 'status');

        // Daftar ID siswa yang TINGGAL KELAS (dikecualikan dari kenaikan)
        $stayedStudentIds = $request->input('stayed_student_ids', []);
        if (!is_array($stayedStudentIds)) {
            $stayedStudentIds = [];
        }

        $allClasses = SchoolClass::all();

        // Pisahkan kelas berdasarkan tingkatan
        $grade12Classes = [];
        $grade11Classes = [];
        $grade10Classes = [];

        foreach ($allClasses as $c) {
            $name = trim($c->name);
            if (preg_match('/^(XII|12)\b/i', $name)) {
                $grade12Classes[] = $c;
            } elseif (preg_match('/^(XI|11)\b/i', $name)) {
                $grade11Classes[] = $c;
            } elseif (preg_match('/^(X|10)\b/i', $name)) {
                $grade10Classes[] = $c;
            }
        }

        $graduatedCount = 0;
        $promoted11to12Count = 0;
        $promoted10to11Count = 0;
        $unmappedClasses = [];
        $stayedReassignments = [];
        $currentCounts = [];

        DB::transaction(function () use (
            $grade12Classes, $grade11Classes, $grade10Classes, $allClasses,
            $stayedStudentIds, $hasStatusCol,
            &$graduatedCount, &$promoted11to12Count, &$promoted10to11Count, &$unmappedClasses,
            &$stayedReassignments, &$currentCounts
        ) {
            // TAHAP 1: Luluskan Siswa Kelas XII (Dieksekusi pertama agar tidak tertimpa perpindahan siswa XI)
            foreach ($grade12Classes as $c12) {
                $query = User::where('role', 'siswa')
                    ->where('class_id', $c12->id);

                if (!empty($stayedStudentIds)) {
                    $query->whereNotIn('id', $stayedStudentIds);
                }

                if ($hasStatusCol) {
                    $query->where(function ($q) {
                        $q->where('status', 'aktif')->orWhereNull('status');
                    });
                }

                $updateData = ['class_id' => null];
                if ($hasStatusCol) {
                    $updateData['status'] = 'lulus';
                }

                $graduatedCount += $query->update($updateData);
            }

            // TAHAP 2: Naikkan Siswa Kelas XI ke Kelas XII
            foreach ($grade11Classes as $c11) {
                $targetClass = $this->resolveTargetClass($c11, $allClasses);

                if ($targetClass) {
                    $query = User::where('role', 'siswa')
                        ->where('class_id', $c11->id);

                    if (!empty($stayedStudentIds)) {
                        $query->whereNotIn('id', $stayedStudentIds);
                    }

                    if ($hasStatusCol) {
                        $query->where(function ($q) {
                            $q->where('status', 'aktif')->orWhereNull('status');
                        });
                    }

                    $updateData = ['class_id' => $targetClass->id];
                    if ($hasStatusCol) {
                        $updateData['status'] = 'aktif';
                    }

                    $promoted11to12Count += $query->update($updateData);
                } else {
                    $unmappedClasses[] = $c11->name;
                }
            }

            // TAHAP 3: Naikkan Siswa Kelas X ke Kelas XI
            foreach ($grade10Classes as $c10) {
                $targetClass = $this->resolveTargetClass($c10, $allClasses);

                if ($targetClass) {
                    $query = User::where('role', 'siswa')
                        ->where('class_id', $c10->id);

                    if (!empty($stayedStudentIds)) {
                        $query->whereNotIn('id', $stayedStudentIds);
                    }

                    if ($hasStatusCol) {
                        $query->where(function ($q) {
                            $q->where('status', 'aktif')->orWhereNull('status');
                        });
                    }

                    $updateData = ['class_id' => $targetClass->id];
                    if ($hasStatusCol) {
                        $updateData['status'] = 'aktif';
                    }

                    $promoted10to11Count += $query->update($updateData);
                } else {
                    $unmappedClasses[] = $c10->name;
                }
            }

            // TAHAP 4: Alokasi Siswa Tinggal Kelas
            // Aturan: Tetap pada Jurusan yang sama, dialokasikan ke rombel yang memiliki kursi kosong (< 36 siswa)
            if (!empty($stayedStudentIds)) {
                $stayedStudents = User::whereIn('id', $stayedStudentIds)->with('schoolClass')->get();
                foreach ($stayedStudents as $std) {
                    $targetClassStayed = $this->findAvailableClassForStayedStudent($std, $allClasses, $hasStatusCol, $currentCounts);
                    if ($targetClassStayed) {
                        $oldClassName = $std->schoolClass ? $std->schoolClass->name : '-';
                        if ($targetClassStayed->id !== $std->class_id) {
                            $std->update(['class_id' => $targetClassStayed->id]);
                            $stayedReassignments[] = "{$std->name} ({$oldClassName} &rarr; {$targetClassStayed->name})";
                        } else {
                            $stayedReassignments[] = "{$std->name} (tetap di {$targetClassStayed->name})";
                        }
                    }
                }
            }
        });

        $totalSuccess = $graduatedCount + $promoted11to12Count + $promoted10to11Count;
        $stayedCount = count($stayedStudentIds);

        $msg = "Kenaikan Massal Berhasil! Sebanyak {$totalSuccess} siswa berhasil diproses serentak: "
            . "{$graduatedCount} siswa Kelas XII LULUS, "
            . "{$promoted11to12Count} siswa Kelas XI naik ke XII, dan "
            . "{$promoted10to11Count} siswa Kelas X naik ke XI.";

        if ($stayedCount > 0) {
            $msg .= " ({$stayedCount} siswa tinggal kelas tetap di jurusan yang sama dan dialokasikan ke rombel dengan kursi kosong maks 36 siswa: " . implode(', ', $stayedReassignments) . ").";
        }

        if (!empty($unmappedClasses)) {
            $msg .= " Catatan: Kelas " . implode(', ', array_unique($unmappedClasses)) . " dilewati karena rombel tujuan jenjang berikutnya belum dibuat di Master Data.";
        }

        return redirect()->route('waka.promotions.index')->with('success', $msg);
    }

    /**
     * Membatalkan Status Kelulusan (Mengembalikan Alumni ke Kelas Aktif)
     */
    public function restoreAlumni(Request $request, $id)
    {
        $hasStatusCol = Schema::hasColumn('users', 'status');

        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $student = User::where('role', 'siswa')->findOrFail($id);
        $targetClass = SchoolClass::findOrFail($request->class_id);

        $updateData = ['class_id' => $targetClass->id];
        if ($hasStatusCol) {
            $updateData['status'] = 'aktif';
        }

        $student->update($updateData);

        return back()->with('success', "Status alumni '{$student->name}' berhasil dibatalkan dan dimasukkan kembali ke kelas {$targetClass->name}.");
    }

    /**
     * Ekspor Data Siswa Lulus / Alumni ke Format CSV (Bisa dibuka langsung di Excel)
     */
    public function exportAlumni()
    {
        $hasStatusCol = Schema::hasColumn('users', 'status');

        $query = User::where('role', 'siswa');
        if ($hasStatusCol) {
            $query->where('status', 'lulus');
        } else {
            $query->whereNull('class_id');
        }

        $alumni = $query->with('schoolClass')->orderBy('name', 'asc')->get();

        $filename = 'Arsip_Alumni_Siswa_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($alumni) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No',
                'NISN / Identifier',
                'Nama Lengkap',
                'Username',
                'Email',
                'No. Telepon',
                'Status',
                'Tanggal Kelulusan / Update Terakhir'
            ]);

            $no = 1;
            foreach ($alumni as $student) {
                fputcsv($file, [
                    $no++,
                    $student->identifier ?? '-',
                    $student->name,
                    $student->username,
                    $student->email ?? '-',
                    $student->phone ?? '-',
                    strtoupper($student->status ?? 'LULUS'),
                    $student->updated_at ? $student->updated_at->format('d/m/Y H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper resolusi kelas tujuan kenaikan kelas:
     * - X RPL 1 -> XI RPL 1
     * - XI RPL 1 -> XII RPL 1
     * - Mendukung resolusi kanonikal 48 kelas resmi SMKN 1 Bantul
     */
    protected function resolveTargetClass($sourceClass, $allClasses)
    {
        $name = trim($sourceClass->name);
        if (preg_match('/^(XII|12)\b/i', $name)) {
            return null; // Siswa tingkat 12 lulus
        }

        $targetGrade = preg_match('/^(X|10)\b/i', $name) ? 'XI' : 'XII';
        $major = $sourceClass->major;

        // 1. Coba pola target kanonikal resmi (misal XI RPL 1 -> XII RPL 1)
        $targetPattern = preg_replace('/^(X|XI|10|11)\b/i', $targetGrade, $name);
        $canonicalTarget = SchoolClass::canonicalName($targetPattern);
        if ($canonicalTarget) {
            $found = $allClasses->first(fn($c) => $c->name === $canonicalTarget);
            if ($found) return $found;
        }

        // 2. Coba pencocokan string tanpa spasi
        $targetNorm = str_replace(' ', '', strtolower($targetPattern));
        $found = $allClasses->first(fn($c) => str_replace(' ', '', strtolower($c->name)) === $targetNorm);
        if ($found) return $found;

        // 3. Fallback jika rombel tunggal pada jenjang tersebut (seperti XII LPS atau XII BD)
        $found = $allClasses->first(function ($c) use ($targetGrade, $major) {
            return preg_match('/^' . $targetGrade . '\b/i', trim($c->name)) && $c->major === $major;
        });

        return $found;
    }

    /**
     * Mencari rombel yang tersedia untuk siswa tinggal kelas:
     * 1. Siswa WAJIB tetap di JURUSAN YANG SAMA pada jenjang tersebut (misal RPL tetap di RPL, TKJ tetap di TKJ).
     * 2. Bergantung pada ketersediaan kursi kosong (kapasitas maksimal 36 siswa per rombel).
     * 3. Jika kelas asal masih memiliki kursi kosong (< 36), siswa tetap di kelas asalnya.
     * 4. Jika kelas asal sudah penuh (>= 36), siswa dialokasikan ke rombel paralel di jurusan yang sama yang memiliki kursi kosong terbanyak.
     */
    protected function findAvailableClassForStayedStudent($student, $allClasses, $hasStatusCol, array &$currentCounts)
    {
        $currentClass = $student->schoolClass;
        if (!$currentClass) {
            return null;
        }

        $className = trim($currentClass->name);

        // 1. Ekstrak jenjang kelas (X, XI, XII)
        $grade = null;
        if (preg_match('/^(XII|12)\b/i', $className)) {
            $grade = 'XII';
        } elseif (preg_match('/^(XI|11)\b/i', $className)) {
            $grade = 'XI';
        } elseif (preg_match('/^(X|10)\b/i', $className)) {
            $grade = 'X';
        }

        if (!$grade) {
            return $currentClass;
        }

        // 2. Ekstrak kode jurusan resmi dari model (RPL, TKJ, DKV, AK, MP, BD, BR, LPS)
        $major = $currentClass->major;

        // 3. Filter rombel yang berada pada JENJANG YANG SAMA dan JURUSAN YANG SAMA
        $sameMajorClasses = $allClasses->filter(function ($c) use ($grade, $major) {
            $target = ($grade === 'XII') ? '(XII|12)' : (($grade === 'XI') ? '(XI|11)' : '(X|10)');
            if (!preg_match('/^' . $target . '\b/i', trim($c->name))) {
                return false;
            }
            if ($major && $major !== 'UMUM') {
                return $c->major === $major;
            }
            return true;
        });

        if ($sameMajorClasses->isEmpty()) {
            return $currentClass;
        }

        // 4. Hitung kuota terisi untuk setiap rombel di jurusan tersebut
        foreach ($sameMajorClasses as $c) {
            if (!isset($currentCounts[$c->id])) {
                $q = User::where('role', 'siswa')->where('class_id', $c->id);
                if ($hasStatusCol) {
                    $q->where(function ($sq) {
                        $sq->where('status', 'aktif')->orWhereNull('status');
                    });
                }
                $currentCounts[$c->id] = $q->count();
            }
        }

        // 5. Prioritas Alokasi Berdasarkan Sisa Kursi Kosong (Kapasitas Maksimal 36 Siswa):
        $currentClassCount = $currentCounts[$currentClass->id] ?? 0;

        // Rombel pada jurusan yang sama yang masih ada kursi kosong (< 36 siswa)
        $availableWithSeats = $sameMajorClasses->filter(fn($c) => ($currentCounts[$c->id] ?? 0) < 36);

        $chosenClass = null;

        if ($availableWithSeats->isNotEmpty()) {
            // Jika kelas asal masih memiliki kursi kosong (< 36), utamakan tetap di kelas asal
            if ($currentClassCount < 36 && $availableWithSeats->contains('id', $currentClass->id)) {
                $chosenClass = $currentClass;
            } else {
                // Jika kelas asal sudah penuh (>= 36), pilih rombel paralel di jurusan yang sama dengan sisa kursi terbanyak (jumlah siswa paling sedikit)
                $chosenClass = $availableWithSeats
                    ->filter(fn($c) => $c->id !== $currentClass->id)
                    ->sortBy(fn($c) => $currentCounts[$c->id] ?? 0)
                    ->first() ?: $currentClass;
            }
        } else {
            // Jika seluruh rombel pada jurusan tersebut sudah mencapai 36 siswa, pilih rombel dengan siswa paling sedikit di jurusan tersebut
            $chosenClass = $sameMajorClasses->sortBy(fn($c) => $currentCounts[$c->id] ?? 999)->first() ?: $currentClass;
        }

        if ($chosenClass) {
            $currentCounts[$chosenClass->id] = ($currentCounts[$chosenClass->id] ?? 0) + 1;
        }

        return $chosenClass;
    }
}
