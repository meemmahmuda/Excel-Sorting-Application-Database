<?php
session_start();
if(!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require 'db.php';
include 'header.php';

// Function to get headers from uploaded file
function getHeaders($content, $ext){
    $tmp = tempnam(sys_get_temp_dir(),'xls');
    file_put_contents($tmp, $content);

    if($ext === 'csv'){
        $fp = fopen($tmp,'r');
        $h = fgetcsv($fp) ?: [];
        fclose($fp);
    } else {
        $h = IOFactory::load($tmp)->getActiveSheet()->rangeToArray('A1:Z1')[0] ?? [];
    }

    unlink($tmp);
    return $h;
}
?>

<h2>Select Columns to Compare</h2>

<?php
if(!empty($_FILES['file1']['tmp_name']) && !empty($_FILES['file2']['tmp_name'])){
    $names = [];

    foreach(['file1','file2'] as $f){
        $names[$f] = $_FILES[$f]['name'];
        $content = file_get_contents($_FILES[$f]['tmp_name']);
        $stmt = $pdo->prepare("INSERT INTO excel_files(filename,file_content,type) VALUES(?,?,?)");
        $stmt->execute([$names[$f], $content, 'uploaded']);
    }

    echo "<form action='compare.php' method='post'>
          <input type='hidden' name='f1' value='".htmlspecialchars($names['file1'])."'>
          <input type='hidden' name='f2' value='".htmlspecialchars($names['file2'])."'>";

    foreach($names as $k => $n){
        $stmt = $pdo->prepare("SELECT file_content FROM excel_files WHERE filename=? AND type='uploaded'");
        $stmt->execute([$n]);
        $content = $stmt->fetchColumn();

        $h = getHeaders($content, pathinfo($n, PATHINFO_EXTENSION));
        echo "<label>".ucfirst($k)." Column:</label>
              <select name='".($k==='file1'?'c1':'c2')."'>"
              .implode('', array_map(fn($v)=>"<option>".htmlspecialchars($v)."</option>", $h))
              ."</select><br>";
    }

    echo "<button>Compare</button></form>";
} else {
    echo "<p>Upload two files first.</p>";
}
?>

<?php include 'footer.php'; ?>
