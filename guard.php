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

// ============================
// SEARCH + FILTER PARAMETERS
// ============================
$search = trim($_GET['search'] ?? '');
$shift_filter = trim($_GET['shift'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "SELECT id, username, email, phone, shift, status 
        FROM users 
        WHERE role = 'guard'";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (username LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if (!empty($shift_filter) && in_array($shift_filter, ['Morning', 'Afternoon', 'Night'])) {
    $sql .= " AND shift = ?";
    $params[] = $shift_filter;
    $types .= "s";
}

if (!empty($status_filter) && in_array($status_filter, ['Active', 'Inactive'])) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
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
            <h2 style="margin: 0 0 15px 0;">All Guards</h2>

            <!-- SEARCH + FILTER BAR -->
            <form method="GET" action="guard.php" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end; background: #f9f9f9; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                
                <!-- Search -->
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Search</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; font-size: 13px;"></i>
                        <input type="text" name="search" placeholder="Name, email, or phone..." value="<?= htmlspecialchars($search) ?>"
                               style="width: 100%; padding: 9px 10px 9px 32px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;">
                    </div>
                </div>

                <!-- Shift filter -->
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Shift</label>
                    <select name="shift" style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px; background: #fff;">
                        <option value="">All Shifts</option>
                        <option value="Morning" <?= $shift_filter === 'Morning' ? 'selected' : '' ?>>Morning</option>
                        <option value="Afternoon" <?= $shift_filter === 'Afternoon' ? 'selected' : '' ?>>Afternoon</option>
                        <option value="Night" <?= $shift_filter === 'Night' ? 'selected' : '' ?>>Night</option>
                    </select>
                </div>

                <!-- Status filter -->
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px; background: #fff;">
                        <option value="">All</option>
                        <option value="Active" <?= $status_filter === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $status_filter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 6px;">
                    <button type="submit" style="background: #c8102e; color: white; border: none; padding: 9px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">
                        Filter
                    </button>
                    <?php if ($search || $shift_filter || $status_filter): ?>
                        <a href="guard.php" style="background: #eee; color: #333; text-decoration: none; padding: 9px 14px; border-radius: 6px; font-size: 13px;">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>

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
                if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 30px; color:#999;">
                            <?php if ($search || $shift_filter || $status_filter): ?>
                                No guards match your filter.
                            <?php else: ?>
                                No guards added yet.
                            <?php endif; ?>
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
                            <td style="white-space: nowrap;">
                                <button 
                                    onclick='openEditModal(<?= htmlspecialchars(json_encode([
                                        "id" => $row["id"],
                                        "username" => $row["username"],
                                        "email" => $row["email"],
                                        "phone" => $row["phone"] ?? "",
                                        "shift" => $row["shift"] ?? ""
                                    ]), ENT_QUOTES, "UTF-8") ?>)'
                                    style="background: #17a2b8; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; text-decoration: none; display: inline-block; border: none; cursor: pointer;">
                                    Edit
                                </button>
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
    <!-- ADD MODAL                                  -->
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
    <!-- EDIT MODAL                                 -->
    <!-- ========================================== -->
    <div id="editGuardModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        
        <div style="background: white; width: 550px; max-width: 90%; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #c8102e; margin: 0;">Edit Guard</h2>
                <i class="fa-solid fa-xmark" onclick="closeEditModal()" style="cursor: pointer; font-size: 24px; color: #666;"></i>
            </div>

            <form action="actions/edit_guard.php" method="POST">
                <input type="hidden" name="id" id="edit_id">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Full Name *</label>
                        <input 
                            type="text" 
                            name="username" 
                            id="edit_username"
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
                            id="edit_phone"
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
                    <input type="email" name="email" id="edit_email" required maxlength="100" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                </div>

                <div style="margin-top: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Password</label>
                    <input 
                        type="text" 
                        name="password" 
                        placeholder="Leave blank to keep current password" 
                        minlength="8"
                        maxlength="50"
                        pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}"
                        title="At least 8 characters, must contain at least one letter and one number"
                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    <small style="color:#888; font-size:12px;">🔒 Password is securely hashed.</small>
                </div>

                <div style="margin-top: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Shift *</label>
                    <select name="shift" id="edit_shift" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; background:#fff;">
                        <option value="">-- Select Shift --</option>
                        <option value="Morning">Morning</option>
                        <option value="Afternoon">Afternoon</option>
                        <option value="Night">Night</option>
                    </select>
                </div>

                <button type="submit" style="background: #c8102e; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 20px;">
                    Update Guard
                </button>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- JAVASCRIPT                                 -->
    <!-- ========================================== -->
    <script>
        // ----- Add Modal -----
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

        // ----- Edit Modal -----
        function openEditModal(guard) {
            document.getElementById('edit_id').value = guard.id;
            document.getElementById('edit_username').value = guard.username;
            document.getElementById('edit_email').value = guard.email;
            document.getElementById('edit_phone').value = guard.phone || '';
            document.getElementById('edit_shift').value = guard.shift || '';
            document.getElementById('editGuardModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editGuardModal').style.display = 'none';
        }

        document.getElementById('editGuardModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeEditModal();
            }
        });
    </script>

    <!-- Load script.js for input enforcement -->
    <script src="script.js"></script>

</body>
</html>