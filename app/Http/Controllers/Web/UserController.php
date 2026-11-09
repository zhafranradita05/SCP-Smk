<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SchoolClass;
use App\Traits\MultiFileParserTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use MultiFileParserTrait;
    /**
     * Menampilkan daftar pengguna dengan relasi kelas & wali kelas
     */
    public function index(Request $request)
    {
        $roleFilter = $request->query('role');
        $search     = $request->query('search');
        $tingkat    = $request->query('tingkat', 'all');
        $jurusan    = $request->query('jurusan', 'all');
        $rombel     = $request->query('rombel', 'all');

        // Eager-load relasi schoolClass, homeroomClass, bkClasses, dan student (untuk akun Orang Tua)
        $query = User::with(['schoolClass', 'homeroomClass', 'bkClasses', 'student.schoolClass']);

        $statusWali = $request->query('status_wali', 'all');

        // Normalisasi peran Guru Pegawai (menggabungkan 'guru' dan 'wali_kelas')
        $isGuru = in_array($roleFilter, ['guru', 'wali_kelas']);
        $isSiswa = ($roleFilter === 'siswa');
        $isParent = ($roleFilter === 'orang_tua');

        if ($isGuru) {
            $query->whereIn('role', ['guru', 'wali_kelas']);
            $roleFilter = 'guru'; // Seragamkan ke 'guru' (Guru Pegawai)
        } elseif ($roleFilter && $roleFilter !== 'all') {
            $query->where('role', $roleFilter);
        }

        // Filter Penugasan Wali Kelas khusus untuk Guru Pegawai
        if ($isGuru && $statusWali && $statusWali !== 'all') {
            if ($statusWali === 'wali') {
                $query->has('homeroomClass');
            } elseif ($statusWali === 'non_wali') {
                $query->doesntHave('homeroomClass');
            }
        }

        // Filter Tingkat Kelas (X, XI, XII, none untuk Belum Ada Kelas / Belum Ditugaskan, alumni untuk Siswa Lulus)
        if ($tingkat && $tingkat !== 'all') {
            if ($tingkat === 'none') {
                if ($isGuru) {
                    $query->doesntHave('homeroomClass');
                } elseif ($isSiswa) {
                    $query->whereNull('class_id');
                } elseif ($isParent) {
                    $query->whereNull('class_id')->whereDoesntHave('student');
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('class_id')->orWhereDoesntHave('homeroomClass');
                    });
                }
            } elseif ($tingkat === 'alumni') {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
                    if ($isParent) {
                        $query->whereHas('student', fn($sq) => $sq->where('status', 'lulus'));
                    } else {
                        $query->where('status', 'lulus');
                    }
                } else {
                    $query->whereNull('class_id');
                }
            } else {
                if ($isGuru) {
                    $query->whereHas('homeroomClass', function ($q) use ($tingkat) {
                        $q->where('name', 'like', $tingkat . ' %')
                          ->orWhere('name', 'like', $tingkat . '-%');
                    });
                } elseif ($isSiswa) {
                    $query->whereHas('schoolClass', function ($q) use ($tingkat) {
                        $q->where('name', 'like', $tingkat . ' %')
                          ->orWhere('name', 'like', $tingkat . '-%');
                    });
                } elseif ($isParent) {
                    $query->where(function ($pq) use ($tingkat) {
                        $pq->whereHas('student.schoolClass', function ($q) use ($tingkat) {
                            $q->where('name', 'like', $tingkat . ' %')
                              ->orWhere('name', 'like', $tingkat . '-%');
                        })->orWhereHas('schoolClass', function ($q) use ($tingkat) {
                            $q->where('name', 'like', $tingkat . ' %')
                              ->orWhere('name', 'like', $tingkat . '-%');
                        });
                    });
                } else {
                    $query->where(function ($wq) use ($tingkat) {
                        $wq->whereHas('schoolClass', function ($q) use ($tingkat) {
                            $q->where('name', 'like', $tingkat . ' %')
                              ->orWhere('name', 'like', $tingkat . '-%');
                        })->orWhereHas('homeroomClass', function ($q) use ($tingkat) {
                            $q->where('name', 'like', $tingkat . ' %')
                              ->orWhere('name', 'like', $tingkat . '-%');
                        })->orWhereHas('student.schoolClass', function ($q) use ($tingkat) {
                            $q->where('name', 'like', $tingkat . ' %')
                              ->orWhere('name', 'like', $tingkat . '-%');
                        });
                    });
                }
            }
        }

        // Filter Jurusan (AK, BD, BR, DKV, LPS, MP, RPL, TKJ)
        if ($jurusan && $jurusan !== 'all') {
            $matchJurusan = function ($q) use ($jurusan) {
                $q->where(function ($sq) use ($jurusan) {
                    $sq->where('name', 'like', '% ' . $jurusan . ' %')
                       ->orWhere('name', 'like', '% ' . $jurusan)
                       ->orWhere('name', 'like', '%-' . $jurusan . '-%')
                       ->orWhere('name', 'like', '%-' . $jurusan);
                });
            };

            if ($isGuru) {
                $query->whereHas('homeroomClass', $matchJurusan);
            } elseif ($isSiswa) {
                $query->whereHas('schoolClass', $matchJurusan);
            } elseif ($isParent) {
                $query->where(function ($pq) use ($matchJurusan) {
                    $pq->whereHas('student.schoolClass', $matchJurusan)
                       ->orWhereHas('schoolClass', $matchJurusan);
                });
            } else {
                $query->where(function ($wq) use ($matchJurusan) {
                    $wq->whereHas('schoolClass', $matchJurusan)
                       ->orWhereHas('homeroomClass', $matchJurusan)
                       ->orWhereHas('student.schoolClass', $matchJurusan);
                });
            }
        }

        // Filter Rombel (1, 2, 3, 4)
        if ($rombel && $rombel !== 'all') {
            $matchRombel = function ($q) use ($rombel) {
                $q->where(function ($sq) use ($rombel) {
                    $sq->where('name', 'like', '% ' . $rombel)
                       ->orWhere('name', 'like', '%-' . $rombel);
                });
            };

            if ($isGuru) {
                $query->whereHas('homeroomClass', $matchRombel);
            } elseif ($isSiswa) {
                $query->whereHas('schoolClass', $matchRombel);
            } elseif ($isParent) {
                $query->where(function ($pq) use ($matchRombel) {
                    $pq->whereHas('student.schoolClass', $matchRombel)
                       ->orWhereHas('schoolClass', $matchRombel);
                });
            } else {
                $query->where(function ($wq) use ($matchRombel) {
                    $wq->whereHas('schoolClass', $matchRombel)
                       ->orWhereHas('homeroomClass', $matchRombel)
                       ->orWhereHas('student.schoolClass', $matchRombel);
                });
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search, $isGuru, $isSiswa, $isParent) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('identifier', 'like', "%{$search}%");

                if ($isGuru) {
                    $q->orWhereHas('homeroomClass', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
                } elseif ($isSiswa) {
                    $q->orWhereHas('schoolClass', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
                } elseif ($isParent) {
                    $q->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                           ->orWhere('identifier', 'like', "%{$search}%");
                    })->orWhereHas('student.schoolClass', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
                } else {
                    $q->orWhereHas('schoolClass', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('homeroomClass', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    })->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                           ->orWhere('identifier', 'like', "%{$search}%");
                    });
                }
            });
        }

        $users = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        $availableJurusan = ['AK', 'BD', 'BR', 'DKV', 'LPS', 'MP', 'RPL', 'TKJ'];
        $availableRombel  = ['1', '2', '3', '4'];
        $classesList      = SchoolClass::orderBy('name')->get();

        // Hitung statistik siswa per Angkatan
        $studentCounts = [
            'all'        => User::where('role', 'siswa')->count(),
            'X'          => User::where('role', 'siswa')->whereHas('schoolClass', fn($q) => $q->where('name', 'like', 'X %')->orWhere('name', 'like', 'X-%'))->count(),
            'XI'         => User::where('role', 'siswa')->whereHas('schoolClass', fn($q) => $q->where('name', 'like', 'XI %')->orWhere('name', 'like', 'XI-%'))->count(),
            'XII'        => User::where('role', 'siswa')->whereHas('schoolClass', fn($q) => $q->where('name', 'like', 'XII %')->orWhere('name', 'like', 'XII-%'))->count(),
            'alumni'     => \Illuminate\Support\Facades\Schema::hasColumn('users', 'status') ? User::where('role', 'siswa')->where('status', 'lulus')->count() : 0,
            'unassigned' => User::where('role', 'siswa')->whereNull('class_id')->count(),
        ];

        return view('waka.users.index', compact(
            'users', 
            'roleFilter', 
            'search',
            'tingkat',
            'jurusan',
            'rombel',
            'statusWali',
            'availableJurusan',
            'availableRombel',
            'classesList',
            'studentCounts'
        ));
    }

    /**
     * Menampilkan form tambah pengguna
     */
    public function create()
    {
        $classes = SchoolClass::orderBy('name', 'asc')->get();
        return view('waka.users.create', compact('classes'));
    }

    /**
     * Menyimpan data pengguna baru beserta kelas siswa
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:50|unique:users,username',
            'email'      => 'required|email|max:255|unique:users,email',
            'password'   => 'required|string|min:6',
            'role'       => ['required', Rule::in(['siswa', 'guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum'])],
            'identifier' => 'nullable|string|max:50',
            'phone'      => 'nullable|string|max:20',
            'class_id'   => 'nullable|exists:classes,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // Jika bukan siswa, pastikan class_id bernilai null
        if ($validated['role'] !== 'siswa') {
            $validated['class_id'] = null;
        }

        $user = User::create($validated);

        // Jika user yang ditambahkan adalah siswa, sinkronkan otomatis akun Orang Tua
        if ($user->role === 'siswa') {
            $parentNis = trim($user->identifier ?: $user->username);
            if (!empty($parentNis)) {
                User::updateOrCreate(
                    ['student_id' => $user->id],
                    [
                        'name'       => 'Orang Tua ' . $user->name,
                        'username'   => 'P' . $parentNis,
                        'identifier' => 'P' . $parentNis,
                        'email'      => 'p' . strtolower($parentNis) . '@gmail.com',
                        'password'   => Hash::make('password123'),
                        'role'       => 'orang_tua',
                        'class_id'   => $user->class_id,
                        'phone'      => $user->phone,
                        'status'     => 'aktif',
                    ]
                );
            }
        }

        $redirectRole = in_array($user->role, ['guru', 'wali_kelas']) ? 'guru' : $user->role;
        return redirect()->route('waka.users.index', ['role' => $redirectRole])
            ->with('success', 'Pengguna berhasil ditambahkan!');
    }

    /**
     * Menampilkan form edit pengguna
     */
    public function edit(User $user)
    {
        $classes = SchoolClass::orderBy('name', 'asc')->get();
        return view('waka.users.edit', compact('user', 'classes'));
    }

    /**
     * Memperbarui data pengguna beserta kelas siswa
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'   => 'nullable|string|min:6',
            'role'       => ['required', Rule::in(['siswa', 'guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum'])],
            'identifier' => 'nullable|string|max:50',
            'phone'      => 'nullable|string|max:20',
            'class_id'   => 'nullable|exists:classes,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($validated['role'] !== 'siswa') {
            $validated['class_id'] = null;
        }

        $user->update($validated);

        // Jika user yang diupdate adalah siswa, sinkronkan data akun Orang Tua
        if ($user->role === 'siswa') {
            $parentNis = trim($user->identifier ?: $user->username);
            if (!empty($parentNis)) {
                $parentUser = User::where('student_id', $user->id)->orWhere('username', 'P' . $parentNis)->first();
                if ($parentUser) {
                    $parentUser->update([
                        'name'       => 'Orang Tua ' . $user->name,
                        'username'   => 'P' . $parentNis,
                        'identifier' => 'P' . $parentNis,
                        'email'      => 'p' . strtolower($parentNis) . '@gmail.com',
                        'class_id'   => $user->class_id,
                        'phone'      => $user->phone,
                        'status'     => $user->status ?? 'aktif',
                    ]);
                } else {
                    User::create([
                        'name'       => 'Orang Tua ' . $user->name,
                        'username'   => 'P' . $parentNis,
                        'identifier' => 'P' . $parentNis,
                        'email'      => 'p' . strtolower($parentNis) . '@gmail.com',
                        'password'   => Hash::make('password123'),
                        'role'       => 'orang_tua',
                        'student_id' => $user->id,
                        'class_id'   => $user->class_id,
                        'phone'      => $user->phone,
                        'status'     => $user->status ?? 'aktif',
                    ]);
                }
            }
        }

        $redirectRole = in_array($user->role, ['guru', 'wali_kelas']) ? 'guru' : $user->role;
        return redirect()->route('waka.users.index', ['role' => $redirectRole])
            ->with('success', 'Data pengguna berhasil diperbarui!');
    }

    /**
     * Menghapus pengguna
     */
    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri!');
        }

        // Jika siswa dihapus, hapus juga akun Orang Tua terkait
        if ($user->role === 'siswa') {
            User::where('student_id', $user->id)->delete();
        }

        $user->delete();

        return redirect()->back()->with('success', 'Pengguna berhasil dihapus!');
    }

    /**
     * Menampilkan form edit profil Waka Kurikulum yang sedang login
     */
    public function editProfile()
    {
        $user = Auth::user();
        return view('waka.profile', compact('user'));
    }

    /**
     * Memperbarui data profil Waka Kurikulum yang sedang login
     */
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'identifier' => 'nullable|string|max:50',
            'phone'      => 'nullable|string|max:20',
            'password'   => 'nullable|string|min:6|confirmed',
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'username.required'  => 'Username login wajib diisi.',
            'username.unique'    => 'Username ini sudah digunakan akun lain.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'email.unique'       => 'Email ini sudah digunakan akun lain.',
            'password.min'       => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('waka.profile.edit')
            ->with('success', 'Profil Waka Kurikulum berhasil diperbarui!');
    }

    /**
     * Download template Excel/CSV untuk import data siswa
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_siswa.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $availableClasses = SchoolClass::orderBy('name', 'asc')->pluck('name')->toArray();
        $sampleClass1 = $availableClasses[0] ?? 'X RPL 1';
        $sampleClass2 = $availableClasses[1] ?? 'X TKJ 1';

        $callback = function () use ($sampleClass1, $sampleClass2) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM agar terbaca sempurna di Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Baris Header
            fputcsv($handle, ['nama', 'nisn', 'kelas', 'username', 'email', 'telepon', 'password'], ';');

            // Contoh Data 1
            fputcsv($handle, [
                'Budi Santoso',
                '1005',
                $sampleClass1,
                'budi',
                'budi@gmail.com',
                '08123456789',
                'password123',
            ], ';');

            // Contoh Data 2
            fputcsv($handle, [
                'Siti Rahmawati',
                '1006',
                $sampleClass2,
                'siti',
                'siti@gmail.com',
                '08129876543',
                'password123',
            ], ';');

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Memproses import data siswa dari file Excel, CSV, TSV, HTML, PDF
     * Mendukung: format template resmi, presensi sekolah (multi-kolom/multi-pertemuan),
     * format buku nilai, workbook multi-sheet (1 sheet per kelas), dan multi-file sekaligus.
     */
    public function importStudents(Request $request)
    {
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploadedFiles = is_array($files) ? $files : [$files];
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        }

        if (empty($uploadedFiles)) {
            return redirect()->back()->with('error', 'Silakan pilih minimal 1 file spreadsheet atau presensi (Excel, CSV, atau PDF) untuk diimport!');
        }

        $explicitClassId = $request->input('target_class_id'); // Pilihan kelas manual jika dipilih di modal
        $targetAngkatan  = $request->input('target_angkatan'); // Pilihan angkatan (X, XI, XII)
        if ($targetAngkatan === 'all') $targetAngkatan = null;

        // Simpan data kelas ke memory untuk pencarian cepat
        $classes = SchoolClass::all()->mapWithKeys(function ($c) {
            return [strtolower(trim($c->name)) => $c->id];
        });

        // Hash default password sekali untuk performa import massal cepat (sub-detik)
        $defaultPasswordHash = Hash::make('password123');
        $usedUsernamesInBatch = [];
        $usedEmailsInBatch    = [];

        $totalCreated   = 0;
        $totalUpdated   = 0;
        $totalSkipped   = 0;
        $processedFiles = [];
        $errors         = [];
        $unregisteredClasses = [];
        $matchedClassNames   = [];

        foreach ($uploadedFiles as $file) {
            if (!$file->isValid()) continue;

            $origName = $file->getClientOriginalName();
            $parsed = $this->parseAnyFileWithSheets($file->getPathname(), $origName);
            $formatName = $parsed['format'];
            $sheets = $parsed['sheets'];

            if (empty($sheets)) continue;

            $processedFiles[] = "{$origName} ({$formatName})";

            foreach ($sheets as $sheet) {
                $sheetName = trim($sheet['name']);
                $rows = $sheet['rows'];

                if (empty($rows) || count($rows) < 2) {
                    continue;
                }

                // 1. Tentukan Kelas Tingkat Sheet (Sheet-level Class)
                $sheetClassId = $explicitClassId ?: null;
                if (!$sheetClassId && !empty($sheetName)) {
                    $sheetClassId = $this->resolveClassIdFromText($sheetName, $classes, $targetAngkatan);
                }
                if (!$sheetClassId) {
                    $sheetClassId = $this->resolveClassIdFromText($origName, $classes, $targetAngkatan);
                }

                if ($sheetClassId) {
                    $cName = SchoolClass::find($sheetClassId)?->name;
                    if ($cName) $matchedClassNames[$cName] = true;
                }

                // Ekstraksi data siswa secara cerdas (Mendukung format presensi multi-kelas bertumpuk, buku nilai, maupun template resmi)
                $extraction = $this->extractStudentsFromRows($rows, $classes, $targetAngkatan, $sheetClassId);
                $studentList = $extraction['students'];

                foreach ($extraction['matchedClasses'] as $cName) {
                    $matchedClassNames[$cName] = true;
                }

                // 2. Iterasi setiap data siswa yang berhasil diekstraksi
                foreach ($studentList as $sItem) {
                    $nama     = $sItem['name'];
                    $rawNis   = $sItem['nis'];
                    $classId  = $sItem['class_id'] ?: $sheetClassId;
                    $telepon  = $sItem['phone'] ?? null;
                    $email    = $sItem['email'] ?? '';
                    $username = $sItem['username'] ?? '';
                    $password = $sItem['password'] ?? 'password123';

                    if (empty($nama)) {
                        $totalSkipped++;
                        continue;
                    }

                    if ($classId) {
                        $cName = SchoolClass::find($classId)?->name;
                        if ($cName) $matchedClassNames[$cName] = true;
                    }

                    // 3. Pencocokan Siswa yang Sudah Ada (Upsert Cerdas)
                    $existingUser = null;
                    if (!empty($rawNis)) {
                        $existingUser = User::where('role', 'siswa')->where('identifier', $rawNis)->first();
                    }
                    if (!$existingUser && $classId) {
                        $lowerName = strtolower($nama);
                        $existingUser = User::where('role', 'siswa')
                            ->where('class_id', $classId)
                            ->whereRaw('LOWER(name) = ?', [$lowerName])
                            ->first();
                    }
                    if (!$existingUser && !empty($username)) {
                        $existingUser = User::where('username', $username)->first();
                    }

                    // 4. Generate username otomatis 1 suku kata jika belum ada atau masih format lama
                    if (empty($username)) {
                        if ($existingUser && !empty($existingUser->username) && !str_starts_with($existingUser->username, 'siswa_')) {
                            $username = $existingUser->username;
                        } else {
                            $username = self::generateSingleWordUsername($nama, $existingUser?->id, $usedUsernamesInBatch);
                        }
                    }
                    $usedUsernamesInBatch[] = $username;

                    // 5. Generate email otomatis (<username>@gmail.com) jika belum ada atau masih format lama
                    if (empty($email)) {
                        if ($existingUser && !empty($existingUser->email) && !str_contains($existingUser->email, '@siswa.sekolah.com')) {
                            $email = $existingUser->email;
                        } else {
                            $baseEmail = $username . '@gmail.com';
                            $email = $baseEmail;
                            $eCounter = 1;
                            while (
                                (User::where('email', $email)->when($existingUser, fn($q) => $q->where('id', '!=', $existingUser->id))->exists())
                                || in_array($email, $usedEmailsInBatch, true)
                            ) {
                                $email = $username . $eCounter . '@gmail.com';
                                $eCounter++;
                            }
                        }
                    }
                    $usedEmailsInBatch[] = $email;

                    try {
                        if ($existingUser) {
                            $updateData = [
                                'name' => $nama,
                            ];
                            if ($telepon && (empty($existingUser->phone) || $existingUser->phone === '-')) {
                                $updateData['phone'] = $telepon;
                            }
                            if ($classId) {
                                $updateData['class_id'] = $classId;
                            }
                            if ($rawNis && $existingUser->identifier !== $rawNis) {
                                $updateData['identifier'] = $rawNis;
                            }
                            // Jika username atau email masih format siswa_ / @siswa.sekolah.com, otomatis ubah ke 1 suku kata & @gmail.com
                            if (str_starts_with($existingUser->username, 'siswa_') || str_contains($existingUser->email, '@siswa.sekolah.com')) {
                                $updateData['username'] = $username;
                                $updateData['email'] = $email;
                            }
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
                                $updateData['status'] = 'aktif';
                            }
                            $existingUser->update($updateData);
                            $totalUpdated++;
                            $savedStudent = $existingUser;
                        } else {
                            $newUserData = [
                                'name'       => $nama,
                                'username'   => $username,
                                'email'      => $email,
                                'password'   => (!empty($password) && $password !== 'password123') ? Hash::make($password) : $defaultPasswordHash,
                                'role'       => 'siswa',
                                'identifier' => $rawNis,
                                'class_id'   => $classId,
                                'phone'      => $telepon,
                            ];
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
                                $newUserData['status'] = 'aktif';
                            }
                            $savedStudent = User::create($newUserData);
                            $totalCreated++;
                        }

                        // Sinkronkan akun Orang Tua berelasi dengan Siswa (P + NIS)
                        if ($savedStudent) {
                            $parentNis = trim($savedStudent->identifier ?: $savedStudent->username);
                            if (!empty($parentNis)) {
                                User::updateOrCreate(
                                    ['student_id' => $savedStudent->id],
                                    [
                                        'name'       => 'Orang Tua ' . $savedStudent->name,
                                        'username'   => 'P' . $parentNis,
                                        'identifier' => 'P' . $parentNis,
                                        'email'      => 'p' . strtolower($parentNis) . '@gmail.com',
                                        'password'   => $defaultPasswordHash,
                                        'role'       => 'orang_tua',
                                        'class_id'   => $savedStudent->class_id,
                                        'phone'      => $savedStudent->phone,
                                        'status'     => 'aktif',
                                    ]
                                );
                            }
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Baris " . ($i + 1) . " ($nama): " . $e->getMessage();
                    }
                }
            }
        }

        if ($totalCreated === 0 && $totalUpdated === 0 && empty($errors)) {
            return redirect()->back()->with('error', 'Tidak ada data siswa yang berhasil diimpor! Pastikan file memuat kolom Nama Siswa dan NIS/Kelas.');
        }

        $classesMsg = !empty($matchedClassNames) ? " [Kelas: " . implode(', ', array_keys($matchedClassNames)) . "]" : "";
        $msg = "Import data siswa berhasil! {$totalCreated} siswa baru ditambahkan, {$totalUpdated} siswa diperbarui{$classesMsg}.";
        if ($totalSkipped > 0) {
            $msg .= " ({$totalSkipped} baris kosong/keterangan dilewati).";
        }
        if (!empty($unregisteredClasses)) {
            $missingList = implode(', ', array_keys($unregisteredClasses));
            $msg .= " Perhatian: Kelas [ {$missingList} ] belum terdaftar di Master Kelas. Silakan tambahkan kelas tersebut di menu Master Kelas.";
        }
        if (!empty($errors)) {
            $msg .= " Terdapat catatan kendala: " . implode(', ', array_slice($errors, 0, 3));
        }

        return redirect()->route('waka.users.index', ['role' => 'siswa'])->with('success', $msg);
    }

    /**
     * Deteksi kolom dan baris awal data siswa secara cerdas (mendukung header 1 baris & 2 baris gabungan)
     */
    private function detectStudentColumnsAndDataStart($rows)
    {
        $colMap = [
            'nama'     => false,
            'nisn'     => false,
            'kelas'    => false,
            'gender'   => false,
            'telepon'  => false,
            'email'    => false,
            'username' => false,
            'password' => false,
        ];
        $dataStartIndex = -1;
        $detectedClassInHeader = null;

        $maxScan = min(count($rows), 25);
        $bestScore = 0;

        for ($r = 0; $r < $maxScan; $r++) {
            $currentRow = $rows[$r] ?? [];
            $nextRow = $rows[$r + 1] ?? [];

            // Periksa apakah ada baris judul yang memuat info kelas (misal: "KELAS : X TKJ 1" atau "ROMBEL : XII RPL 2")
            $rowText = implode(' ', array_filter($currentRow));
            if (!$detectedClassInHeader && preg_match('/\b(kelas|rombel)\s*[:\s\-]\s*([a-z0-9\s\-]+)/i', $rowText, $cm)) {
                $detectedClassInHeader = trim($cm[2]);
            }

            // Siapkan kombinasi 2 baris (untuk tabel presensi dengan header bertingkat)
            $maxCols = max(count($currentRow), count($nextRow));
            $mergedRow = [];
            for ($c = 0; $c < $maxCols; $c++) {
                $v1 = trim((string)($currentRow[$c] ?? ''));
                $v2 = trim((string)($nextRow[$c] ?? ''));
                $mergedRow[$c] = trim($v1 . ' ' . $v2);
            }

            foreach ([$mergedRow, $currentRow] as $trialIndex => $testRow) {
                $tempMap = [
                    'nama'     => false,
                    'nisn'     => false,
                    'kelas'    => false,
                    'gender'   => false,
                    'telepon'  => false,
                    'email'    => false,
                    'username' => false,
                    'password' => false,
                ];

                foreach ($testRow as $cIdx => $val) {
                    $cClean = preg_replace('/[^a-z0-9\/]/', ' ', strtolower(trim((string)$val)));
                    $cClean = preg_replace('/\s+/', ' ', trim($cClean));

                    if (empty($cClean)) continue;

                    // Match Nama
                    if ($tempMap['nama'] === false && preg_match('/\b(nama siswa|nama peserta didik|nama lengkap|peserta didik|nama|siswa|name|student name)\b/i', $cClean)) {
                        if (!preg_match('/\b(ayah|ibu|orang tua|ortu|wali|guru|sekolah)\b/i', $cClean)) {
                            $tempMap['nama'] = $cIdx;
                        }
                    }
                    // Match NIS / NISN / No Induk
                    elseif ($tempMap['nisn'] === false && preg_match('/\b(nisn|nis|no induk|nomor induk|nipd|no siswa|identifier|id siswa)\b/i', $cClean)) {
                        if (!preg_match('/\b(urut|urt)\b/i', $cClean)) {
                            $tempMap['nisn'] = $cIdx;
                        }
                    }
                    // Match Kelas / Rombel
                    elseif ($tempMap['kelas'] === false && preg_match('/^(kelas|rombel|rombongan belajar|class)$/i', $cClean)) {
                        $tempMap['kelas'] = $cIdx;
                    }
                    // Match Gender / LP
                    elseif ($tempMap['gender'] === false && preg_match('/^(l\/p|lp|jk|jenis kelamin|gender|sex)$/i', $cClean)) {
                        $tempMap['gender'] = $cIdx;
                    }
                    // Match Telepon
                    elseif ($tempMap['telepon'] === false && preg_match('/\b(telepon|telp|hp|no hp|wa|no wa|whatsapp|phone)\b/i', $cClean)) {
                        $tempMap['telepon'] = $cIdx;
                    }
                    // Match Email
                    elseif ($tempMap['email'] === false && preg_match('/\b(email|surel|mail)\b/i', $cClean)) {
                        $tempMap['email'] = $cIdx;
                    }
                    // Match Username
                    elseif ($tempMap['username'] === false && preg_match('/^(username|user)$/i', $cClean)) {
                        $tempMap['username'] = $cIdx;
                    }
                    // Match Password
                    elseif ($tempMap['password'] === false && preg_match('/^(password|pass|kata sandi)$/i', $cClean)) {
                        $tempMap['password'] = $cIdx;
                    }
                }

                if ($tempMap['nama'] !== false) {
                    $score = 20;
                    if ($tempMap['nisn'] !== false)   $score += 50;
                    if ($tempMap['gender'] !== false) $score += 15;
                    if ($tempMap['kelas'] !== false)  $score += 15;

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $colMap = $tempMap;
                        $dataStartIndex = ($trialIndex === 0) ? ($r + 2) : ($r + 1);

                        // Jika sudah menemukan kombinasi lengkap (Nama + NISN), hentikan pencarian awal
                        if ($tempMap['nisn'] !== false) {
                            break 2;
                        }
                    }
                }
            }
        }

        // Fallback: jika setelah scan tetap belum dapat, cari baris apa saja yang memiliki teks nama
        if ($colMap['nama'] === false) {
            foreach ($rows as $r => $testRow) {
                if ($r > 20) break;
                foreach ($testRow as $cIdx => $val) {
                    $cClean = strtolower(trim((string)$val));
                    if (in_array($cClean, ['nama', 'name', 'nama siswa'])) {
                        $colMap['nama'] = $cIdx;
                        $dataStartIndex = $r + 1;
                        break 2;
                    }
                }
            }
        }

        return [
            'colMap'         => $colMap,
            'dataStartIndex' => $dataStartIndex,
            'detectedClass'  => $detectedClassInHeader,
        ];
    }

    /**
     * Pencocokan cerdas teks nama kelas ke id kelas database (mendukung preferensi Angkatan)
     */
    private function resolveClassIdFromText($text, $classes, $preferredAngkatan = null)
    {
        return $this->detectClassFromRowText($text, $classes, $preferredAngkatan);
    }

    /**
     * Deteksi nama kelas dari baris/teks sheet (mendukung header angkatan multi-jurusan)
     */
    private function detectClassFromRowText($rowText, $classes, $preferredAngkatan = null)
    {
        if (empty($rowText)) return null;

        $clean = str_ireplace(
            ['daftar', 'hadir', 'siswa', 'presensi', 'tahun', 'ajaran', 'semester', 'ganjil', 'genap', 'kompetensi', 'keahlian', 'program', 'kejuruan', '.xlsx', '.xls', '.csv', '.tsv', '.txt'],
            ' ',
            $rowText
        );
        $clean = preg_replace('/[-._\/]/', ' ', $clean);
        $clean = strtolower(preg_replace('/\s+/', ' ', trim($clean)));

        if (empty($clean)) return null;

        // Normalisasi angka arab ke romawi (10 -> x, 11 -> xi, 12 -> xii)
        $clean = preg_replace('/\b10\b/', 'x', $clean);
        $clean = preg_replace('/\b11\b/', 'xi', $clean);
        $clean = preg_replace('/\b12\b/', 'xii', $clean);

        // Kembangkan alias Kurikulum Merdeka
        $cleanWithAlias = str_ireplace(
            ['tjkt', 'pplg', 'mplb', 'otkp', 'akl'],
            ['tkj',  'rpl',  'mp',   'mp',   'ak'],
            $clean
        );

        $sortedClasses = $classes->toArray();
        uksort($sortedClasses, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        // 1. Ekstraksi spesifik jika ada kata "kelas : ..." atau "rombel : ..."
        if (preg_match('/\b(?:kelas|rombel)\s*[:\s\-]\s*([a-z0-9\s]+)/i', $rowText, $m)) {
            $rawExtracted = strtolower(trim(preg_replace('/[-._\/]/', ' ', $m[1])));
            $rawExtracted = preg_replace('/\b10\b/', 'x', $rawExtracted);
            $rawExtracted = preg_replace('/\b11\b/', 'xi', $rawExtracted);
            $rawExtracted = preg_replace('/\b12\b/', 'xii', $rawExtracted);
            $aliasExtracted = str_ireplace(['tjkt', 'pplg', 'mplb', 'otkp', 'akl'], ['tkj', 'rpl', 'mp', 'mp', 'ak'], $rawExtracted);

            $extractedCandidates = [$aliasExtracted, $rawExtracted];
            if (!empty($preferredAngkatan)) {
                $pref = strtolower($preferredAngkatan);
                $extractedCandidates[] = $pref . ' ' . $aliasExtracted;
                $extractedCandidates[] = $pref . ' ' . $rawExtracted;
            }

            foreach ($extractedCandidates as $eCand) {
                $noSpaceE = str_replace(' ', '', $eCand);
                foreach ($sortedClasses as $cName => $id) {
                    $noSpaceC = str_replace(' ', '', $cName);
                    if ($noSpaceC === $noSpaceE || str_starts_with($noSpaceE, $noSpaceC) || str_contains($noSpaceE, $noSpaceC)) {
                        return $id;
                    }
                }
            }
        }

        // 2. Direct search di seluruh baris teks
        $directCandidates = [$cleanWithAlias, $clean];
        if (!empty($preferredAngkatan)) {
            $pref = strtolower($preferredAngkatan);
            $directCandidates[] = $pref . ' ' . $cleanWithAlias;
            $directCandidates[] = $pref . ' ' . $clean;
        }

        foreach ($directCandidates as $cand) {
            $noSpaceCand = str_replace(' ', '', $cand);
            foreach ($sortedClasses as $cName => $id) {
                $noSpaceC = str_replace(' ', '', $cName);
                if (preg_match('/\b' . preg_quote($cName, '/') . '\b/i', $cand) || str_contains($noSpaceCand, $noSpaceC)) {
                    return $id;
                }
            }
        }

        return null;
    }

    /**
     * Mesin Ekstraksi Adaptif Siswa: Mendukung format presensi multi-kelas bertumpuk,
     * buku nilai tidak terstruktur, maupun template CSV standar secara otomatis.
     */
    private function extractStudentsFromRows(array $rows, $classes, $preferredAngkatan = null, $sheetClassId = null)
    {
        $students = [];
        $matchedClasses = [];
        $currentClassId = $sheetClassId;

        $forbiddenPhrases = [
            'daftar hadir', 'tahun pelajaran', 'tahun ajaran', 'mata pelajaran',
            'guru mata pelajaran', 'semester', 'jumlah siswa', 'nomor', 'nama siswa',
            'nama peserta didik', 'peserta didik', 'urt', 'nis', 'nisn', 'l / p', 'l/p',
            'pertemuan ke', 'guru', 'wali kelas', 'kepala sekolah', 'bantul', 'nip.', 'nip ',
            'jumlah', 'total', 'keterangan', 'catatan', 'rekap', 'hadir', 'sakit', 'izin', 'alpa'
        ];

        foreach ($rows as $rIdx => $row) {
            if (empty(array_filter($row))) continue;

            $fullRowText = implode(' ', array_filter($row));
            $fullRowClean = strtolower(trim(preg_replace('/\s+/', ' ', $fullRowText)));

            // 1. Cek apakah baris ini memuat judul / pergantian kelas (misal "KELAS : XII RPL 1", "KELAS : X TKJ 2")
            if (preg_match('/\b(?:kelas|rombel)\s*[:\s\-]\s*([a-z0-9\s]+)/i', $fullRowText, $cm)) {
                $rawClassText = trim(preg_replace('/[,;].*$/', '', $cm[1]));
                $foundClassId = $this->detectClassFromRowText($rawClassText, $classes, $preferredAngkatan);
                if ($foundClassId) {
                    $currentClassId = $foundClassId;
                    $cName = SchoolClass::find($currentClassId)?->name;
                    if ($cName) $matchedClasses[$cName] = true;
                    continue; // Baris nama kelas dilewati (bukan siswa)
                }
            }

            if (count(array_filter($row)) <= 3) {
                $foundClassId = $this->detectClassFromRowText($fullRowClean, $classes, $preferredAngkatan);
                if ($foundClassId) {
                    $currentClassId = $foundClassId;
                    $cName = SchoolClass::find($currentClassId)?->name;
                    if ($cName) $matchedClasses[$cName] = true;
                    continue;
                }
            }

            // 2. Filter baris metadata / header tabel / tanda tangan guru
            $isMetadata = false;
            foreach ($forbiddenPhrases as $fp) {
                if (str_starts_with($fullRowClean, $fp) || str_contains($fullRowClean, $fp)) {
                    $hasNis = false;
                    foreach ($row as $ci => $cv) {
                        if ($ci <= 3 && preg_match('/^\d{4,8}$/', trim((string)$cv))) {
                            $hasNis = true;
                            break;
                        }
                    }
                    if (!$hasNis) {
                        $isMetadata = true;
                        break;
                    }
                }
            }
            if ($isMetadata) {
                continue;
            }

            // Filter garis titik-titik tanda tangan (misal "...................")
            if (preg_match('/^[\.\s\-_,]{3,}$/', $fullRowClean)) {
                continue;
            }

            // 3. Ekstraksi Field Siswa: NIS, Nama, Gender, Telepon, Kelas
            $nis = null;
            $nama = null;
            $gender = null;
            $rowClassId = null;

            $c0 = trim((string)($row[0] ?? ''));
            $c1 = trim((string)($row[1] ?? ''));
            $c2 = trim((string)($row[2] ?? ''));
            $c3 = trim((string)($row[3] ?? ''));

            // Pola 1: Presensi Asli Sekolah (Col 0 = No Urut 1..50, Col 1 = NIS 4..8 digit, Col 2 = Nama Siswa)
            if (preg_match('/^\d{1,3}$/', $c0) && preg_match('/^\d{4,8}$/', $c1) && !empty($c2) && strlen(preg_replace('/[^a-zA-Z]/', '', $c2)) >= 2) {
                $nis = $c1;
                $nama = $c2;
                $gender = in_array(strtoupper($c3), ['L', 'P']) ? strtoupper($c3) : null;
            }
            // Pola 2: Template CSV Resmi (Col 0 = Nama Siswa, Col 1 = NIS 4..8 digit, Col 2 = Kelas)
            elseif (!preg_match('/^\d+$/', $c0) && preg_match('/^\d{4,8}$/', $c1) && strlen(preg_replace('/[^a-zA-Z]/', '', $c0)) >= 2) {
                $nama = $c0;
                $nis = $c1;
                if (!empty($c2)) {
                    $rowClassId = $this->detectClassFromRowText($c2, $classes, $preferredAngkatan);
                }
            }
            // Pola 3: Format Col 0 = NIS, Col 1 = Nama
            elseif (preg_match('/^\d{4,8}$/', $c0) && !empty($c1) && strlen(preg_replace('/[^a-zA-Z]/', '', $c1)) >= 2) {
                $nis = $c0;
                $nama = $c1;
                $gender = in_array(strtoupper($c2), ['L', 'P']) ? strtoupper($c2) : null;
            }
            // Pola 4: Fallback pencarian adaptif di setiap sel baris
            else {
                foreach ($row as $ci => $cVal) {
                    $cValTrim = trim((string)$cVal);
                    if (empty($cValTrim)) continue;

                    if (!$nis && preg_match('/^\d{4,10}$/', $cValTrim)) {
                        if (!in_array($cValTrim, range(1, 50))) {
                            $nis = $cValTrim;
                            continue;
                        }
                    }

                    if (!$gender && in_array(strtoupper($cValTrim), ['L', 'P', 'LAKI-LAKI', 'PEREMPUAN'])) {
                        $gender = strtoupper(substr($cValTrim, 0, 1));
                        continue;
                    }

                    if (!$nama && strlen(preg_replace('/[^a-zA-Z]/', '', $cValTrim)) >= 3) {
                        $isForbidden = false;
                        $valLower = strtolower($cValTrim);
                        foreach ($forbiddenPhrases as $fp) {
                            if (str_contains($valLower, $fp)) {
                                $isForbidden = true;
                                break;
                            }
                        }
                        if (!$isForbidden) {
                            $nama = $cValTrim;
                        }
                    }
                }
            }

            if (empty($nama)) {
                continue;
            }

            // Validasi nama siswa: pastikan bukan teks judul metadata yang tersisa
            $namaLower = strtolower($nama);
            $rejectNama = false;
            foreach (['mata pelajaran', 'tahun pelajaran', 'tahun ajaran', 'guru mata', 'daftar hadir', 'jumlah siswa', 'semester'] as $badWord) {
                if (str_contains($namaLower, $badWord)) {
                    $rejectNama = true;
                    break;
                }
            }
            if ($rejectNama) {
                continue;
            }

            // Bersihkan nomor urut di awal nama jika ada (misal "1. ABID" -> "ABID")
            $nama = trim(preg_replace('/^[0-9]+[\.\-\s]+/', '', $nama));

            $effectiveClassId = $rowClassId ?: $currentClassId;
            if ($effectiveClassId) {
                $cName = SchoolClass::find($effectiveClassId)?->name;
                if ($cName) $matchedClasses[$cName] = true;
            }

            $students[] = [
                'row'        => $rIdx + 1,
                'nis'        => $nis,
                'name'       => $nama,
                'gender'     => $gender,
                'class_id'   => $effectiveClassId,
                'class_name' => SchoolClass::find($effectiveClassId)?->name,
            ];
        }

        return [
            'students'       => $students,
            'matchedClasses' => array_keys($matchedClasses),
        ];
    }

    /**
     * Download template CSV/Excel untuk import data Guru Pegawai
     */
    public function downloadTeacherTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_guru_pegawai.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar rapi & otomatis terkolom di Microsoft Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header kolom
            fputcsv($handle, ['No', 'Nama Guru / Pegawai', 'NIP / NUPTK', 'Email', 'No Telepon'], ';');

            // Contoh data
            fputcsv($handle, ['1', 'Budi Santoso, M.Kom', '198201012005011002', 'budi.santoso@sekolah.com', '081234567890'], ';');
            fputcsv($handle, ['2', 'Nurul Fatimah, S.Pd', '198705122010012005', 'nurul.fatimah@sekolah.com', '081234567891'], ';');
            fputcsv($handle, ['3', 'RUSDIYANTO, S.Pd', '57', 'rusdiyanto@sekolah.com', '081234567892'], ';');
            fputcsv($handle, ['4', 'Ahmad Dahlan, S.Pd', '199003152015031003', 'ahmad.dahlan@sekolah.com', '081234567893'], ';');

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Helper untuk membersihkan dan menstandarkan nama guru untuk pencocokan data
     */
    public static function cleanTeacherNameForMatching($name)
    {
        if (!$name) return '';
        // 1. Bersihkan angka di depan nama (misal "12. Budi" -> "Budi")
        $str = preg_replace('/^[0-9]+[\.\s\-]+/', '', (string)$name);
        // 2. Lowercase
        $str = mb_strtolower(trim($str));
        // 3. Bersihkan tanda baca
        $str = str_replace([',', '.', '-', '_', '\'', '"', '`'], ' ', $str);
        // 4. Hapus gelar umum pendidikan & keagamaan di Indonesia
        $titles = [
            's pd i', 'm pd i', 's pd', 'm pd', 's t', 'm t', 's kom', 'm kom', 
            's ag', 'm ag', 's si', 'm si', 's e', 'm m', 's sos', 'm sos',
            's psy', 'm psy', 'dra', 'drs', 'dr', 'prof', 'gr', 'h', 'hj'
        ];
        foreach ($titles as $title) {
            $str = preg_replace('/\b' . preg_quote($title, '/') . '\b/i', ' ', $str);
        }
        // 5. Hapus semua spasi dan non-alphanumeric tersisa
        return preg_replace('/[^a-z0-9]/', '', $str);
    }

    /**
     * Menghasilkan username 1 suku kata / kata tunggal yang mudah diingat
     */
    public static function generateSingleWordUsername($name, $ignoreUserId = null, $inMemoryUsernames = [])
    {
        $str = preg_replace('/^[0-9]+[\.\s\-]+/', '', (string)$name);
        $str = str_replace([',', '.', '-', '_', '\'', '"', '`', '/', '\\', '(', ')'], ' ', strtolower($str));

        $titles = [
            's pd i', 'm pd i', 's pd', 'm pd', 's t', 'm t', 's kom', 'm kom', 
            's ag', 'm ag', 's si', 'm si', 's e', 'm m', 's sos', 'm sos', 'ss',
            's psy', 'm psy', 'dra', 'drs', 'dr', 'prof', 'gr', 'h', 'hj', 'spd', 'mpd',
            'st', 'skom', 'mkom', 'sag', 'mag', 'ssos', 'se', 'mm', 'spdkor', 'macc',
            'm si', 'msi', 's ip', 'sip'
        ];
        foreach ($titles as $t) {
            $str = preg_replace('/\b' . preg_quote($t, '/') . '\b/i', ' ', $str);
        }

        $str = preg_replace('/[^a-z\s]/', ' ', $str);
        $words = array_values(array_filter(explode(' ', trim($str)), function($w) {
            return strlen(trim($w)) >= 2;
        }));

        $base = !empty($words) ? $words[0] : 'user';

        $candidates = [$base];
        if (isset($words[1])) $candidates[] = $words[1];
        if (isset($words[2])) $candidates[] = $words[2];
        $candidates = array_values(array_unique($candidates));

        foreach ($candidates as $cand) {
            if (!empty($inMemoryUsernames) && in_array($cand, $inMemoryUsernames, true)) {
                continue;
            }
            $q = User::where('username', $cand);
            if ($ignoreUserId) {
                $q->where('id', '!=', $ignoreUserId);
            }
            if (!$q->exists()) {
                return $cand;
            }
        }

        // Fallback: kata pertama + angka
        $counter = 1;
        while (true) {
            $cand = $base . $counter;
            if (!empty($inMemoryUsernames) && in_array($cand, $inMemoryUsernames, true)) {
                $counter++;
                continue;
            }
            $q = User::where('username', $cand);
            if ($ignoreUserId) {
                $q->where('id', '!=', $ignoreUserId);
            }
            if (!$q->exists()) {
                return $cand;
            }
            $counter++;
        }
    }

    /**
     * Memproses import data Guru & Pegawai dari berbagai format file (Multi-File: Excel, CSV, TSV, PDF, TXT, JSON)
     */
    public function importTeachers(Request $request)
    {
        $uploadedFiles = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            $uploadedFiles = is_array($files) ? $files : [$files];
        } elseif ($request->hasFile('file')) {
            $uploadedFiles = [$request->file('file')];
        }

        if (empty($uploadedFiles)) {
            return redirect()->back()->with('error', 'Silakan pilih minimal 1 file (Excel, CSV, PDF, TXT, atau JSON) untuk diimport!');
        }

        $totalCreated   = 0;
        $totalUpdated   = 0;
        $processedFiles = [];
        $errors         = [];

        // Ambil semua guru untuk pencocokan cepat
        $allTeachers = User::whereIn('role', ['guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum'])->get();

        foreach ($uploadedFiles as $file) {
            if (!$file->isValid()) continue;

            $origName = $file->getClientOriginalName();
            $parsed = $this->parseAnyFile($file->getPathname(), $origName);
            $formatName = $parsed['format'];
            $rows = $parsed['rows'];

            if (empty($rows)) continue;

            $processedFiles[] = "{$origName} ({$formatName})";

            // 1. Deteksi Baris Header Kolom
            $colMap = [
                'nama'   => false,
                'nip'    => false,
                'email'  => false,
                'phone'  => false,
                'status' => false,
            ];

            $headerRowIndex = -1;

            foreach (array_slice($rows, 0, 20) as $rIdx => $r) {
                $rowCombined = strtoupper(implode(' ', array_filter($r)));
                if (preg_match('/^(PEMERINTAH|DINAS|KEMENTERIAN|SEKOLAH|SMK|SMA|SMP|DAFTAR|REKAP|LAPORAN|DATA PEGAWAI)\b/i', trim($rowCombined)) && !preg_match('/\b(NIP|NUPTK|NAMA)\b/i', trim($rowCombined))) {
                    continue;
                }

                $tempMap = [
                    'nama'   => false,
                    'nip'    => false,
                    'email'  => false,
                    'phone'  => false,
                    'status' => false,
                ];

                foreach ($r as $cIdx => $val) {
                    $cClean = preg_replace('/[^a-z0-9]/', ' ', strtolower(trim((string)$val)));
                    $cClean = preg_replace('/\s+/', ' ', trim($cClean));

                    if (empty($cClean)) continue;

                    // Hindari false match kolom sekolah, ruang, mapel, kelas (kecuali wali kelas)
                    if (preg_match('/\b(sekolah|ruang|mapel|pelajaran)\b/i', $cClean)) {
                        continue;
                    }
                    if (preg_match('/\b(kelas)\b/i', $cClean) && !preg_match('/\b(wali)\b/i', $cClean)) {
                        continue;
                    }

                    if ($tempMap['nama'] === false && preg_match('/^(nama|nama guru|nama pegawai|nama ptk|nama pendidik|nama wali|nama wali kelas|wali kelas|wali|nama lengkap|guru|pegawai|pendidik|ptk|name|full name)$/i', $cClean)) {
                        $tempMap['nama'] = $cIdx;
                    } elseif ($tempMap['nip'] === false && preg_match('/\b(nip|nuptk|nik|identifier|kode guru|id guru|kode|id|no induk)\b/i', $cClean)) {
                        $tempMap['nip'] = $cIdx;
                    } elseif ($tempMap['email'] === false && preg_match('/\b(email|surel|mail|e mail)\b/i', $cClean)) {
                        $tempMap['email'] = $cIdx;
                    } elseif ($tempMap['phone'] === false && preg_match('/\b(telepon|telp|no hp|nohp|hp|wa|whatsapp|phone|handphone|no telp)\b/i', $cClean)) {
                        $tempMap['phone'] = $cIdx;
                    } elseif ($tempMap['status'] === false && preg_match('/\b(status|kepegawaian|status ptk|pangkat|golongan)\b/i', $cClean)) {
                        $tempMap['status'] = $cIdx;
                    }
                }

                if ($tempMap['nama'] !== false || ($tempMap['nip'] !== false && count(array_filter($tempMap, fn($v) => $v !== false)) >= 2)) {
                    $headerRowIndex = $rIdx;
                    $colMap = $tempMap;
                    break;
                }
            }

            // Fallback cerdas jika baris header tidak memiliki nama eksplisit
            if ($headerRowIndex === -1) {
                $colScores = [];
                foreach (array_slice($rows, 0, 10) as $r) {
                    foreach ($r as $cIdx => $val) {
                        $valStr = trim((string)$val);
                        if (filter_var($valStr, FILTER_VALIDATE_EMAIL)) {
                            $colScores[$cIdx]['email'] = ($colScores[$cIdx]['email'] ?? 0) + 1;
                        }
                        $digits = preg_replace('/[^0-9]/', '', $valStr);
                        if (preg_match('/^(08|\+62|62)[0-9]{8,13}$/', $valStr) || (strlen($digits) >= 10 && strlen($digits) <= 13 && str_starts_with($digits, '08'))) {
                            $colScores[$cIdx]['phone'] = ($colScores[$cIdx]['phone'] ?? 0) + 1;
                        } elseif (strlen($digits) >= 16 && strlen($digits) <= 18) {
                            $colScores[$cIdx]['nip'] = ($colScores[$cIdx]['nip'] ?? 0) + 1;
                        }
                        if (preg_match('/\b(S\.Pd|M\.Pd|S\.T|S\.Kom|Dra|Drs|M\.M|S\.Ag)\b/i', $valStr) || (preg_match('/^[A-Za-z\s,\.]{4,50}$/', $valStr) && !is_numeric($valStr))) {
                            $colScores[$cIdx]['nama'] = ($colScores[$cIdx]['nama'] ?? 0) + 1;
                        }
                    }
                }

                foreach ($colScores as $cIdx => $types) {
                    arsort($types);
                    $bestType = key($types);
                    if ($colMap[$bestType] === false) {
                        $colMap[$bestType] = $cIdx;
                    }
                }

                if ($colMap['nama'] === false) {
                    $colMap['nama'] = isset($rows[0][1]) ? 1 : 0;
                }
                $dataRows = $rows;
            } else {
                $dataRows = array_slice($rows, $headerRowIndex + 1);
            }

            // 2. Proses Setiap Baris Data Guru
            foreach ($dataRows as $rIdx => $r) {
                if (empty(array_filter($r))) continue;

                $rawNama = ($colMap['nama'] !== false && isset($r[$colMap['nama']])) ? trim((string)$r[$colMap['nama']]) : '';
                $nama = trim(preg_replace('/^[0-9]+[\.\s\-]+/', '', $rawNama));

                if (empty($nama) || strlen($nama) < 2) {
                    continue;
                }

                // Abaikan baris ringkasan / tanda tangan
                $upperNama = strtoupper($nama);
                if (preg_match('/^(TOTAL|JUMLAH|KEPALA SEKOLAH|MENGETAHUI|DAFTAR|REKAPITULASI|TANGGAL|NAMA GURU|NIP|NO)\b/i', $upperNama)) {
                    continue;
                }

                // Abaikan jika baris data adalah nama ruangan, laboratorium, bengkel atau fasilitas
                if (preg_match('/^(LAB|LABORATORIUM|RUANG|BENGKEL|PERPUSTAKAAN|AULA|KANTIN|GUDANG|LAPANGAN|R\.\s*[0-9]|R\s*[0-9])\b/i', $upperNama)) {
                    continue;
                }

                $rawNip = ($colMap['nip'] !== false && isset($r[$colMap['nip']])) ? trim((string)$r[$colMap['nip']]) : '';
                $nip = trim($rawNip, " \t\n\r\0\x0B'\"");
                if ($nip === '-' || $nip === '0' || strtolower($nip) === 'null' || empty($nip)) {
                    $nip = null;
                }

                $rawEmail = ($colMap['email'] !== false && isset($r[$colMap['email']])) ? trim((string)$r[$colMap['email']]) : '';
                $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL) ? strtolower($rawEmail) : null;

                $rawPhone = ($colMap['phone'] !== false && isset($r[$colMap['phone']])) ? trim((string)$r[$colMap['phone']]) : '';
                $phone = preg_replace('/[^0-9\+]/', '', $rawPhone);
                if (empty($phone) || $phone === '-') {
                    $phone = null;
                }

                // 3. Pencocokan Guru yang Sudah Ada
                $existingUser = null;

                // A. Berdasarkan NIP / Identifier
                if ($nip) {
                    $existingUser = $allTeachers->firstWhere('identifier', $nip);
                    if (!$existingUser) {
                        $existingUser = User::where('identifier', $nip)->first();
                    }
                }

                // B. Berdasarkan Email
                if (!$existingUser && $email) {
                    $existingUser = $allTeachers->firstWhere('email', $email);
                    if (!$existingUser) {
                        $existingUser = User::where('email', $email)->first();
                    }
                }

                // C. Berdasarkan Nama Lengkap (Exact Case-Insensitive)
                if (!$existingUser) {
                    $lowerNama = strtolower(trim($nama));
                    $existingUser = $allTeachers->first(function ($t) use ($lowerNama) {
                        return strtolower(trim($t->name)) === $lowerNama;
                    });
                }

                // D. Berdasarkan Nama Bersih (Tanpa Gelar Akademik)
                if (!$existingUser) {
                    $cleanTarget = $this->cleanTeacherNameForMatching($nama);
                    if (strlen($cleanTarget) >= 4) {
                        $existingUser = $allTeachers->first(function ($t) use ($cleanTarget) {
                            return $this->cleanTeacherNameForMatching($t->name) === $cleanTarget;
                        });
                    }
                }

                try {
                    if ($existingUser) {
                        $updated = false;
                        if ($nip && $existingUser->identifier !== $nip) {
                            $existingUser->identifier = $nip;
                            $updated = true;
                        }
                        if ($phone && (empty($existingUser->phone) || $existingUser->phone !== $phone)) {
                            $existingUser->phone = $phone;
                            $updated = true;
                        }
                        if ($email && $existingUser->email !== $email) {
                            $emailExists = User::where('email', $email)->where('id', '!=', $existingUser->id)->exists();
                            if (!$emailExists) {
                                $existingUser->email = $email;
                                $updated = true;
                            }
                        }
                        if (!in_array($existingUser->role, ['guru', 'wali_kelas', 'guru_bk', 'waka_kurikulum'])) {
                            $existingUser->role = 'guru';
                            $updated = true;
                        }
                        if ($updated) {
                            $existingUser->save();
                        }
                        $totalUpdated++;
                    } else {
                        // Buat Akun Guru Baru dengan Username 1 Suku Kata
                        $username = self::generateSingleWordUsername($nama);

                        if (empty($email)) {
                            $baseEmail = $username . '@sekolah.com';
                            $email = $baseEmail;
                            $eCounter = 1;
                            while (User::where('email', $email)->exists()) {
                                $email = $username . $eCounter . '@sekolah.com';
                                $eCounter++;
                            }
                        }

                        $userData = [
                            'name'       => $nama,
                            'username'   => $username,
                            'email'      => $email,
                            'password'   => Hash::make('password123'),
                            'role'       => 'guru',
                            'identifier' => $nip,
                            'phone'      => $phone,
                        ];

                        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'status')) {
                            $userData['status'] = 'aktif';
                        }

                        $newUser = User::create($userData);
                        $allTeachers->push($newUser);
                        $totalCreated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($rIdx + 1) . " ($nama): " . $e->getMessage();
                }
            }
        }

        $msg = "Import data guru & pegawai berhasil! {$totalCreated} guru baru ditambahkan, {$totalUpdated} data guru diperbarui.";
        if (!empty($processedFiles)) {
            $msg .= " File diproses: " . implode(', ', $processedFiles) . ".";
        }
        if (!empty($errors)) {
            $msg .= " Catatan kendala: " . implode('; ', array_slice($errors, 0, 3));
        }

        return redirect()->route('waka.users.index', ['role' => 'guru'])->with('success', $msg);
    }

    /**
     * Membaca isi file spreadsheet (.xlsx dan .csv) secara native tanpa library eksternal
     */
    private function parseSpreadsheetRows($filePath, $extension)
    {
        $rows = [];

        // 1. Parsing file .xlsx (OpenXML Zip)
        if (in_array($extension, ['xlsx', 'xls']) && class_exists('\ZipArchive')) {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $sharedStrings = [];
                $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
                if ($sharedStringsXml) {
                    $xmlStrings = simplexml_load_string($sharedStringsXml);
                    if ($xmlStrings && isset($xmlStrings->si)) {
                        foreach ($xmlStrings->si as $si) {
                            $sharedStrings[] = (string)($si->t ?? (isset($si->r) ? $si->r->t : ''));
                        }
                    }
                }

                $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
                if ($sheetXml) {
                    $xmlSheet = simplexml_load_string($sheetXml);
                    if ($xmlSheet && isset($xmlSheet->sheetData->row)) {
                        foreach ($xmlSheet->sheetData->row as $r) {
                            $cellValues = [];
                            foreach ($r->c as $c) {
                                $val = (string)($c->v ?? '');
                                $t = (string)($c['t'] ?? '');
                                if ($t === 's' && isset($sharedStrings[(int)$val])) {
                                    $val = $sharedStrings[(int)$val];
                                }
                                $cellValues[] = trim($val);
                            }
                            if (!empty(array_filter($cellValues))) {
                                $rows[] = $cellValues;
                            }
                        }
                    }
                }
                $zip->close();
                if (!empty($rows)) {
                    return $rows;
                }
            }
        }

        // 2. Parsing file .csv dengan auto-detect delimiter (; atau , atau tab)
        if (($handle = fopen($filePath, 'r')) !== false) {
            $firstLine = fgets($handle);
            rewind($handle);

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

            while (($data = fgetcsv($handle, 2000, $bestDelim)) !== false) {
                if (!empty(array_filter($data))) {
                    $rows[] = $data;
                }
            }
            fclose($handle);
        }

        return $rows;
    }
}