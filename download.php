<?php
$pdo = new PDO("mysql:host=localhost;dbname=excel_sort","root","");

if(!empty($_GET['download'])){
    $stmt = $pdo->prepare("SELECT file_content, filename FROM excel_files WHERE filename=?");
    $stmt->execute([$_GET['download']]);
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$row['filename'].'"');
        echo $row['file_content'];
        exit;
    }
    exit("File not found.");
}

$files = $pdo->query("SELECT * FROM excel_files ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Files</title></head>
<body>
<h2>All Files</h2>
<?php if($files): ?>
<table border="1" cellpadding="5">
<tr><th>ID</th><th>Filename</th><th>Type</th><th>Uploaded At</th><th>Download</th></tr>
<?php foreach($files as $f): ?>
<tr>
<td><?= $f['id'] ?></td>
<td><?= htmlspecialchars($f['filename']) ?></td>
<td><?= $f['type'] ?></td>
<td><?= $f['created_at'] ?></td>
<td><a href="?download=<?= urlencode($f['filename']) ?>">Download</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?><p>No files found.</p><?php endif; ?>
</body>
</html>
