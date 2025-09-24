<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

require 'db.php';

if(isset($_GET['offset'])){
    $offset = (int)$_GET['offset'];
    $limit = 5;
    $stmt = $pdo->prepare("SELECT * FROM excel_files ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($files as $f){
        echo "<tr>
            <td><input type='checkbox' name='files[]' value='".$f['filename']."'></td>
            <td>".$f['filename']."</td>
            <td>".$f['type']."</td>
            <td>".$f['created_at']."</td>
            <td><a href='?delete=".$f['filename']."' onclick='return confirm(\"Delete this file?\")'>Delete</a></td>
        </tr>";
    }
    exit;
}

if(isset($_GET['delete'])){
    $stmt = $pdo->prepare("DELETE FROM excel_files WHERE filename=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if(isset($_POST['files'])){
    $files = $_POST['files'];

    if(count($files) == 1){
        $stmt = $pdo->prepare("SELECT file_content, filename FROM excel_files WHERE filename=? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$files[0]]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row){
            $ext = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
            header('Content-Description: File Transfer');
            header('Content-Type: '.($ext === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
            header('Content-Disposition: attachment; filename="'.$row['filename'].'"');
            header('Content-Length: '.strlen($row['file_content']));
            echo $row['file_content'];
            exit;
        }
    } else {
        $zip = new ZipArchive();
        $zipName = 'files_'.time().'.zip';
        $tmpFile = sys_get_temp_dir().'/'.$zipName;

        if($zip->open($tmpFile, ZipArchive::CREATE)){
            $added = [];
            foreach($files as $f){
                $stmt = $pdo->prepare("SELECT file_content, filename FROM excel_files WHERE filename=? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$f]);
                $r = $stmt->fetch(PDO::FETCH_ASSOC);

                if($r){
                    $filename = $r['filename'];
                    if(isset($added[$filename])){
                        $added[$filename]++;
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($filename, PATHINFO_FILENAME);
                        $filename = $nameOnly . '_' . $added[$filename] . '.' . $ext;
                    } else {
                        $added[$filename] = 0;
                    }
                    $zip->addFromString($filename, $r['file_content']);
                }
            }
            $zip->close();

            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.$zipName.'"');
            header('Content-Length: '.filesize($tmpFile));
            readfile($tmpFile);
            unlink($tmpFile);
            exit;
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM excel_files ORDER BY id DESC LIMIT 5");
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<h2>All Files</h2>

<form method="post" id="fileForm">
<table id="fileTable">
<tr>
<th>Select</th><th>Filename</th><th>Type</th><th>Uploaded At</th><th>Delete</th>
</tr>
<?php foreach($files as $f): ?>
<tr>
<td><input type="checkbox" name="files[]" value="<?= $f['filename'] ?>"></td>
<td><?= $f['filename'] ?></td>
<td><?= $f['type'] ?></td>
<td><?= $f['created_at'] ?></td>
<td><a href="?delete=<?= $f['filename'] ?>" onclick="return confirm('Delete this file?')">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
<br>
<button type="button" id="seeMoreBtn">See More</button>
<br><br>
<button type="submit">Download Selected Files</button>
</form>

<script>
let offset = 5;
document.getElementById('seeMoreBtn').addEventListener('click', function(){
    fetch('?offset='+offset)
    .then(r => r.text())
    .then(data => {
        if(data.trim() === ''){
            this.style.display = 'none';
        } else {
            document.getElementById('fileTable').insertAdjacentHTML('beforeend', data);
            offset += 5;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
