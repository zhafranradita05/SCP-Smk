<?php

/**
 * Ekstraktor teks dan koordinat PDF matriks ruangan
 */
function parseRoomMatrixPdf($filePath) {
    $content = @file_get_contents($filePath);
    if (!$content || !preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
        return [];
    }

    $cMap = [];
    foreach ($matches[1] as $s) {
        $dec = @gzuncompress($s);
        if ($dec === false) $dec = @gzinflate($s);
        if ($dec === false && strlen($s) > 2) $dec = @gzinflate(substr($s, 2));
        if ($dec === false) continue;
        if (str_contains($dec, 'beginbfchar') || str_contains($dec, 'beginbfrange')) {
            if (preg_match_all('/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/', $dec, $bf, PREG_SET_ORDER)) {
                foreach ($bf as $m) $cMap[hexdec($m[1])] = mb_chr(hexdec($m[2]), 'UTF-8');
            }
            if (preg_match_all('/<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>\s+<([0-9A-Fa-f]+)>/', $dec, $bfr, PREG_SET_ORDER)) {
                foreach ($bfr as $m) {
                    $st = hexdec($m[1]); $en = hexdec($m[2]); $dst = hexdec($m[3]);
                    for ($c = $st; $c <= $en; $c++) $cMap[$c] = mb_chr($dst + ($c - $st), 'UTF-8');
                }
            }
        }
    }

    $allPagesItems = [];

    foreach ($matches[1] as $s) {
        $dec = @gzuncompress($s);
        if ($dec === false) $dec = @gzinflate($s);
        if ($dec === false && strlen($s) > 2) $dec = @gzinflate(substr($s, 2));
        if ($dec === false || !str_contains($dec, 'BT')) continue;

        preg_match_all('/(?:(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+Tm|(\-?\d+(?:\.\d+)?)\s+(\-?\d+(?:\.\d+)?)\s+T[dD]|\[(.*?)\]\s*TJ|\((.*?)(?<!\\\\)\)\s*(?:Tj|\'|\")|<([0-9A-Fa-f]+)>\s*(?:Tj|\'|\"))/s', $dec, $tokens, PREG_SET_ORDER);

        $tlmX = 0; $tlmY = 0; $tmX = 0; $tmY = 0;
        $items = [];
        foreach ($tokens as $t) {
            if (isset($t[6]) && $t[6] !== '') {
                $tlmX = (float)$t[5]; $tlmY = (float)$t[6];
                $tmX = $tlmX; $tmY = $tlmY;
            } elseif (isset($t[8]) && $t[8] !== '') {
                $tlmX += (float)$t[7]; $tlmY += (float)$t[8];
                $tmX = $tlmX; $tmY = $tlmY;
            } elseif (!empty($t[9])) {
                preg_match_all('/(?:\((.*?)(?<!\\\\)\)|<([0-9A-Fa-f]+)>)/s', $t[9], $parts, PREG_SET_ORDER);
                $str = '';
                foreach ($parts as $p) {
                    if (!empty($p[1])) $str .= $p[1];
                    elseif (!empty($p[2])) {
                        for ($k = 0; $k < strlen($p[2]); $k += 4) {
                            $code = hexdec(substr($p[2], $k, 4));
                            $str .= $cMap[$code] ?? '';
                        }
                    }
                }
                if ($str !== '') $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $str];
            } elseif (isset($t[10]) && $t[10] !== '') {
                $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $t[10]];
            } elseif (isset($t[11]) && $t[11] !== '') {
                $str = '';
                for ($k = 0; $k < strlen($t[11]); $k += 4) {
                    $code = hexdec(substr($t[11], $k, 4));
                    $str .= $cMap[$code] ?? '';
                }
                if ($str !== '') $items[] = ['x' => $tmX, 'y' => $tmY, 'text' => $str];
            }
        }
        if (!empty($items)) {
            $allPagesItems[] = $items;
        }
    }

    return $allPagesItems;
}

