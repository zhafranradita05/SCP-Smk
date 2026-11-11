<?php
$content = file_get_contents(__DIR__ . '/Dokumen/ukk_scp (1).sql');
if (!$content) {
    $content = file_get_contents(__DIR__ . '/Dokumen/ukk_scp_hosting_ready.sql');
}

// Columns: id, teacher_id, class_id, room_id, subject, cycle, day, start_time, end_time
preg_match_all("/\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)',\s*'([^']*)'/", $content, $schedRows, PREG_SET_ORDER);

preg_match_all("/\((\d+),\s*'([^']+)',\s*(\d+|NULL),\s*(\d+|NULL)/", $content, $classMatches, PREG_SET_ORDER);
$classes = [];
foreach ($classMatches as $cm) {
    $classes[(int)$cm[1]] = $cm[2];
}

$classSchedules = [];
foreach ($schedRows as $r) {
    $classId = (int)$r[3];
    $cycle = $r[6];
    $day = $r[7];
    if (!isset($classSchedules[$classId])) {
        $classSchedules[$classId] = ['putaran_1' => 0, 'putaran_2' => 0, 'days' => []];
    }
    $classSchedules[$classId][$cycle]++;
    $classSchedules[$classId]['days'][$day] = true;
}

echo "=== CEK JADWAL PER KELAS ===\n";
$missingPutaran1 = [];
$missingPutaran2 = [];
$missingAll = [];

foreach ($classes as $id => $name) {
    $p1 = $classSchedules[$id]['putaran_1'] ?? 0;
    $p2 = $classSchedules[$id]['putaran_2'] ?? 0;
    echo sprintf("Kelas [%-2d] %-10s -> P1: %-3d | P2: %-3d\n", $id, $name, $p1, $p2);
    if ($p1 == 0 && $p2 == 0) {
        $missingAll[] = "$name (ID: $id)";
    } elseif ($p1 == 0) {
        $missingPutaran1[] = "$name (ID: $id)";
    } elseif ($p2 == 0) {
        $missingPutaran2[] = "$name (ID: $id)";
    }
}

echo "\nKelas tanpa jadwal sama sekali: " . count($missingAll) . " -> " . implode(', ', $missingAll) . "\n";
echo "Kelas tanpa Putaran 1: " . count($missingPutaran1) . " -> " . implode(', ', $missingPutaran1) . "\n";
echo "Kelas tanpa Putaran 2: " . count($missingPutaran2) . " -> " . implode(', ', $missingPutaran2) . "\n";

