<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Mengajar - {{ strtoupper($selectedSemester) }} {{ $selectedYear }}</title>
    <!-- Favicon SCP -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #2C1D11; padding: 20px; }
        .header { display: flex; align-items: center; justify-content: center; gap: 20px; border-bottom: 2px solid #2C1D11; padding-bottom: 12px; margin-bottom: 20px; }
        .header-logo { width: 70px; height: 70px; object-fit: contain; flex-shrink: 0; }
        .header-text { text-align: left; }
        .header-text h1 { margin: 0; font-size: 16px; text-transform: uppercase; font-weight: bold; }
        .header-text p { margin: 3px 0 0; font-size: 11px; color: #444; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .header p { margin: 4px 0 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #795548; padding: 6px 10px; text-align: left; }
        th { background-color: #FAF7F2; font-weight: bold; }
        .text-center { text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 6px 16px; font-weight: bold; cursor: pointer;">Cetak Sekarang</button>
    </div>

    <div class="header">
        <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="header-logo">
        <div class="header-text">
            <h1>School Class Progress (SCP)</h1>
            <p style="font-size: 13px; font-weight: 600; color: #2C1D11;">Laporan Rekapitulasi Pembelajaran & Kehadiran</p>
        <p>Tahun Ajaran: <strong>{{ $selectedYear }}</strong> | Semester: <strong>{{ strtoupper($selectedSemester) }}</strong></p>
        <p>Periode: {{ $startDate->format('d F Y') }} s/d {{ $endDate->format('d F Y') }}</p>
        </div>
    </div>

    <h3>Daftar Jurnal Mengajar Guru yang Terlaksana</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 80px;">Tanggal</th>
                <th>Nama Guru</th>
                <th>Mata Pelajaran</th>
                <th>Kelas</th>
                <th>Materi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($journals as $index => $j)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ \Carbon\Carbon::parse($j->date)->format('d/m/Y') }}</td>
                <td>{{ $j->teacher->name ?? '-' }}</td>
                <td>{{ $j->schedule->subject ?? '-' }}</td>
                <td>{{ $j->schedule->schoolClass->name ?? '-' }}</td>
                <td>{{ $j->materi }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada catatan jurnal pada semester ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 40px; float: right; width: 200px; text-align: center;">
        <p>{{ date('d F Y') }}</p>
        <p>Waka Kurikulum,</p>
        <br><br><br>
        <p><strong>( ........................................... )</strong></p>
    </div>

</body>
</html>