function testAllDaysAllPages($filePath) {
    echo "=======================================================\n";
    echo "Testing All Pages on: " . basename($filePath) . "\n";
    echo "=======================================================\n";

    $pages = parseRoomMatrixPdf($filePath);
    $allLookup = [];

    $pageDays = [
        0 => ['Senin', 'Selasa'],
        1 => ['Rabu', 'Kamis'],
        2 => ['Jumat']
    ];

    $globalColMap = [];

    foreach ($pages as $pIdx => $pItems) {
        if (!isset($pageDays[$pIdx])) continue;
        $possibleDays = $pageDays[$pIdx];
        $dayIndex = 0;
        $currentDay = $possibleDays[$dayIndex];

        $rows = [];
        foreach ($pItems as $it) {
            $placed = false;
            foreach ($rows as $y => &$grp) {
                if (abs((float)$y - $it['y']) <= 3.0) {
                    $grp[] = $it; $placed = true; break;
                }
            }
            unset($grp);
            if (!$placed) $rows[sprintf('%.1f', $it['y'])] = [$it];
        }
        krsort($rows, SORT_NUMERIC);

        // Find top header
        $headerY = null;
        foreach ($rows as $y => $grp) {
            $rowStr = implode(' ', array_map(fn($it) => trim($it['text']), $grp));
            if (preg_match('/\bHari\b/i', $rowStr) && preg_match('/\bJam\b/i', $rowStr)) {
                $headerY = (float)$y;
                break;
            }
        }
        if (!$headerY) continue;

        // Collect header tokens
        $headerTokens = [];
        foreach ($pItems as $it) {
            if ($it['y'] >= $headerY - 15.0 && $it['y'] <= $headerY + 25.0 && $it['x'] > 100) {
                $headerTokens[] = $it;
            }
        }
        usort($headerTokens, fn($a, $b) => $a['x'] <=> $b['x']);
        $colCenters = [];
        foreach ($headerTokens as $t) {
            $found = false;
            foreach ($colCenters as &$c) {
                if (abs($c['x'] - $t['x']) <= 6.0) {
                    $c['tokens'][] = $t;
                    $sum = array_sum(array_column($c['tokens'], 'x'));
                    $c['x'] = $sum / count($c['tokens']);
                    $found = true;
                    break;
                }
            }
            if (!$found) $colCenters[] = ['x' => $t['x'], 'tokens' => [$t]];
        }

        $pageColMap = [];
        foreach ($colCenters as $c) {
            $tokTexts = array_map(fn($t) => trim($t['text']), $c['tokens']);
            $grade = null; $major = null; $rombel = '';
            foreach ($tokTexts as $tt) {
                if (in_array($tt, ['10', '11', '12'])) {
                    $grade = $tt === '10' ? 'X' : ($tt === '11' ? 'XI' : 'XII');
                }
                if (preg_match('/^(RPL|TKJ|DKV|AK|LPS|MP|BR|BD)\s*([0-9]*)$/i', $tt, $mm)) {
                    $major = strtoupper($mm[1]);
                    if ($mm[2] !== '') $rombel = $mm[2];
                }
                if (is_numeric($tt) && (int)$tt >= 1 && (int)$tt <= 4 && $tt !== '10' && $tt !== '11' && $tt !== '12') {
                    $rombel = $tt;
                }
            }
            if ($grade && $major) {
                $clsName = trim("$grade $major $rombel");
                $pageColMap[] = [
                    'x' => $c['x'],
                    'class' => $clsName,
                    'key' => strtolower(str_replace(' ', '', $clsName))
                ];
            }
        }

        $activeColMap = !empty($pageColMap) ? $pageColMap : $globalColMap;
        if (!empty($pageColMap)) $globalColMap = $pageColMap;

        $lastPeriod = 0;

        foreach ($rows as $y => $grp) {
            if ((float)$y >= $headerY - 10.0) continue;
            usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);

            // Check subheader
            $headerCheck = array_filter($grp, fn($it) => $it['x'] < 110 && preg_match('/\bHari\b/i', $it['text']));
            if (!empty($headerCheck)) {
                if (isset($possibleDays[$dayIndex + 1])) {
                    $dayIndex++;
                    $currentDay = $possibleDays[$dayIndex];
                    $lastPeriod = 0;
                }
                continue;
            }

            // Check period token strictly at x between 45 and 63
            $periodTokens = array_filter($grp, fn($it) => $it['x'] >= 45 && $it['x'] <= 63);
            $periodNum = null;
            foreach ($periodTokens as $pt) {
                $txt = trim($pt['text']);
                if (is_numeric($txt) && (int)$txt >= 1 && (int)$txt <= 10) {
                    $periodNum = (int)$txt;
                    break;
                }
            }
            if (!$periodNum) continue;

            if ($lastPeriod >= 7 && $periodNum <= 2 && isset($possibleDays[$dayIndex + 1])) {
                $dayIndex++;
                $currentDay = $possibleDays[$dayIndex];
            }
            $lastPeriod = $periodNum;

            foreach ($grp as $it) {
                if ($it['x'] < 105) continue;
                $val = trim($it['text']);
                if (empty($val) || $val === '-' || $val === '---') continue;

                $bestCol = null;
                $minDist = 999;
                foreach ($activeColMap as $c) {
                    $dist = abs($c['x'] - $it['x']);
                    if ($dist < $minDist && $dist <= 8.0) {
                        $minDist = $dist;
                        $bestCol = $c;
                    }
                }

                if ($bestCol) {
                    $clsKey = $bestCol['key'];
                    $allLookup[$currentDay][$periodNum][$clsKey] = $val;
                }
            }
        }
    }

    echo "Results for " . basename($filePath) . ":\n";
    foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d) {
        $pCount = isset($allLookup[$d]) ? count($allLookup[$d]) : 0;
        $cellCount = 0;
        if (isset($allLookup[$d])) {
            foreach ($allLookup[$d] as $p => $cls) $cellCount += count($cls);
        }
        echo "  $d: $pCount periods, $cellCount room mappings\n";
    }
}

testAllDaysAllPages(__DIR__ . '/Jadwal & ruangan/JADWAL_RUANG_PUT 1_REVISI_SEPT.pdf');
testAllDaysAllPages(__DIR__ . '/Jadwal & ruangan/JADWAL RUANGAN PUT 2 GANJIL 26_27 OK.pdf');

