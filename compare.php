<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

require 'db.php';
include 'header.php';

function loadData($content, $ext) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'xls');
    file_put_contents($tmpFile, $content);

    $data = [];
    $headers = [];

    if ($ext == 'csv') {
        $fp = fopen($tmpFile, 'r');
        if ($fp) {
            $headers = fgetcsv($fp);
            while ($row = fgetcsv($fp)) {
                while (count($row) < count($headers)) $row[] = '';
                $data[] = array_combine($headers, $row);
            }
            fclose($fp);
        }
    } else {
        $sheet = IOFactory::load($tmpFile)->getActiveSheet();
        $rows = $sheet->toArray();
        $headers = array_shift($rows);
        foreach ($rows as $r) $data[] = array_combine($headers, $r);
    }

    unlink($tmpFile);
    return [$data, $headers];
}

$f1 = $_POST['f1'] ?? '';
$f2 = $_POST['f2'] ?? '';
$c1 = $_POST['c1'] ?? '';
$c2 = $_POST['c2'] ?? '';

if (!$f1 || !$f2 || !$c1 || !$c2) {
    echo "<p>Error: Missing file or column selection.</p>";
    include 'footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT file_content FROM excel_files WHERE filename=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$f1]);
$content1 = $stmt->fetchColumn();

$stmt->execute([$f2]);
$content2 = $stmt->fetchColumn();

list($d1, $h1) = loadData($content1, pathinfo($f1, PATHINFO_EXTENSION));
list($d2, $h2) = loadData($content2, pathinfo($f2, PATHINFO_EXTENSION));

$v1 = array_column($d1, $c1);
$v2 = array_column($d2, $c2);

$out = [];

foreach ($d1 as $r1) {
    if (!in_array($r1[$c1], $v2)) {
        $out[] = array_merge(array_values($r1), array_fill(0, count($d2[0]) + 2, ''));
    }
}

foreach ($d2 as $r2) {
    if (!in_array($r2[$c2], $v1)) {
        $out[] = array_merge(array_fill(0, count($d1[0]) + 2, ''), array_values($r2));
    }
}

if ($out) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray(array_merge($h1, ['', ''], $h2), null, 'A1');
    $sheet->fromArray($out, null, 'A2');

    $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

    $tmpFile = tempnam(sys_get_temp_dir(), 'xls');
    IOFactory::createWriter($spreadsheet, 'Xlsx')->save($tmpFile);
    $outname = "unmatched_" . time() . ".xlsx";

    $stmt = $pdo->prepare("INSERT INTO excel_files(filename, file_content, type) VALUES(?, ?, ?)");
    $stmt->execute([$outname, file_get_contents($tmpFile), 'unmatched']);
    unlink($tmpFile);

    echo "<h2>Compare Result</h2>";
    echo "<p>Unmatched rows found!</p>";
    echo "<a href='download.php?file=" . urlencode($outname) . "'>Download Unmatched File</a>";
} else {
    echo "<h2>Compare Result</h2>";
    echo "<p>No unmatched rows!</p>";
}

include 'footer.php';
