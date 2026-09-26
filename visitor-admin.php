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
$status_filter = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$guard_filter = trim($_GET['guard_id'] ?? '');

// Build the query dynamically (with JOIN for guard name)
$sql = "SELECT v.*, u.username AS guard_name 
        FROM visitors v 
        LEFT JOIN users u ON v.registered_by = u.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (v.visitor_name LIKE ? OR v.phone LIKE ? OR v.meet_person LIKE ? OR v.department LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if (!empty($status_filter) && in_array($status_filter, ['In', 'Out'])) {
    $sql .= " AND v.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $sql .= " AND DATE(v.in_time) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $sql .= " AND DATE(v.in_time) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if (!empty($guard_filter)) {
    $sql .= " AND v.registered_by = ?";
    $params[] = intval($guard_filter);
    $types .= "i";
}

$sql .= " ORDER BY v.in_time DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get list of guards for the dropdown
$guards_list = $conn->query("SELECT id, username FROM users WHERE role = 'guard' ORDER BY username");
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
            <li onclick="window.location.href='reports.php'"><i class="fa-solid fa-chart-column"></i> Reports</li>
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

        <!-- VISITOR TABLE -->
        <div class="table-section">
            <h2 style="margin: 0 0 15px 0;">All Visitor Records</h2>

            <!-- SEARCH + FILTER BAR -->
            <form method="GET" action="visitor-admin.php" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end; background: #f9f9f9; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Search</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; font-size: 13px;"></i>
                        <input type="text" name="search" placeholder="Name, phone, person..." value="<?= htmlspecialchars($search) ?>"
                               style="width: 100%; padding: 9px 10px 9px 32px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;">
                    </div>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Registered By</label>
                    <select name="guard_id" style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px; background: #fff;">
                        <option value="">All Guards</option>
                        <?php while ($g = $guards_list->fetch_assoc()): ?>
                            <option value="<?= $g['id'] ?>" <?= $guard_filter == $g['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($g['username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Status</label>
                    <select name="status" style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px; background: #fff;">
                        <option value="">All</option>
                        <option value="In" <?= $status_filter === 'In' ? 'selected' : '' ?>>In</option>
                        <option value="Out" <?= $status_filter === 'Out' ? 'selected' : '' ?>>Out</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                           style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                           style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;">
                </div>

                <div style="display: flex; gap: 6px;">
                    <button type="submit" style="background: #c8102e; color: white; border: none; padding: 9px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">
                        Filter
                    </button>
                    <?php if ($search || $status_filter || $date_from || $date_to || $guard_filter): ?>
                        <a href="visitor-admin.php" style="background: #eee; color: #333; text-decoration: none; padding: 9px 14px; border-radius: 6px; font-size: 13px;">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <table id="visitorTable">
                <thead>
                    <tr>
                        <th>Visitor Name</th>
                        <th>Phone</th>
                        <th>Meet Person</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Status</th>
                        <th>Registered By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding: 30px; color:#999;">
                            <?php if ($search || $status_filter || $date_from || $date_to || $guard_filter): ?>
                                No visitors match your filter.
                            <?php else: ?>
                                No visitors registered yet.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['visitor_name']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['meet_person']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td><?= date('d M Y', strtotime($row['in_time'])) ?></td>
                            <td><?= date('h:i A', strtotime($row['in_time'])) ?></td>
                            <td><?= $row['out_time'] ? date('h:i A', strtotime($row['out_time'])) : '-' ?></td>
                            <td>
                                <?php if ($row['status'] === 'In'): ?>
                                    <span style="background: #28a745; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">In</span>
                                <?php else: ?>
                                    <span style="background: #c8102e; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">Out</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['guard_name'] ?? 'N/A') ?></td>
                            <td style="white-space: nowrap;">
                                <button 
                                    onclick='openEditVisitorModal(<?= htmlspecialchars(json_encode([
                                        "id" => $row["id"],
                                        "visitor_name" => $row["visitor_name"],
                                        "phone" => $row["phone"],
                                        "id_number" => $row["id_number"] ?? "",
                                        "company" => $row["company"] ?? "",
                                        "meet_person" => $row["meet_person"],
                                        "department" => $row["department"],
                                        "purpose" => $row["purpose"] ?? ""
                                    ]), ENT_QUOTES, "UTF-8") ?>)'
                                    style="background: #17a2b8; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; text-decoration: none; display: inline-block; border: none; cursor: pointer;">
                                    Edit
                                </button>
                                <a href="actions/delete_visitor.php?id=<?= $row['id'] ?>" 
                                   onclick="return confirm('Delete this visitor record?');"
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
    <!-- EDIT VISITOR MODAL                         -->
    <!-- ========================================== -->
    <div id="editVisitorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        
        <div style="background: white; width: 550px; max-width: 90%; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #c8102e; margin: 0;">Edit Visitor</h2>
                <i class="fa-solid fa-xmark" onclick="closeEditVisitorModal()" style="cursor: pointer; font-size: 24px; color: #666;"></i>
            </div>

            <form action="actions/edit_visitor.php" method="POST">
                <input type="hidden" name="id" id="editv_id">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Visitor Name *</label>
                        <input 
                            type="text" 
                            name="visitor_name" 
                            id="editv_name"
                            required 
                            data-type="text"
                            pattern="[A-Za-z\s\-']+"
                            title="Only letters, spaces, hyphens, and apostrophes are allowed"
                            minlength="3"
                            maxlength="100"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Phone Number *</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            id="editv_phone"
                            required
                            data-type="numbers"
                            pattern="[0-9+\s]+"
                            title="Only digits, spaces, and + are allowed"
                            minlength="10"
                            maxlength="15"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">ID / Passport Number</label>
                        <input 
                            type="text" 
                            name="id_number" 
                            id="editv_id_number"
                            data-type="alphanumeric"
                            pattern="[A-Za-z0-9]+"
                            title="Only letters and numbers are allowed"
                            minlength="6"
                            maxlength="15"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Company</label>
                        <input 
                            type="text" 
                            name="company" 
                            id="editv_company"
                            maxlength="100"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Meeting Person *</label>
                        <input 
                            type="text" 
                            name="meet_person" 
                            id="editv_meet"
                            required 
                            data-type="text"
                            pattern="[A-Za-z\s\-']+"
                            title="Only letters, spaces, hyphens, and apostrophes are allowed"
                            minlength="2"
                            maxlength="100"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Department *</label>
                        <select name="department" id="editv_department" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; background:#fff;">
                            <option value="">-- Select --</option>
                            <?php
                            $depts = $conn->query("SELECT department_name FROM departments ORDER BY department_name");
                            while ($d = $depts->fetch_assoc()):
                            ?>
                                <option value="<?= htmlspecialchars($d['department_name']) ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 15px;">
                    <label style="display:block; margin-bottom:5px; font-weight:500;">Purpose of Visit</label>
                    <textarea 
                        name="purpose" 
                        id="editv_purpose"
                        rows="3" 
                        maxlength="500"
                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family: 'Poppins', sans-serif; resize:vertical;"></textarea>
                </div>

                <button type="submit" style="background: #c8102e; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 20px;">
                    Update Visitor
                </button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function openEditVisitorModal(v) {
            document.getElementById('editv_id').value = v.id;
            document.getElementById('editv_name').value = v.visitor_name;
            document.getElementById('editv_phone').value = v.phone;
            document.getElementById('editv_id_number').value = v.id_number || '';
            document.getElementById('editv_company').value = v.company || '';
            document.getElementById('editv_meet').value = v.meet_person;
            document.getElementById('editv_department').value = v.department;
            document.getElementById('editv_purpose').value = v.purpose || '';
            document.getElementById('editVisitorModal').style.display = 'flex';
        }

        function closeEditVisitorModal() {
            document.getElementById('editVisitorModal').style.display = 'none';
        }

        document.getElementById('editVisitorModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeEditVisitorModal();
            }
        });
    </script>

    <!-- Load script.js for input enforcement -->
    <script src="script.js"></script>

</body>
</html>