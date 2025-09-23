<?php
// Start session in every page that includes this header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My File Download App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header style="padding: 10px; background-color: #f2f2f2; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h1>Excel Sorting System</h1>
            <nav>
                <a href="index.php">Upload File</a> |
                <a href="download.php">Download Files</a>
            </nav>
        </div>
        <div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span> |
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </header>
