<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';
$admin_name = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor - Admin - KPC VMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- SIDEBAR (Admin) -->
    <div class="sidebar">
        <h2>KPC Admin</h2>
        <ul>
            <li onclick="window.location.href='dashboard-admin.php'"><i class="fa-solid fa-gauge"></i> Dashboard</li>
            <li onclick="window.location.href='guard.php'"><i class="fa-solid fa-users-gear"></i> Guard</li>
            <li class="active"><i class="fa-solid fa-list"></i> Visitor</li>
            <li onclick="window.location.href='actions/logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <h1>Visitor</h1>
            <div class="profile">
                <span>Welcome, <?= htmlspecialchars($admin_name) ?></span>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($admin_name) ?>&background=c8102e&color=fff" alt="Profile">
            </div>
        </div>

        <!-- BREADCRUMB -->
        <div style="margin-bottom: 20px;">
            <p style="color: #666;">
                <a href="dashboard-admin.php" style="color: #c8102e; text-decoration: none;">Dashboard</a> / Visitor
            </p>
        </div>

        <!-- VISITOR TABLE -->
        <div class="table-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">All Visitor Records</h2>
                <div>
                    <label style="margin-right: 10px; color: #666;">Show</label>
                    <select style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                    <label style="margin-left: 10px; color: #666;">entries</label>
                </div>
            </div>

            <table id="visitorTable">
                <thead>
                    <tr>
                        <th>Visitor Name</th>
                        <th>Phone</th>
                        <th>Meet Person</th>
                        <th>Department</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Status</th>
                        <th>Registered By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query("
                    SELECT v.*, u.username AS guard_name 
                    FROM visitors v 
                    JOIN users u ON v.registered_by = u.id 
                    ORDER BY v.in_time DESC
                ");

                if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 30px; color:#999;">
                            No visitors registered yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['visitor_name']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['meet_person']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td><?= date('h:i A', strtotime($row['in_time'])) ?></td>
                            <td><?= $row['out_time'] ? date('h:i A', strtotime($row['out_time'])) : '-' ?></td>
                            <td>
                                <?php if ($row['status'] === 'In'): ?>
                                    <span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">In</span>
                                <?php else: ?>
                                    <span style="background: #c8102e; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Out</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['guard_name']) ?></td>
                            <td>
                                <a href="actions/delete_visitor.php?id=<?= $row['id'] ?>" 
                                   onclick="return confirm('Delete this visitor record?');"
                                   style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block;">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>