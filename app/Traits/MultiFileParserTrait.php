<?php

namespace App\Traits;

trait MultiFileParserTrait
{
    /**
     * Deteksi dan parsing file dari berbagai format secara otomatis
     * Mendukung: PDF, Excel (.xlsx, .xls), CSV, TSV, TXT, JSON, HTML Table
     */
    protected function parseAnyFile($filePath, $originalName = '')
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $handle = @fopen($filePath, 'rb');
        $magic = $handle ? fread($handle, 1024) : '';
        if ($handle) fclose($handle);

        // 1. Format PDF
        if (str_starts_with($magic, '%PDF-') || $ext === 'pdf') {
            $rows = $this->parsePdfRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'PDF', 'rows' => $rows];
            }
        }

        // 2. Format Excel Modern (XLSX / ZIP XML)
        if (str_starts_with($magic, "PK\x03\x04") || in_array($ext, ['xlsx', 'xlsm', 'xltx'])) {
            $rows = $this->parseXlsxRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'Excel (.xlsx)', 'rows' => $rows];
            }
        }

        // 3. Format JSON
        $trimmedMagic = ltrim($magic);
        if (str_starts_with($trimmedMagic, '[') || str_starts_with($trimmedMagic, '{') || $ext === 'json') {
            $rows = $this->parseJsonRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'JSON', 'rows' => $rows];
            }
        }

        // 4. Format HTML / Tabel Web
        if (stripos($magic, '<table') !== false || stripos($magic, '<html') !== false || in_array($ext, ['html', 'htm'])) {
            $rows = $this->parseHtmlRows($filePath);
            if (!empty($rows)) {
                return ['format' => 'HTML Table / Excel Web', 'rows' => $rows];
            }
        }

        // 5. Format CSV / Delimited File (Comma, Semicolon, Tab, Pipe)
        $rows = $this->parseCsvRows($filePath);
        if (!empty($rows)) {
            $label = ($ext === 'tsv') ? 'TSV' : (($ext === 'txt') ? 'Text File' : 'CSV / Delimited');
            return ['format' => $label, 'rows' => $rows];
        }

        // 6. Generic Text Parsing Fallback
        $rawText = @file_get_contents($filePath);
        if ($rawText) {
            $rows = $this->convertRawTextToRows($rawText);
            if (!empty($rows)) {
                return ['format' => 'Teks Terstruktur', 'rows' => $rows];
            }
        }

        return ['format' => strtoupper($ext ?: 'Tidak Diketahui'), 'rows' => []];
    }

    /**
     * Parsing file dengan ekstraksi multi-sheet (khususnya untuk workbook Excel)
     * Mengembalikan array: ['format' => string, 'sheets' => [ ['name' => string, 'rows' => array], ... ]]
     */
    protected function parseAnyFileWithSheets($filePath, $originalName = '')
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $handle = @fopen($filePath, 'rb');
        $magic = $handle ? fread($handle, 1024) : '';
        if ($handle) fclose($handle);

        // 1. Format Excel Modern (XLSX) -> Ekstraksi seluruh sheet
        if (str_starts_with($magic, "PK\x03\x04") || in_array($ext, ['xlsx', 'xlsm', 'xltx'])) {
            $sheets = $this->parseXlsxSheets($filePath);
            if (!empty($sheets)) {
                return ['format' => 'Excel (.xlsx)', 'sheets' => $sheets];
            }
        }

        // 2. Format lainnya (PDF, CSV, TSV, HTML, JSON, TXT)
        $parsed = $this->parseAnyFile($filePath, $originalName);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        return [
            'format' => $parsed['format'],
            'sheets' => [
                [
                    'name' => $baseName,
                    'rows' => $parsed['rows']
                ]
            ]
        ];
    }

    /**
     * Parsing dokumen PDF murni PHP tanpa dependensi luar
     */
    protected function parsePdfRows($filePath)
    {
        $content = @file_get_contents($filePath);
        if (!$content) return [];

        $allText = '';

        // Ekstraksi seluruh stream biner yang ada dalam file PDF
        if (preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $decompressed = @gzuncompress($stream);
                if ($decompressed === false) {
                    $decompressed = @gzinflate($stream);
                }
                if ($decompressed === false && strlen($stream) > 2) {
                    $decompressed = @gzinflate(substr($stream, 2));
                }

                if ($decompressed !== false) {
                    $allText .= "\n" . $this->extractPdfTextTokens($decompressed);
                } else {
                    $allText .= "\n" . $this->extractPdfTextTokens($stream);
                }
            }
        }

        // Fallback jika ada teks uncompressed di luar stream
        $rawExtracted = $this->extractPdfTextTokens($content);
        if (strlen($rawExtracted) > strlen($allText)) {
            $allText .= "\n" . $rawExtracted;
        }

        return $this->convertRawTextToRows($allText);
    }

    /**
     * Ekstraksi token teks operator PDF (BT...ET, Tj, TJ, ', ")
     */
    protected function extractPdfTextTokens($stream)
    {
        $output = '';

        if (preg_match_all('/BT\s+(.*?)\s+ET/s', $stream, $btMatches)) {
            foreach ($btMatches[1] as $block) {
                $blockWithMarkers = preg_replace('/(\bT\*|(?:\-?\d+(?:\.\d+)?\s+\-?(?:[1-9]\d*|0\.\d+)\s+T[dD])|\r?\n)/', " [[NL]] ", $block);
                $subLines = explode('[[NL]]', $blockWithMarkers);

                foreach ($subLines as $subLine) {
                    $subLine = trim($subLine);
                    if ($subLine === '') continue;

                    $lineText = '';

                    if (preg_match_all('/\[(.*?)\]\s*TJ/s', $subLine, $tjMatches)) {
                        foreach ($tjMatches[1] as $tjContent) {
                            preg_match_all('/(?:\((.*?)(?<!\\\\)\)|(\-?\d+(?:\.\d+)?))/s', $tjContent, $tokens, PREG_SET_ORDER);
                            foreach ($tokens as $t) {
                                if (isset($t[1]) && $t[1] !== '') {
                                    $lineText .= $this->decodePdfString($t[1]) . ' ';
                                } elseif (isset($t[2]) && (float)$t[2] < -120) {
                                    $lineText .= "\t";
                                }
                            }
                        }
                    }

                    if (preg_match_all('/\((.*?)(?<!\\\\)\)\s*(?:Tj|\'|\")/s', $subLine, $simpleMatches)) {
                        foreach ($simpleMatches[1] as $str) {
                            $lineText .= $this->decodePdfString($str) . "\t";
                        }
                    }

                    $lineText = trim($lineText);
                    if ($lineText !== '') {
                        $output .= $lineText . "\n";
                    }
                }
            }
        }

        if (trim($output) === '') {
            if (preg_match_all('/\((.*?)(?<!\\\\)\)\s*(?:Tj|\'|\")/s', $stream, $matches)) {
                foreach ($matches[1] as $str) {
                    $output .= $this->decodePdfString($str) . "\n";
                }
            }
        }

        return $output;
    }

    /**
     * Decode escape sequence karakter string PDF
     */
    protected function decodePdfString($str)
    {
        $str = str_replace(
            ['\\n', '\\r', '\\t', '\\b', '\\f', '\\(', '\\)', '\\\\'],
            ["\n", "\r", "\t", "\x08", "\x0C", "(", ")", "\\"],
            $str
        );
        $str = preg_replace_callback('/\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);

        return trim($str);
    }

    /**
     * Mengubah teks mentah atau teks hasil ekstraksi PDF menjadi baris & kolom data
     */
    protected function convertRawTextToRows($rawText)
    {
        $rawLines = preg_split('/\r\n|\n|\r/', $rawText);
        $rows = [];
        $delimiters = [';', "\t", ',', '|'];

        foreach ($rawLines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $bestDelim = null;
            $maxParts = 1;
            foreach ($delimiters as $d) {
                $parts = str_getcsv($line, $d);
                if (count($parts) > $maxParts) {
                    $maxParts = count($parts);
                    $bestDelim = $d;
                }
            }

            if ($bestDelim !== null && $maxParts >= 2) {
                $row = array_map('trim', str_getcsv($line, $bestDelim));
            } else {
                $parts = preg_split('/\s{2,}|\t+/', $line);
                if (count($parts) >= 2) {
                    $row = array_map('trim', $parts);
                } else {
                    $row = [$line];
                }
            }

            if (!empty(array_filter($row))) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Parsing file JSON (Mendukung array of objects maupun array of arrays)
     */
    protected function parseJsonRows($filePath)
    {
        $content = @file_get_contents($filePath);
        if (!$content) return [];

        $data = json_decode($content, true);
        if (!is_array($data)) return [];

        if (isset($data['data']) && is_array($data['data'])) {
            $data = $data['data'];
        } elseif (isset($data['walikelas']) && is_array($data['walikelas'])) {
            $data = $data['walikelas'];
        } elseif (isset($data['ruangan']) && is_array($data['ruangan'])) {
            $data = $data['ruangan'];
        } elseif (isset($data['schedules']) && is_array($data['schedules'])) {
            $data = $data['schedules'];
        } elseif (isset($data['jadwal']) && is_array($data['jadwal'])) {
            $data = $data['jadwal'];
        }

        if (empty($data)) return [];

        $first = reset($data);
        if (is_array($first) && !isset($first[0])) {
            $headers = array_keys($first);
            $rows = [$headers];
            foreach ($data as $item) {
                if (!is_array($item)) continue;
                $row = [];
                foreach ($headers as $h) {
                    $row[] = (string)($item[$h] ?? '');
                }
                $rows[] = $row;
            }
            return $rows;
        }

        if (is_array($first)) {
            return $data;
        }

        return [];
    }

    /**
     * Parsing tabel HTML
     */
    protected function parseHtmlRows($filePath)
    {
        $content = @file_get_contents($filePath);
        if (!$content) return [];

        $rows = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        if ($tables->length > 0) {
            $table = $tables->item(0);
            $trList = $table->getElementsByTagName('tr');
            foreach ($trList as $tr) {
                $row = [];
                $cells = $tr->getElementsByTagName('th');
                if ($cells->length === 0) {
                    $cells = $tr->getElementsByTagName('td');
                }
                foreach ($cells as $cell) {
                    $row[] = trim(preg_replace('/\s+/', ' ', $cell->textContent));
                }
                if (!empty(array_filter($row))) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Parsing seluruh sheet dalam file Excel modern (.xlsx) beserta nama sheet-nya
     * Mengembalikan array of: ['name' => $sheetName, 'rows' => $rows]
     */
    protected function parseXlsxSheets($filePath)
    {
        $allSheets = [];
        if (!class_exists('\ZipArchive')) return [];

        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $sharedStrings = [];
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml) {
                $xmlStrings = @simplexml_load_string($sharedStringsXml);
                if ($xmlStrings && isset($xmlStrings->si)) {
                    foreach ($xmlStrings->si as $si) {
                        if (isset($si->t)) {
                            $sharedStrings[] = (string)$si->t;
                        } elseif (isset($si->r)) {
                            $text = '';
                            foreach ($si->r as $rPart) {
                                $text .= (string)($rPart->t ?? '');
                            }
                            $sharedStrings[] = $text;
                        } else {
                            $sharedStrings[] = '';
                        }
                    }
                }
            }

            // 1. Ekstraksi relasi workbook (xl/_rels/workbook.xml.rels)
            $sheetRels = [];
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($relsXml) {
                $xmlRels = @simplexml_load_string($relsXml);
                if ($xmlRels && isset($xmlRels->Relationship)) {
                    foreach ($xmlRels->Relationship as $rel) {
                        $rId = (string)($rel['Id'] ?? '');
                        $target = (string)($rel['Target'] ?? '');
                        if ($rId && $target) {
                            $cleanTarget = ltrim($target, '/');
                            if (!str_starts_with($cleanTarget, 'xl/')) {
                                $cleanTarget = 'xl/' . $cleanTarget;
                            }
                            $sheetRels[$rId] = $cleanTarget;
                        }
                    }
                }
            }

            // 2. Ekstraksi daftar sheet dari xl/workbook.xml
            $sheetsMeta = [];
            $wbXml = $zip->getFromName('xl/workbook.xml');
            if ($wbXml) {
                $xmlWb = @simplexml_load_string($wbXml);
                if ($xmlWb && isset($xmlWb->sheets->sheet)) {
                    foreach ($xmlWb->sheets->sheet as $s) {
                        $sName = (string)($s['name'] ?? '');
                        $rId = '';
                        $attrs = $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                        if ($attrs && isset($attrs['id'])) {
                            $rId = (string)$attrs['id'];
                        }
                        if (!$rId) {
                            $rId = (string)($s['id'] ?? '');
                        }
                        $targetPath = $sheetRels[$rId] ?? '';
                        if ($targetPath && $zip->locateName($targetPath) !== false) {
                            $sheetsMeta[] = ['name' => $sName, 'path' => $targetPath];
                        }
                    }
                }
            }

            // Fallback jika xl/workbook.xml tidak terbaca
            if (empty($sheetsMeta)) {
                for ($idx = 0; $idx < $zip->numFiles; $idx++) {
                    $stat = $zip->statIndex($idx);
                    if ($stat && preg_match('#^xl/worksheets/sheet(\d+)\.xml$#i', $stat['name'], $sm)) {
                        $sheetsMeta[] = [
                            'name' => 'Sheet ' . $sm[1],
                            'path' => $stat['name']
                        ];
                    }
                }
            }

            // 3. Baca tiap worksheet
            foreach ($sheetsMeta as $meta) {
                $sheetXml = $zip->getFromName($meta['path']);
                if (!$sheetXml) continue;

                $xmlSheet = @simplexml_load_string($sheetXml);
                $sheetRows = [];
                if ($xmlSheet && isset($xmlSheet->sheetData->row)) {
                    foreach ($xmlSheet->sheetData->row as $r) {
                        $cellValues = [];
                        $maxColIndex = 0;
                        foreach ($r->c as $c) {
                            $cellRef = (string)($c['r'] ?? '');
                            $colIdx = 0;
                            if (preg_match('/^([A-Z]+)/', $cellRef, $matches)) {
                                $colStr = $matches[1];
                                $len = strlen($colStr);
                                for ($ci = 0; $ci < $len; $ci++) {
                                    $colIdx = $colIdx * 26 + (ord($colStr[$ci]) - ord('A') + 1);
                                }
                                $colIdx -= 1;
                            }

                            $val = '';
                            $t = (string)($c['t'] ?? '');
                            if ($t === 's' && isset($c->v) && isset($sharedStrings[(int)$c->v])) {
                                $val = $sharedStrings[(int)$c->v];
                            } elseif ($t === 'inlineStr' && isset($c->is->t)) {
                                $val = (string)$c->is->t;
                            } elseif (isset($c->v)) {
                                $val = (string)$c->v;
                            }

                            $cellValues[$colIdx] = trim($val);
                            if ($colIdx > $maxColIndex) {
                                $maxColIndex = $colIdx;
                            }
                        }

                        $rowNormalized = [];
                        for ($ci = 0; $ci <= $maxColIndex; $ci++) {
                            $rowNormalized[$ci] = $cellValues[$ci] ?? '';
                        }

                        if (!empty(array_filter($rowNormalized))) {
                            $sheetRows[] = $rowNormalized;
                        }
                    }
                }

                if (!empty($sheetRows)) {
                    $allSheets[] = [
                        'name' => $meta['name'],
                        'rows' => $sheetRows
                    ];
                }
            }

            $zip->close();
        }

        return $allSheets;
    }

    /**
     * Parsing file Excel modern (.xlsx) sheet pertama murni menggunakan ZipArchive & SimpleXML
     */
    protected function parseXlsxRows($filePath)
    {
        $sheets = $this->parseXlsxSheets($filePath);
        return $sheets[0]['rows'] ?? [];
    }

    /**
     * Parsing file CSV, TSV, atau Delimited Text
     */
    protected function parseCsvRows($filePath)
    {
        $rows = [];
        if (($handle = @fopen($filePath, 'r')) !== false) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                return [];
            }
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

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
            while (($data = fgetcsv($handle, 3000, $bestDelim)) !== false) {
                if (!empty(array_filter($data))) {
                    $rows[] = array_map('trim', $data);
                }
            }
            fclose($handle);
        }

        return $rows;
    }
}

