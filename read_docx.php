<?php
$zip = new ZipArchive;
if ($zip->open('Soal UKK_SCP.docx') === TRUE) {
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
    echo strip_tags($xml);
} else {
    echo "Failed to open file.";
}
