<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';
$guard_name = $_SESSION['username'];

// Flash messages
$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// SEARCH + FILTER PARAMETERS
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');

// Build the query dynamically
$sql = "SELECT * FROM visitors WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (visitor_name LIKE ? OR phone LIKE ? OR meet_person LIKE ? OR department LIKE ?)";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if (!empty($status_filter) && in_array($status_filter, ['In', 'Out'])) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $sql .= " AND DATE(in_time) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $sql .= " AND DATE(in_time) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql .= " ORDER BY in_time DESC";

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
    <title>Visitor - KPC VMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <!-- SIDEBAR (Guard) -->
    <div class="sidebar">
        <h2>KPC VMS</h2>
        <ul>
            <li onclick="window.location.href='dashboard-guard.php'"><i class="fa-solid fa-gauge"></i> Dashboard</li>
            <li class="active"><i class="fa-solid fa-users"></i> Visitor</li>
            <li onclick="window.location.href='actions/logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

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

        <!-- TOPBAR -->
        <div class="topbar">
            <h1>Visitor</h1>
            <div class="profile">
                <span>Welcome, <?= htmlspecialchars($guard_name ?? 'Guard') ?></span>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($guard_name ?? 'Guard') ?>&background=111&color=fff" alt="Profile">
            </div>
        </div>

        <!-- BREADCRUMB & ADD BUTTON -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <p style="color: #666;">
                <a href="dashboard-guard.php" style="color: #c8102e; text-decoration: none;">Dashboard</a> / Visitor
            </p>
            <button onclick="openModal()" style="background: #c8102e; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 15px;">
                <i class="fa-solid fa-plus"></i> Add Visitor
            </button>
        </div>

        <!-- VISITOR TABLE -->
        <div class="table-section">
            <h2 style="margin: 0 0 15px 0;">Visitor Records</h2>

            <!-- SEARCH + FILTER BAR -->
            <form method="GET" action="visitor.php" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 10px; align-items: end; background: #f9f9f9; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Search</label>
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; font-size: 13px;"></i>
                        <input type="text" name="search" placeholder="Name, phone, person, dept..." value="<?= htmlspecialchars($search) ?>"
                               style="width: 100%; padding: 9px 10px 9px 32px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;">
                    </div>
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
                    <?php if ($search || $status_filter || $date_from || $date_to): ?>
                        <a href="visitor.php" style="background: #eee; color: #333; text-decoration: none; padding: 9px 14px; border-radius: 6px; font-size: 13px;">
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <table id="visitorTable">
                <thead>
                    <tr>
                        <th>Visitor Name</th>
                        <th>Meet Person</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($result->num_rows === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding: 30px; color:#999;">
                            <?php if ($search || $status_filter || $date_from || $date_to): ?>
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
                            <td>
                                <?php if ($row['status'] === 'In'): ?>
                                    <a href="actions/checkout_visitor.php?id=<?= $row['id'] ?>" 
                                       style="background: #c8102e; color: white; padding: 5px 10px; border-radius: 4px; text-decoration:none; font-size: 12px; display:inline-block;">
                                        Check Out
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999; font-size:12px;">Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL (POP-UP FORM) -->
    <div id="visitorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        
        <div style="background: white; width: 550px; max-width: 90%; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #c8102e; margin: 0;">Add Visitor</h2>
                <i class="fa-solid fa-xmark" onclick="closeModal()" style="cursor: pointer; font-size: 24px; color: #666;"></i>
            </div>

            <form action="actions/add_visitor.php" method="POST">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Visitor Name *</label>
                        <input 
                            type="text" 
                            name="visitor_name" 
                            placeholder="e.g. Vishal" 
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
                            placeholder="e.g. 0712 345 678" 
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
                            placeholder="e.g. 12345678" 
                            maxlength="20"
                            style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:5px; font-weight:500;">Company</label>
                        <input 
                            type="text" 
                            name="company" 
                            placeholder="e.g. Safaricom" 
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
                            placeholder="e.g. Ivin" 
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
                        <select name="department" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; background:#fff;">
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
                        rows="3" 
                        placeholder="e.g. Meeting with IT team" 
                        maxlength="500"
                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family: 'Poppins', sans-serif; resize:vertical;"></textarea>
                </div>

                <button type="submit" style="background: #c8102e; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 20px;">
                    Save Visitor
                </button>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        function openModal() {
            document.getElementById('visitorModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('visitorModal').style.display = 'none';
        }

        document.getElementById('visitorModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal();
            }
        });
    </script>

    <!-- Load script.js for input enforcement -->
    <script src="script.js"></script>

</body>
</html>