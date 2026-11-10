<?php

$f1 = __DIR__ . '/Jadwal & ruangan/JADWAL KELAS PUT 1.pdf';
$f2 = __DIR__ . '/Jadwal & ruangan/JADWAL KELAS PUT 2 SEM GANJIL 26_27 REVISI.pdf';

function dumpClassPdf($filePath, $limit = 40) {
    echo "=== DUMPING $filePath ===\n";
    $content = file_get_contents($filePath);
    preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches);

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

    $streamCount = 0;
    foreach ($matches[1] as $streamIdx => $s) {
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

        $rows = [];
        foreach ($items as $it) {
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

        echo "--- Page / Stream $streamIdx ---\n";
        $count = 0;
        foreach ($rows as $y => $grp) {
            usort($grp, fn($a, $b) => $a['x'] <=> $b['x']);
            $lineTexts = [];
            foreach ($grp as $it) {
                $lineTexts[] = sprintf("[x=%.1f]%s", $it['x'], $it['text']);
            }
            echo "Y=$y: " . implode(" ", $lineTexts) . "\n";
            if (++$count >= $limit) break;
        }

        $streamCount++;
        if ($streamCount >= 3) break; // First 3 pages
    }
}

dumpClassPdf($f1, 30);

