<?php include 'header.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <title>Choose Columns to Compare</title>
    <link rel="stylesheet" href="style.css"> <!-- include single CSS file -->
</head>
<body>
<h2>Select Columns to Compare</h2>
<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
require 'db.php';

function getHeaders($content, $ext){
    $tmp = tempnam(sys_get_temp_dir(),'xls');
    file_put_contents($tmp, $content);
    $h = $ext=='csv' ? fgetcsv(fopen($tmp,'r')) : IOFactory::load($tmp)->getActiveSheet()->rangeToArray('A1:Z1')[0];
    unlink($tmp);
    return $h;
}

if($_FILES){
    $names = [];
    foreach(['file1','file2'] as $f){
        $names[$f] = $_FILES[$f]['name'];
        $content = file_get_contents($_FILES[$f]['tmp_name']);
        $pdo->prepare("INSERT INTO excel_files(filename,file_content,type) VALUES(?,?,?)")
            ->execute([$names[$f], $content, 'uploaded']);
    }

    echo "<form action='compare.php' method='post'>
            <input type='hidden' name='f1' value='{$names['file1']}'>
            <input type='hidden' name='f2' value='{$names['file2']}'>";

    foreach($names as $k=>$n){
        $content = $pdo->query("SELECT file_content FROM excel_files WHERE filename='$n' AND type='uploaded'")->fetchColumn();
        $h = getHeaders($content, pathinfo($n, PATHINFO_EXTENSION));
        echo "<label>".ucfirst($k)." Column:</label>
              <select name='".($k=='file1'?'c1':'c2')."'>"
             .implode('', array_map(fn($v)=>"<option>$v</option>", $h))
             ."</select>";
    }

    echo "<button>Compare</button></form>";
} else {
    echo "<p>Upload two files first.</p>";
}
?>
</body>
</html>

<?php include 'footer.php'; ?>
