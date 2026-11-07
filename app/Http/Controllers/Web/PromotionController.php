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
     * Menampilkan Halaman Kenaikan Kelas & Kelulusan Siswa
     */
    public function index(Request $request)
    {
        $hasStatusCol = Schema::hasColumn('users', 'status');

        // Ambil semua daftar kelas beserta jumlah siswa aktifnya
        $classes = SchoolClass::orderBy('name', 'asc')
            ->withCount(['students' => function ($q) use ($hasStatusCol) {
                if ($hasStatusCol) {
                    $q->where(function ($sq) {
                        $sq->where('status', 'aktif')->orWhereNull('status');
                    });
                }
            }])
            ->get();

        $sourceClassId = $request->query('source_class_id');
        $sourceClass = null;
        $students = collect();
        $suggestedTargetClassId = null;
        $isFinalYearClass = false;

        if ($sourceClassId) {
            $sourceClass = SchoolClass::find($sourceClassId);
            if ($sourceClass) {
                $query = User::where('role', 'siswa')->where('class_id', $sourceClass->id);
                if ($hasStatusCol) {
                    $query->where(function ($q) {
                        $q->where('status', 'aktif')->orWhereNull('status');
                    });
                }
                $students = $query->orderBy('name', 'asc')->get();

                // Deteksi otomatis kelas tujuan berdasarkan penamaan jenjang
                $className = trim($sourceClass->name);
                if (preg_match('/^(XII|12)\b/i', $className)) {
                    $isFinalYearClass = true;
                } elseif (preg_match('/^(X|10)\b/i', $className)) {
                    $targetPattern = preg_replace('/^(X|10)\b/i', 'XI', $className);
                    $match = $classes->first(function ($c) use ($targetPattern) {
                        return strcasecmp(str_replace(' ', '', $c->name), str_replace(' ', '', $targetPattern)) === 0;
                    });
                    $suggestedTargetClassId = $match ? $match->id : null;
                } elseif (preg_match('/^(XI|11)\b/i', $className)) {
                    $targetPattern = preg_replace('/^(XI|11)\b/i', 'XII', $className);
                    $match = $classes->first(function ($c) use ($targetPattern) {
                        return strcasecmp(str_replace(' ', '', $c->name), str_replace(' ', '', $targetPattern)) === 0;
                    });
                    $suggestedTargetClassId = $match ? $match->id : null;
                }
            }
        }

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

        // Ambil daftar alumni terbaru untuk tab riwayat/batal lulus
        $recentAlumni = collect();
        if ($hasStatusCol) {
            $recentAlumni = User::where('role', 'siswa')
                ->where('status', 'lulus')
                ->orderBy('updated_at', 'desc')
                ->take(15)
                ->get();
        }

        // Data pendukung untuk Opsi 2: Kenaikan Massal Serentak Seluruh Sekolah
        $activeStudentsQuery = User::where('role', 'siswa')
            ->whereNotNull('class_id')
            ->with('schoolClass');
        if ($hasStatusCol) {
            $activeStudentsQuery->where(function ($q) {
                $q->where('status', 'aktif')->orWhereNull('status');
            });
        }
        $allActiveStudents = $activeStudentsQuery->orderBy('name', 'asc')->get();

        // Hitung perkiraan per tingkat & Pratinjau Alur Otomatis
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
            } elseif (preg_match('/^(XI|11)\b/i', $cName)) {
                $countXI += $studentCount;
                $targetPattern = preg_replace('/^(XI|11)\b/i', 'XII', $cName);
                $targetClass = $classes->first(function ($item) use ($targetPattern) {
                    return strcasecmp(str_replace(' ', '', $item->name), str_replace(' ', '', $targetPattern)) === 0;
                });
                $bulkMappingPreview[] = [
                    'source' => $cName,
                    'target' => $targetClass ? $targetClass->name : '⚠️ Rombel ' . $targetPattern . ' Belum Ada',
                    'count'  => $studentCount,
                    'status' => $targetClass ? 'promote' : 'unmapped'
                ];
            } elseif (preg_match('/^(X|10)\b/i', $cName)) {
                $countX += $studentCount;
                $targetPattern = preg_replace('/^(X|10)\b/i', 'XI', $cName);
                $targetClass = $classes->first(function ($item) use ($targetPattern) {
                    return strcasecmp(str_replace(' ', '', $item->name), str_replace(' ', '', $targetPattern)) === 0;
                });
                $bulkMappingPreview[] = [
                    'source' => $cName,
                    'target' => $targetClass ? $targetClass->name : '⚠️ Rombel ' . $targetPattern . ' Belum Ada',
                    'count'  => $studentCount,
                    'status' => $targetClass ? 'promote' : 'unmapped'
                ];
            }
        }

        return view('waka.promotions.index', compact(
            'classes',
            'sourceClassId',
            'sourceClass',
            'students',
            'suggestedTargetClassId',
            'isFinalYearClass',
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
     * Memproses Kenaikan Kelas atau Kelulusan Siswa Per Rombel
     */
    public function process(Request $request)
    {
        $hasStatusCol = Schema::hasColumn('users', 'status');

        $request->validate([
            'source_class_id' => 'required|exists:classes,id',
            'action_type'     => 'required|in:promote,graduate',
            'target_class_id' => 'required_if:action_type,promote|nullable|exists:classes,id',
            'student_ids'     => 'nullable|array',
            'student_ids.*'   => 'exists:users,id',
        ], [
            'source_class_id.required'    => 'Silakan pilih kelas asal terlebih dahulu.',
            'action_type.required'        => 'Pilih tindakan (Naik Kelas atau Luluskan).',
            'target_class_id.required_if' => 'Silakan pilih kelas tujuan kenaikan.',
        ]);

        $sourceClass = SchoolClass::findOrFail($request->source_class_id);

        if ($request->action_type === 'promote' && $request->source_class_id == $request->target_class_id) {
            return back()->with('error', 'Kelas tujuan tidak boleh sama dengan kelas asal! Siswa yang tidak dicentang otomatis tetap tinggal di kelas asal.');
        }

        // Ambil semua siswa aktif di kelas asal
        $allClassStudents = User::where('role', 'siswa')
            ->where('class_id', $sourceClass->id);
        if ($hasStatusCol) {
            $allClassStudents->where(function ($q) {
                $q->where('status', 'aktif')->orWhereNull('status');
            });
        }
        $allStudentIds = $allClassStudents->pluck('id')->toArray();

        $selectedIds = $request->input('student_ids', []);
        $selectedCount = count($selectedIds);
        $stayedCount = count($allStudentIds) - $selectedCount;

        if ($selectedCount === 0) {
            return back()->with('error', 'Tidak ada siswa yang dipilih! Centang minimal 1 siswa untuk diproses.');
        }

        $allClasses = SchoolClass::all();
        $stayedIds = array_values(array_diff($allStudentIds, $selectedIds));
        $stayedReassignments = [];
        $currentCounts = [];

        DB::transaction(function () use (
            $request, $sourceClass, $selectedIds, $stayedIds, $hasStatusCol, $allClasses,
            &$stayedReassignments, &$currentCounts
        ) {
            if ($request->action_type === 'promote') {
                $targetClass = SchoolClass::findOrFail($request->target_class_id);
                $updateData = ['class_id' => $targetClass->id];
                if ($hasStatusCol) {
                    $updateData['status'] = 'aktif';
                }

                User::whereIn('id', $selectedIds)
                    ->where('class_id', $sourceClass->id)
                    ->update($updateData);

                // Siswa yang tidak dicentang (tinggal kelas): Otomatis dipindahkan ke rombel paralel yang memiliki kursi kosong (< 36 siswa)
                if (!empty($stayedIds)) {
                    $stayedStudents = User::whereIn('id', $stayedIds)->with('schoolClass')->get();
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
            } else {
                // Aksi Luluskan Siswa: lepaskan class_id dan beri status lulus
                $updateData = ['class_id' => null];
                if ($hasStatusCol) {
                    $updateData['status'] = 'lulus';
                }

                User::whereIn('id', $selectedIds)
                    ->where('class_id', $sourceClass->id)
                    ->update($updateData);
            }
        });

        if ($request->action_type === 'promote') {
            $targetClass = SchoolClass::findOrFail($request->target_class_id);
            $msg = "Kenaikan kelas berhasil! {$selectedCount} siswa dari {$sourceClass->name} berhasil dinaikkan ke {$targetClass->name}.";
            if ($stayedCount > 0) {
                $msg .= " ({$stayedCount} siswa yang tidak dicentang tinggal kelas dan otomatis dialokasikan ke rombel dengan kursi kosong maks 36 siswa: " . implode(', ', $stayedReassignments) . ").";
            }
        } else {
            $msg = "Kelulusan berhasil diproses! {$selectedCount} siswa dari {$sourceClass->name} resmi dinyatakan LULUS (Alumni).";
            if ($stayedCount > 0) {
                $msg .= " ({$stayedCount} siswa yang tidak dicentang tetap berstatus aktif di {$sourceClass->name}).";
            }
        }

        return redirect()->route('waka.promotions.index', ['source_class_id' => $sourceClass->id])
            ->with('success', $msg);
    }

    /**
     * Memproses Kenaikan Massal Seluruh Sekolah Sekali Klik (Dengan Pengecualian Siswa Tinggal Kelas)
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
                $name = trim($c11->name);
                $targetPattern = preg_replace('/^(XI|11)\b/i', 'XII', $name);
                $targetClass = $allClasses->first(function ($c) use ($targetPattern) {
                    return strcasecmp(str_replace(' ', '', $c->name), str_replace(' ', '', $targetPattern)) === 0;
                });

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
                    $unmappedClasses[] = $name;
                }
            }

            // TAHAP 3: Naikkan Siswa Kelas X ke Kelas XI
            foreach ($grade10Classes as $c10) {
                $name = trim($c10->name);
                $targetPattern = preg_replace('/^(X|10)\b/i', 'XI', $name);
                $targetClass = $allClasses->first(function ($c) use ($targetPattern) {
                    return strcasecmp(str_replace(' ', '', $c->name), str_replace(' ', '', $targetPattern)) === 0;
                });

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
                    $unmappedClasses[] = $name;
                }
            }

            // TAHAP 4: Alokasi Siswa Tinggal Kelas Berdasarkan Kursi Kosong (Kapasitas Maksimal 36 Siswa)
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
            $msg .= " ({$stayedCount} siswa tinggal kelas dialokasikan ke rombel dengan kursi kosong maks 36 siswa: " . implode(', ', $stayedReassignments) . ").";
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
            // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
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
     * Mencari rombel yang tersedia untuk siswa tinggal kelas berdasarkan sisa kursi (maksimal 36 siswa per rombel)
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

        // 2. Ekstrak jurusan / keahlian (misal RPL, TKJ, DKV, MP, AK, dst.)
        $major = null;
        if (preg_match('/^(X|XI|XII|10|11|12)\s+([A-Za-z]+)/i', $className, $matches)) {
            $major = strtoupper($matches[2]);
        }

        // 3. Filter semua kelas pada jenjang yang sama
        $candidateClasses = $allClasses->filter(function ($c) use ($grade) {
            $target = ($grade === 'XII') ? '(XII|12)' : (($grade === 'XI') ? '(XI|11)' : '(X|10)');
            return preg_match('/^'.$target.'\b/i', trim($c->name));
        });

        // Hitung kuota terisi untuk setiap kelas kandidat
        foreach ($candidateClasses as $c) {
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

        // Kandidat 1: Rombel paralel di jurusan yang sama (misal RPL)
        $sameMajorClasses = $candidateClasses->filter(function ($c) use ($major) {
            if (!$major) return true;
            return stripos(trim($c->name), $major) !== false;
        });

        // Cari rombel paralel lain (selain kelas asal) yang kursinya masih tersedia (< 36)
        $otherParallelWithSeats = $sameMajorClasses
            ->filter(fn($c) => $c->id !== $currentClass->id && ($currentCounts[$c->id] ?? 0) < 36)
            ->sortBy(fn($c) => $currentCounts[$c->id] ?? 0);

        $chosenClass = null;

        if ($otherParallelWithSeats->isNotEmpty()) {
            // Pindahkan ke rombel paralel lain yang masih memiliki sisa kursi terbanyak
            $chosenClass = $otherParallelWithSeats->first();
        } elseif (($currentCounts[$currentClass->id] ?? 0) < 36) {
            // Jika rombel paralel lain penuh tapi kelas asal masih ada kursi (< 36), tetap di kelas asal
            $chosenClass = $currentClass;
        } else {
            // Jika semua rombel jurusan tersebut penuh (>= 36), cari kelas apapun di jenjang tersebut yang < 36
            $anyClassWithSeats = $candidateClasses
                ->filter(fn($c) => ($currentCounts[$c->id] ?? 0) < 36)
                ->sortBy(fn($c) => $currentCounts[$c->id] ?? 0);

            if ($anyClassWithSeats->isNotEmpty()) {
                $chosenClass = $anyClassWithSeats->first();
            } else {
                // Jika seluruh sekolah penuh, pilih kelas dengan siswa paling sedikit
                $chosenClass = $candidateClasses->sortBy(fn($c) => $currentCounts[$c->id] ?? 999)->first() ?: $currentClass;
            }
        }

        if ($chosenClass) {
            // Catat kursi terisi bertambah 1
            $currentCounts[$chosenClass->id] = ($currentCounts[$chosenClass->id] ?? 0) + 1;
        }

        return $chosenClass;
    }
}
