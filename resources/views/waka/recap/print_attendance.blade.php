<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Daftar Hadir Siswa - {{ strtoupper($selectedSemester) }} {{ $selectedYear }}</title>
    <!-- Favicon SCP -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #2C1D11; padding: 20px; }
        .header { display: flex; align-items: center; justify-content: center; gap: 20px; border-bottom: 2px solid #2C1D11; padding-bottom: 12px; margin-bottom: 20px; }
        .header-logo { width: 70px; height: 70px; object-fit: contain; flex-shrink: 0; }
        .header-text { text-align: left; }
        .header-text h1 { margin: 0; font-size: 16px; text-transform: uppercase; font-weight: bold; }
        .header-text p { margin: 3px 0 0; font-size: 11px; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #795548; padding: 6px 8px; text-align: left; }
        th { background-color: #FAF7F2; font-weight: bold; font-size: 10.5px; }
        .text-center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; }
        .badge-hadir { background: #dcfce7; color: #166534; }
        .badge-sakit { background: #e0f2fe; color: #075985; }
        .badge-izin { background: #fef9c3; color: #854d0e; }
        .badge-alpa { background: #fee2e2; color: #991b1b; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 6px 16px; font-weight: bold; cursor: pointer; background: #2563eb; color: #fff; border: none; border-radius: 4px;">Cetak Sekarang</button>
    </div>

    <div class="header">
        <img src="{{ asset('images/logo.png') }}?v=2" alt="Logo SCP" class="header-logo">
        <div class="header-text">
            <h1>School Class Progress (SCP)</h1>
            <p style="font-size: 13px; font-weight: 600; color: #2C1D11;">Laporan Rekapitulasi Daftar Hadir Siswa</p>
            <p>Tahun Ajaran: <strong>{{ $selectedYear }}</strong> | Semester: <strong>{{ strtoupper($selectedSemester) }}</strong> @if($selectedClass) | Kelas: <strong>{{ $selectedClass->name }}</strong> @else | <strong>Semua Kelas</strong> @endif</p>
            <p>Periode: {{ $startDate->format('d F Y') }} s/d {{ $endDate->format('d F Y') }}</p>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
        <h3 style="margin: 0;">Rekapitulasi Kehadiran Siswa Per Sesi Pembelajaran</h3>
        <span style="font-size: 11px; color: #555;">Dicetak pada: {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y H:i') }}</span>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 80px;">NISN</th>
                <th>Nama Siswa</th>
                <th style="width: 80px;">Kelas</th>
                <th style="width: 50px;" class="text-center">Hadir</th>
                <th style="width: 50px;" class="text-center">Sakit</th>
                <th style="width: 50px;" class="text-center">Izin</th>
                <th style="width: 50px;" class="text-center">Alpa</th>
                <th style="width: 60px;" class="text-center">Total Sesi</th>
                <th style="width: 70px;" class="text-center">% Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse($studentAttendanceRecap as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td style="font-family: monospace;">{{ $item['nisn'] }}</td>
                <td><strong>{{ $item['name'] }}</strong></td>
                <td>{{ $item['class_name'] }}</td>
                <td class="text-center"><span class="badge badge-hadir">{{ $item['hadir'] }}</span></td>
                <td class="text-center"><span class="badge badge-sakit">{{ $item['sakit'] }}</span></td>
                <td class="text-center"><span class="badge badge-izin">{{ $item['izin'] }}</span></td>
                <td class="text-center"><span class="badge badge-alpa">{{ $item['alpa'] }}</span></td>
                <td class="text-center"><strong>{{ $item['total_pertemuan'] }}</strong></td>
                <td class="text-center" style="font-weight: bold; color: {{ $item['persentase'] >= 80 ? '#166534' : ($item['persentase'] >= 75 ? '#854d0e' : '#991b1b') }};">
                    {{ $item['persentase'] }}%
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 20px;">Belum ada data kehadiran siswa pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 35px; display: flex; justify-content: flex-end;">
        <div style="text-align: center; width: 220px;">
            <p>Bantul, {{ \Carbon\Carbon::now('Asia/Jakarta')->translatedFormat('d F Y') }}</p>
            <p style="margin-top: 4px; font-weight: bold;">Waka Kurikulum,</p>
            <div style="height: 60px;"></div>
            <p style="margin: 0; font-weight: bold; text-decoration: underline;">Waka Kurikulum SMKN 1 Bantul</p>
            <p style="margin: 2px 0 0 0; color: #555;">NIP. 198001012005011001</p>
        </div>
    </div>

</body>
</html>

