<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Izin Siswa - {{ $academicYear }} ({{ $semester }})</title>
    <!-- Favicon SCP -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: 'Times New Roman', Times, serif, Arial;
            font-size: 11pt;
            color: #111827;
            background-color: #f8fafc;
            margin: 0;
            padding: 20px;
        }

        /* Toolbar Layar (Tidak dicetak) */
        .toolbar {
            max-width: 900px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            font-family: Arial, sans-serif;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
        }

        .btn-primary {
            background-color: #1d4ed8;
            color: #ffffff;
        }
        .btn-primary:hover { background-color: #1e40af; }

        .btn-outline {
            background-color: transparent;
            border: 1px solid #94a3b8;
            color: #334155;
        }
        .btn-outline:hover { background-color: #f1f5f9; }

        .select-filter {
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
        }

        /* Lembar Dokumen Kertas */
        .page-sheet {
            max-width: 900px;
            margin: 0 auto 30px auto;
            background: #ffffff;
            padding: 40px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
        }

        /* KOP SURAT RESMI */
        .kop-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 18px;
            text-align: center;
        }

        .kop-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .kop-text h2 {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kop-text h1 {
            margin: 2px 0;
            font-size: 17pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0f172a;
        }

        .kop-text p {
            margin: 2px 0 0 0;
            font-size: 9.5pt;
            font-family: Arial, sans-serif;
            color: #334155;
        }

        /* JUDUL LAPORAN */
        .report-title-section {
            text-align: center;
            margin-bottom: 18px;
        }

        .report-title {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 0.5px;
        }

        .report-subtitle {
            margin: 4px 0 0 0;
            font-size: 10pt;
            font-weight: bold;
            color: #475569;
        }

        /* KOTAK METADATA KELAS */
        .meta-grid {
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin-bottom: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            border-radius: 6px;
        }

        .meta-grid table td {
            padding: 2px 6px;
            border: none;
        }

        /* STATISTIK RINGKAS */
        .stat-badges {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            font-family: Arial, sans-serif;
            font-size: 9.5pt;
        }

        .stat-box {
            flex: 1;
            padding: 8px 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            text-align: center;
        }

        .stat-box strong {
            display: block;
            font-size: 13pt;
            color: #0f172a;
            margin-top: 2px;
        }

        /* TABEL DATA REKAPITULASI */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9.5pt;
            font-family: Arial, sans-serif;
        }

        table.data-table th, table.data-table td {
            border: 1px solid #475569;
            padding: 6px 8px;
            vertical-align: middle;
        }

        table.data-table th {
            background-color: #e2e8f0;
            font-weight: bold;
            text-align: center;
            font-size: 9.5pt;
            color: #0f172a;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .badge-warning {
            display: inline-block;
            padding: 2px 6px;
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #f87171;
            border-radius: 4px;
            font-size: 8.5pt;
            font-weight: bold;
        }

        .badge-normal {
            display: inline-block;
            padding: 2px 6px;
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
            border-radius: 4px;
            font-size: 8.5pt;
            font-weight: bold;
        }

        /* TANDA TANGAN */
        .signature-section {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }

        .signature-box {
            width: 250px;
            text-align: center;
        }

        .signature-space {
            height: 65px;
        }

        /* PENGATURAN CETAK (PRINT & PDF) */
        @media print {
            body {
                background: none;
                padding: 0;
            }

            .toolbar {
                display: none !important;
            }

            .page-sheet {
                box-shadow: none;
                border: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }

            .page-break {
                page-break-after: always;
                break-after: page;
            }
        }
    </style>
</head>
<body onload="{{ request('auto_print') == '1' ? 'window.print()' : '' }}">

    <!-- 1. TOOLBAR LAYAR (TIDAK DICETAK) -->
    <div class="toolbar no-print">
        <div style="display: flex; align-items: center; gap: 12px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                <span>Cetak / Simpan PDF</span>
            </button>

            @if($isBK || $isWaka)
            <!-- Pilihan Kelas Khusus Guru BK & Waka -->
            <form method="GET" action="{{ route('leaves.recap.print') }}" style="margin: 0; display: inline-flex; align-items: center; gap: 8px;">
                @if(request('token'))
                    <input type="hidden" name="token" value="{{ request('token') }}">
                @endif
                <label for="class_id" style="font-size: 12px; font-weight: bold; color: #64748b;">Pilih Kelas:</label>
                <select name="class_id" id="class_id" onchange="this.form.submit()" class="select-filter">
                    <option value="all" {{ request('class_id', 'all') == 'all' ? 'selected' : '' }}>Semua Kelas (Dipisah per Halaman)</option>
                    @foreach($allClasses as $c)
                        <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>
            @endif
        </div>

        <div style="font-size: 12px; color: #64748b;">
            Masuk sebagai: <strong>{{ $user->name }}</strong> 
            <span style="display: inline-block; padding: 2px 8px; background: #e2e8f0; border-radius: 4px; font-weight: bold; text-transform: uppercase; font-size: 10px;">
                {{ $isWali ? 'Wali Kelas' : ($isBK ? 'Guru BK' : 'Waka') }}
            </span>
        </div>
    </div>

    <!-- 2. KONTEN DOKUMEN CETAK PER KELAS -->
    @forelse($classReports as $reportIndex => $rep)
    <div class="page-sheet {{ $reportIndex < count($classReports) - 1 ? 'page-break' : '' }}">

        <!-- KOP SURAT RESMI -->
        <div class="kop-wrapper">
            <img src="{{ asset('images/logo.png') }}" alt="Logo Sekolah" class="kop-logo">
            <div class="kop-text">
                <h2>PEMERINTAH DAERAH DAERAH ISTIMEWA YOGYAKARTA</h2>
                <h1>SMK NEGERI 1 BANTUL</h1>
                <p>Jalan Parangtritis Km. 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715 | Telp: (0274) 367156</p>
                <p>Website: smkn1bantul.sch.id • Email: info@smkn1bantul.sch.id • Sistem Presensi SCP</p>
            </div>
        </div>

        <!-- JUDUL DOKUMEN -->
        <div class="report-title-section">
            <h3 class="report-title">LAPORAN REKAPITULASI IZIN & PRESENSI SISWA</h3>
            <p class="report-subtitle">Tahun Ajaran {{ $academicYear }} — {{ $semester }}</p>
        </div>

        <!-- IDENTITAS KELAS & METADATA -->
        <div class="meta-grid">
            <table>
                <tr>
                    <td style="font-weight: bold; width: 110px;">Kelas</td>
                    <td>: <strong>{{ $rep['class']->name }}</strong></td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Jurusan</td>
                    <td>: {{ $rep['class']->major_full_name ?? $rep['class']->major ?? 'Kejuruan' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Wali Kelas</td>
                    <td>: {{ $rep['homeroom_teacher']->name ?? '-' }}</td>
                </tr>
            </table>

            <table>
                <tr>
                    <td style="font-weight: bold; width: 120px;">Semester</td>
                    <td>: {{ $semester }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tahun Ajaran</td>
                    <td>: {{ $academicYear }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tanggal Unduh</td>
                    <td>: {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}</td>
                </tr>
            </table>
        </div>

        <!-- STATISTIK RINGKAS KELAS -->
        <div class="stat-badges">
            <div class="stat-box">
                Total Siswa Terdaftar
                <strong>{{ $rep['total_students'] }} Siswa</strong>
            </div>
            <div class="stat-box">
                Total Sakit
                <strong style="color: #b45309;">{{ $rep['total_sakit'] }} Hari</strong>
            </div>
            <div class="stat-box">
                Total Izin
                <strong style="color: #1d4ed8;">{{ $rep['total_izin'] }} Hari</strong>
            </div>
            <div class="stat-box">
                Total Akumulasi
                <strong style="color: #475569;">{{ $rep['total_sakit'] + $rep['total_izin'] }} Hari</strong>
            </div>
            <div class="stat-box">
                Perlu Penanganan BK
                <strong style="color: {{ $rep['needs_attention_count'] > 0 ? '#dc2626' : '#16a34a' }};">
                    {{ $rep['needs_attention_count'] }} Siswa
                </strong>
            </div>
        </div>

        <!-- TABEL RINCIAN SISWA -->
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 90px;">NISN</th>
                    <th>Nama Siswa</th>
                    <th style="width: 50px;">Sakit</th>
                    <th style="width: 50px;">Izin</th>
                    <th style="width: 60px;">Total</th>
                    <th style="width: 95px;">Status Rapor</th>
                    <th>Riwayat & Keterangan Izin Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rep['students'] as $idx => $s)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center" style="font-family: monospace;">{{ $s['identifier'] }}</td>
                    <td class="font-bold">{{ $s['name'] }}</td>
                    <td class="text-center">{{ $s['sakit_days'] }} hr</td>
                    <td class="text-center">{{ $s['izin_days'] }} hr</td>
                    <td class="text-center font-bold">{{ $s['total_days'] }} hr</td>
                    <td class="text-center">
                        @if($s['needs_attention'])
                            <span class="badge-warning">Perlu BK</span>
                        @else
                            <span class="badge-normal">Normal</span>
                        @endif
                    </td>
                    <td style="font-size: 8.5pt; color: #334155;">{{ $s['notes'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #64748b;">
                        Belum ada siswa terdaftar pada kelas ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- TANDA TANGAN PENGESAHAN LAPORAN -->
        <div class="signature-section">
            <div class="signature-box">
                @if($isBK)
                    <p>Mengetahui,</p>
                    <p style="font-weight: bold;">Wali Kelas {{ $rep['class']->name }}</p>
                    <div class="signature-space"></div>
                    <p style="font-weight: bold; text-decoration: underline;">
                        {{ $rep['homeroom_teacher']->name ?? '( .............................................. )' }}
                    </p>
                    <p style="font-size: 9pt;">NIP: {{ $rep['homeroom_teacher']->identifier ?? '-' }}</p>
                @else
                    <p>&nbsp;</p>
                    <p>&nbsp;</p>
                    <div class="signature-space"></div>
                @endif
            </div>

            <div class="signature-box">
                <p>Bantul, {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}</p>
                @if($isWali)
                    <p style="font-weight: bold;">Wali Kelas {{ $rep['class']->name }},</p>
                    <div class="signature-space"></div>
                    <p style="font-weight: bold; text-decoration: underline;">
                        {{ $rep['homeroom_teacher']->name ?? $user->name }}
                    </p>
                    <p style="font-size: 9pt;">NIP: {{ $rep['homeroom_teacher']->identifier ?? $user->identifier ?? '-' }}</p>
                @elseif($isBK)
                    <p style="font-weight: bold;">Guru Bimbingan dan Konseling,</p>
                    <div class="signature-space"></div>
                    <p style="font-weight: bold; text-decoration: underline;">
                        {{ $user->name }}
                    </p>
                    <p style="font-size: 9pt;">NIP: {{ $user->identifier ?? '-' }}</p>
                @else
                    <p style="font-weight: bold;">Waka Kurikulum,</p>
                    <div class="signature-space"></div>
                    <p style="font-weight: bold; text-decoration: underline;">
                        {{ $user->name }}
                    </p>
                    <p style="font-size: 9pt;">NIP: {{ $user->identifier ?? '-' }}</p>
                @endif
            </div>
        </div>

    </div>
    @empty
    <div style="max-width: 600px; margin: 60px auto; text-align: center; background: white; padding: 30px; border-radius: 12px; border: 1px solid #cbd5e1;">
        <h3>Tidak Ada Data Kelas</h3>
        <p style="color: #64748b;">Data laporan perizinan tidak ditemukan untuk parameter yang dipilih.</p>
    </div>
    @endforelse

</body>
</html>

