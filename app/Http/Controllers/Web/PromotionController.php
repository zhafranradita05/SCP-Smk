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

        DB::transaction(function () use ($request, $sourceClass, $selectedIds, $hasStatusCol) {
            if ($request->action_type === 'promote') {
                $targetClass = SchoolClass::findOrFail($request->target_class_id);
                $updateData = ['class_id' => $targetClass->id];
                if ($hasStatusCol) {
                    $updateData['status'] = 'aktif';
                }

                User::whereIn('id', $selectedIds)
                    ->where('class_id', $sourceClass->id)
                    ->update($updateData);
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
                $msg .= " ({$stayedCount} siswa yang tidak dicentang tetap tinggal di {$sourceClass->name}).";
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

        DB::transaction(function () use (
            $grade12Classes, $grade11Classes, $grade10Classes, $allClasses,
            $stayedStudentIds, $hasStatusCol,
            &$graduatedCount, &$promoted11to12Count, &$promoted10to11Count, &$unmappedClasses
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
        });

        $totalSuccess = $graduatedCount + $promoted11to12Count + $promoted10to11Count;
        $stayedCount = count($stayedStudentIds);

        $msg = "Kenaikan Massal Berhasil! Sebanyak {$totalSuccess} siswa berhasil diproses serentak: "
            . "{$graduatedCount} siswa Kelas XII LULUS, "
            . "{$promoted11to12Count} siswa Kelas XI naik ke XII, dan "
            . "{$promoted10to11Count} siswa Kelas X naik ke XI.";

        if ($stayedCount > 0) {
            $msg .= " ({$stayedCount} siswa yang ditandai tinggal kelas tetap dipertahankan di rombel asalnya).";
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
}
