<!DOCTYPE html>
<html>
<head>
    <title>Upload Two Excel/CSV Files</title>
</head>
<body>
    <h2>Upload Two Excel/CSV Files</h2>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <label>Select First File:</label>
        <input type="file" name="file1" accept=".xlsx,.xls,.csv" required><br><br>

        <label>Select Second File:</label>
        <input type="file" name="file2" accept=".xlsx,.xls,.csv" required><br><br>

        <input type="submit" value="Upload Files">
    </form>
</body>
</html>
