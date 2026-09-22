<?php
session_start();

// Only admins can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Get admin info from session
$admin_name = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KPC VMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- SIDEBAR (Admin) -->
    <div class="sidebar">
    <h2>KPC Admin</h2>
    <ul>
        <li class="active" onclick="setActive(this)"><i class="fa-solid fa-gauge"></i> Dashboard</li>
        <li onclick="window.location.href='guard.php'"><i class="fa-solid fa-users-gear"></i> Guard</li>
       <li onclick="window.location.href='visitor-admin.php'"><i class="fa-solid fa-list"></i> Visitor</li>
        <li onclick="window.location.href='actions/logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Logout</li>
    </ul>
</div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <h1>Admin Dashboard</h1>
            <div class="profile">
    <span>Welcome, <?= htmlspecialchars($admin_name) ?></span>
    <img src="https://ui-avatars.com/api/?name=<?= urlencode($admin_name) ?>&background=c8102e&color=fff" alt="Profile">
</div>
        </div>

        <!-- STATS CARDS -->
        <div class="cards">
            <div class="card">
                <h3>Total Visitors This Month</h3>
                <h2>450</h2>
            </div>
            <div class="card">
                <h3>Active Guards</h3>
                <h2>5</h2>
            </div>
            <div class="card">
                <h3>Departments</h3>
                <h2>8</h2>
            </div>
            <div class="card">
                <h3>Guards on Duty</h3>
                <h2>5</h2>
            </div>
        </div>

    </div>

    <!-- Sidebar Active State Script -->
    <script>
        function setActive(element) {
            document.querySelectorAll('.sidebar ul li').forEach(item => item.classList.remove('active'));
            element.classList.add('active');
        }
    </script>
</body>
</html>