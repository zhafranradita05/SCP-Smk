<?php
$testNames = [
    'X RPL 1', 'X RPL1', 'XI RPL 2', 'XII RPL1',
    'X TKJ 1', 'X TKJ1', 'XI TKJ 2', 'XII TKJ2',
    'X DKV 1', 'X DKV1', 'XI DKV 2', 'XII DKV2',
    'X AK 1', 'X AK1', 'XI AK 2', 'XII AK1', 'X AK2', 'X AK3', 'XII AK4',
    'X MP 1', 'X MP1', 'XI MP 2', 'XII MP1',
    'X BR 1', 'X BR1', 'XI BR 2', 'XII BR1',
    'X BD', 'XI BD', 'XII BD',
    'X LPS 1', 'X LPS1', 'XI LPS 2', 'XII LPS',
    'X PPLG 1', 'X TJKT 2', 'X MPLB 1', 'X AKL 1', 'X BDP 1'
];

$regex = '/\b(PPLG|RPL|TJKT|TKJ|DKV|MM|MPLB|OTKP|MP|AKL|AK|BDP|BD|BR|LPS|TBG|TBS)(?![A-Za-z])/i';

$aliasMap = [
    'PPLG' => 'RPL',
    'TJKT' => 'TKJ',
    'MPLB' => 'MP',
    'OTKP' => 'MP',
    'AKL'  => 'AK',
    'BDP'  => 'BD',
];

echo "Testing Regex on Class Names:\n";
$uniqueMajors = [];
foreach ($testNames as $name) {
    if (preg_match($regex, $name, $matches)) {
        $raw = strtoupper($matches[1]);
        $canonical = $aliasMap[$raw] ?? $raw;
        $uniqueMajors[$canonical] = true;
        echo sprintf("  %-12s => RAW: %-5s => CANONICAL: %-5s\n", $name, $raw, $canonical);
    } else {
        echo sprintf("  %-12s => NO MATCH!\n", $name);
    }
}

echo "\nUnique Majors Found (" . count($uniqueMajors) . "): " . implode(', ', array_keys($uniqueMajors)) . PHP_EOL;

