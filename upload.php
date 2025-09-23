<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

require 'db.php';
include 'header.php';

// Function to get headers from a file
function getHeaders($content, $ext) {
    $tmpFile = tempnam(sys_get_temp_dir(), 'xls');
    file_put_contents($tmpFile, $content);

    $headers = array();

    if ($ext == 'csv') {
        $fp = fopen($tmpFile, 'r');
        if ($fp) {
            $row = fgetcsv($fp);
            if ($row) $headers = $row;
            fclose($fp);
        }
    } else {
        $spreadsheet = IOFactory::load($tmpFile);
        $sheet = $spreadsheet->getActiveSheet();
        $rowData = $sheet->rangeToArray('A1:Z1');
        if (!empty($rowData[0])) $headers = $rowData[0];
    }

    unlink($tmpFile);
    return $headers;
}
?>

<h2>Select Columns to Compare</h2>

<?php
if (!empty($_FILES['file1']['tmp_name']) && !empty($_FILES['file2']['tmp_name'])) {

    $file1Name = $_FILES['file1']['name'];
    $file2Name = $_FILES['file2']['name'];

    // Save files in database
    $content1 = file_get_contents($_FILES['file1']['tmp_name']);
    $stmt = $pdo->prepare("INSERT INTO excel_files(filename, file_content, type) VALUES (?, ?, ?)");
    $stmt->execute([$file1Name, $content1, 'uploaded']);

    $content2 = file_get_contents($_FILES['file2']['tmp_name']);
    $stmt->execute([$file2Name, $content2, 'uploaded']);

    echo "<form action='compare.php' method='post'>";
    echo "<input type='hidden' name='f1' value='".htmlspecialchars($file1Name)."'>";
    echo "<input type='hidden' name='f2' value='".htmlspecialchars($file2Name)."'>";

    // File1 column selector - fetch latest uploaded
    $stmt = $pdo->prepare("SELECT file_content FROM excel_files WHERE filename=? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$file1Name]);
    $content = $stmt->fetchColumn();
    $headers = getHeaders($content, pathinfo($file1Name, PATHINFO_EXTENSION));

    echo "<label>File1 Column:</label>";
    echo "<select name='c1'>";
    foreach ($headers as $h) {
        echo "<option>".htmlspecialchars($h)."</option>";
    }
    echo "</select><br>";

    // File2 column selector - fetch latest uploaded
    $stmt->execute([$file2Name]);
    $content = $stmt->fetchColumn();
    $headers = getHeaders($content, pathinfo($file2Name, PATHINFO_EXTENSION));

    echo "<label>File2 Column:</label>";
    echo "<select name='c2'>";
    foreach ($headers as $h) {
        echo "<option>".htmlspecialchars($h)."</option>";
    }
    echo "</select><br>";

    echo "<button type='submit'>Compare</button>";
    echo "</form>";

} else {
    echo "<p>Please upload two files first.</p>";
}
?>

<?php include 'footer.php'; ?>
