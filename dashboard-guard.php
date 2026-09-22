
<?php
session_start();

// Only guards can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    header("Location: index.php");
    exit();
}

$guard_name = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Dashboard - KPC VMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="sidebar">
    <h2>KPC VMS</h2>
    <ul>
        <li class="active" onclick="setActive(this)"><i class="fa-solid fa-gauge"></i> Dashboard</li>
        <li onclick="window.location.href='visitor.php'"><i class="fa-solid fa-users"></i> Visitor</li>
        <li onclick="window.location.href='actions/logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Logout</li>
    </ul>
</div>
    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <h1>Guard Dashboard</h1>
      <div class="profile">
    <span>Welcome, <?= htmlspecialchars($guard_name) ?></span>
    <img src="https://ui-avatars.com/api/?name=<?= urlencode($guard_name) ?>&background=111&color=fff" alt="Profile">
</div>
        </div>

        <!-- STATS CARDS -->
        <div class="cards">
            <div class="card">
                <h3>Expected Today</h3>
                <h2>12</h2>
            </div>
            <div class="card">
                <h3>Currently Inside</h3>
                <h2>4</h2>
            </div>
            <div class="card">
                <h3>Checked Out Today</h3>
                <h2>8</h2>
            </div>
            <div class="card">
                <h3>Pending Check-outs</h3>
                <h2>2</h2>
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