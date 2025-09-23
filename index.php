<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}
?>

<?php include 'header.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Two Excel/CSV Files</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Upload Two Excel/CSV Files</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label>Select First File:</label>
        <input type="file" name="file1" accept=".xlsx,.xls,.csv" required>

        <label>Select Second File:</label>
        <input type="file" name="file2" accept=".xlsx,.xls,.csv" required>

        <input type="submit" value="Upload Files">
    </form>
</body>
</html>

<?php include 'footer.php'; ?>
