<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
require 'db.php';
include 'header.php';

// Function to load file data
function loadData($content, $ext){
    $tmp = tempnam(sys_get_temp_dir(),'xls');
    file_put_contents($tmp,$content);

    if($ext==='csv'){
        $fp = fopen($tmp,'r'); 
        $headers = fgetcsv($fp); 
        $data = [];
        while($row=fgetcsv($fp)){
            // Ensure row length matches headers
            $row = array_pad($row, count($headers), '');
            $data[] = array_combine($headers, $row);
        }
        fclose($fp);
    } else {
        $rows = IOFactory::load($tmp)->getActiveSheet()->toArray();
        $headers = array_shift($rows);
        $data = array_map(fn($r)=>array_combine($headers, $r), $rows);
    }

    unlink($tmp);
    return [$data, $headers];
}

// Get POST data
$f1 = $_POST['f1'] ?? '';
$f2 = $_POST['f2'] ?? '';
$c1 = $_POST['c1'] ?? '';
$c2 = $_POST['c2'] ?? '';

// Validate input
if(!$f1 || !$f2 || !$c1 || !$c2){
    echo "<p>Error: Missing file or column selection.</p>";
    include 'footer.php';
    exit;
}

// Load data and headers securely using prepared statements
$stmt = $pdo->prepare("SELECT file_content FROM excel_files WHERE filename=?");
$stmt->execute([$f1]);
$content1 = $stmt->fetchColumn();

$stmt->execute([$f2]);
$content2 = $stmt->fetchColumn();

list($d1, $h1) = loadData($content1, pathinfo($f1, PATHINFO_EXTENSION));
list($d2, $h2) = loadData($content2, pathinfo($f2, PATHINFO_EXTENSION));

$v1 = array_column($d1,$c1);
$v2 = array_column($d2,$c2);

$out = [];

// Prepare unmatched rows with two-column gap
foreach($d1 as $r){
    if(!in_array($r[$c1], $v2)){
        $out[] = array_merge(array_values($r), array_fill(0, count($d2[0]) + 2, ''));
    }
}

foreach($d2 as $r){
    if(!in_array($r[$c2], $v1)){
        $out[] = array_merge(array_fill(0, count($d1[0]) + 2, ''), array_values($r));
    }
}

// Create unmatched Excel file
if($out){
    $s = new Spreadsheet();
    $sh = $s->getActiveSheet();

    // Merge headers: File1 columns + two empty columns + File2 columns
    $headerRow = array_merge($h1, array_fill(0,2,''), $h2);
    $sh->fromArray($headerRow, null, 'A1');

    // Add unmatched rows starting at A2
    $sh->fromArray($out, null, 'A2');

    // Make headers bold
    $sh->getStyle('A1:'.$sh->getHighestColumn().'1')->getFont()->setBold(true);

    // Save temporary file
    $tmp = tempnam(sys_get_temp_dir(),'xls');
    IOFactory::createWriter($s,'Xlsx')->save($tmp);
    $outname = "unmatched_".time().".xlsx";

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO excel_files(filename,file_content,type) VALUES(?,?,?)");
    $stmt->execute([$outname, file_get_contents($tmp), 'unmatched']);
    unlink($tmp);

    echo "<h2>Compare Result</h2>";
    echo "<p>Unmatched rows found!</p>";
    echo "<a href='download.php?file=".urlencode($outname)."'>Download Unmatched File</a>";
} else {
    echo "<h2>Compare Result</h2>";
    echo "<p>No unmatched rows!</p>";
}

include 'footer.php';
