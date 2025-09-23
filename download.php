<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

require 'db.php';

// Handle AJAX "See More"
if(isset($_GET['offset'])){
    $offset = (int)$_GET['offset'];
    $limit = 5;
    $stmt = $pdo->prepare("SELECT * FROM excel_files ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($files as $f){
        echo "<tr>
            <td><input type='checkbox' name='files[]' value='".htmlspecialchars($f['filename'])."'></td>
            <td>".htmlspecialchars($f['filename'])."</td>
            <td>".htmlspecialchars($f['type'])."</td>
            <td>".htmlspecialchars($f['created_at'])."</td>
            <td><a href='?delete=".urlencode($f['filename'])."' onclick='return confirm(\"Delete this file?\")'>Delete</a></td>
        </tr>";
    }
    exit; // stop further output
}

// Delete file
if(!empty($_GET['delete'])){
    $stmt = $pdo->prepare("DELETE FROM excel_files WHERE filename=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle file download
if(!empty($_POST['files'])){
    $f = $_POST['files'];

    // SINGLE FILE DOWNLOAD
    if(count($f) === 1){
        $row = $pdo->prepare("SELECT file_content, filename FROM excel_files WHERE filename=?");
        $row->execute([$f[0]]);
        $r = $row->fetch(PDO::FETCH_ASSOC);

        if($r){
            $ext = strtolower(pathinfo($r['filename'], PATHINFO_EXTENSION));

            header('Content-Description: File Transfer');
            header('Content-Type: '.($ext === 'csv'
                ? 'text/csv'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
            header('Content-Disposition: attachment; filename="'.$r['filename'].'"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . strlen($r['file_content']));
            ob_clean();
            flush();
            echo $r['file_content'];
            exit;
        }
    }
    // MULTIPLE FILES (ZIP)
    else {
        $zip = new ZipArchive();
        $zipName = 'files_'.time().'.zip';
        $tmp = sys_get_temp_dir().'/'.$zipName;

        if($zip->open($tmp, ZipArchive::CREATE) === TRUE){
            $added = []; // track duplicates

            foreach($f as $fn){
                $row = $pdo->prepare("SELECT file_content, filename FROM excel_files WHERE filename=?");
                $row->execute([$fn]);
                $r = $row->fetch(PDO::FETCH_ASSOC);

                if($r){
                    $filename = $r['filename'];

                    // Handle duplicate filenames in zip
                    if(isset($added[$filename])){
                        $added[$filename]++;
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($filename, PATHINFO_FILENAME);
                        $filename = $nameOnly . '_' . $added[$r['filename']] . '.' . $ext;
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
            header('Content-Length: ' . filesize($tmp));
            ob_clean();
            flush();
            readfile($tmp);
            unlink($tmp);
            exit;
        }
    }
}

// Load initial 5 files
$limit = 5;
$offset = 0;
$stmt = $pdo->prepare("SELECT * FROM excel_files ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Download Files</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h2>All Files</h2>

<form method="post" id="fileForm">
<table id="fileTable">
<tr><th>Select</th><th>Filename</th><th>Type</th><th>Uploaded At</th><th>Delete</th></tr>
<?php foreach($files as $f): ?>
<tr>
<td><input type="checkbox" name="files[]" value="<?= htmlspecialchars($f['filename']) ?>"></td>
<td><?= htmlspecialchars($f['filename']) ?></td>
<td><?= htmlspecialchars($f['type']) ?></td>
<td><?= htmlspecialchars($f['created_at']) ?></td>
<td><a href="?delete=<?= urlencode($f['filename']) ?>" onclick="return confirm('Delete this file?')">Delete</a></td>
</tr>
<?php endforeach; ?>
</table>
<br>
<button type="button" id="seeMoreBtn">See More</button>
<br><br>
<button type="submit">Download Selected Files</button>
</form>

<script>
let offset = 5; // already loaded 5
document.getElementById('seeMoreBtn').addEventListener('click', function(){
    fetch('?offset='+offset)
    .then(response => response.text())
    .then(data => {
        if(data.trim() === '') {
            // No more files
            document.getElementById('seeMoreBtn').style.display = 'none';
        } else {
            const table = document.getElementById('fileTable');
            table.insertAdjacentHTML('beforeend', data);
            offset += 5;
        }
    });
});
</script>

</body>
</html>

<?php include 'footer.php'; ?>
