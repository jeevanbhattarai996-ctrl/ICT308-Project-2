<?php
// Header include
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarePlus Healthcare Management System</title>
    <link rel="stylesheet" href="/careplus_hms/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <div class="header-content">
                <h1>CarePlus Healthcare Management System</h1>
                <p class="tagline">CarePlus Medical Centre</p>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="user-info">
                        <span>Logged in as: <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                        <a href="/careplus_hms/logout.php" class="logout-link">Logout</a>
                    </div>
                <?php endif; ?>
            </div>
        </header>
