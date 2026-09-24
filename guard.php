<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';
$admin_name = $_SESSION['username'];

// Flash messages
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard - Admin - KPC VMS</title>
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
            <li class="active"><i class="fa-solid fa-users-gear"></i> Guard</li>
            <li onclick="window.location.href='visitor-admin.php'"><i class="fa-solid fa-list"></i> Visitor</li>
            <li onclick="window.location.href='reports.php'"><i class="fa-solid fa-chart-column"></i> Reports</li>
            <li onclick="window.location.href='actions/logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <h1>Guard Management</h1>
            <div class="profile">
                <span>Welcome, <?= htmlspecialchars($admin_name) ?></span>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($admin_name) ?>&background=c8102e&color=fff" alt="Profile">
            </div>
        </div>

        <!-- BREADCRUMB + ADD BUTTON -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <p style="color: #666;">
                <a href="dashboard-admin.php" style="color: #c8102e; text-decoration: none;">Dashboard</a> / Guard
            </p>
            <button onclick="openModal()" style="background: #c8102e; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 15px;">
                <i class="fa-solid fa-plus"></i> Add Guard
            </button>
        </div>

        <!-- FLASH MESSAGES -->
        <?php if ($success): ?>
            <div style="background:#e5ffe5;color:#0a7a0a;padding:12px 20px;border-radius:8px;margin-bottom:15px;">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background:#ffe5e5;color:#c8102e;padding:12px 20px;border-radius:8px;margin-bottom:15px;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- GUARD TABLE -->
        <div class="table-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">All Guards</h2>
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

            <table id="guardTable">
                <thead>
                    <tr>
                        <th>Guard Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Shift</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query("
                    SELECT id, username, email, phone, shift, status 
                    FROM users 
                    WHERE role = 'guard' 
                    ORDER BY created_at DESC
                ");

                if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 30px; color:#999;">
                            No guards added yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['shift'] ?? '-') ?></td>
                            <td>
                                <?php if ($row['status'] === 'Active'): ?>
                                    <span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Active</span>
                                <?php else: ?>
                                    <span style="background: #666; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="actions/delete_guard.php?id=<?= $row['id'] ?>" 
                                   onclick="return confirm('Delete this guard?');"
                                   style="background: #dc3545; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; text-decoration: none; display: inline-block;">
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

    <!-- ========================================== -->
    <!-- MODAL (POP-UP FORM)                        -->
    <!-- ========================================== -->
    <div id="guardModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        
        <div style="background: white; width: 550px; max-width: 90%; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #c8102e; margin: 0;">Add Guard</h2>
                <i class="fa-solid fa-xmark" onclick="closeModal()" style="cursor: pointer; font-size: 24px; color: #666;"></i>
            </div>

            <form action="actions/add_guard.php" method="POST">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Full Name *</label>
                        <input 
                            type="text" 
                            name="username" 
                            placeholder="e.g. Ryan" 
                            required 
                            data-type="text"
                            pattern="[A-Za-z\s\-']+"
                            title="Only letters, spaces, hyphens, and apostrophes are allowed"
                            minlength="3"
                            maxlength="50"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Phone Number</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            placeholder="e.g. 0712 345 678" 
                            data-type="numbers"
                            pattern="[0-9+\s]+"
                            title="Only digits, spaces, and + are allowed"
                            minlength="10"
                            maxlength="15"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                </div>

                <div style="margin-top: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Email Address *</label>
                    <input type="email" name="email" placeholder="e.g. ryan@kpc.com" required maxlength="100" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div style="margin-top: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Password *</label>
                    <input 
                        type="text" 
                        name="password" 
                        placeholder="Min 8 chars, letters and numbers" 
                        required 
                        minlength="8"
                        maxlength="50"
                        pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}"
                        title="At least 8 characters, must contain at least one letter and one number"
                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div style="margin-top: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Shift *</label>
                    <select name="shift" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; background:#fff;">
                        <option value="">-- Select Shift --</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Night">Night</option>
                    </select>
                </div>

                <button type="submit" style="background: #c8102e; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 20px;">
                    Save Guard
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- JAVASCRIPT                                 -->
    <!-- ========================================== -->
    <script>
        function openModal() {
            document.getElementById('guardModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('guardModal').style.display = 'none';
        }

        document.getElementById('guardModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });
    </script>

    <!-- Load script.js for input enforcement -->
    <script src="script.js"></script>

</body>
</html>