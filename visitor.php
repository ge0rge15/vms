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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">Visitor Records</h2>
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
                        <th>Meet Person</th>
                        <th>Department</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
<?php
$result = $conn->query("
    SELECT * FROM visitors 
    ORDER BY in_time DESC
");

if ($result->num_rows === 0): ?>
    <tr>
        <td colspan="7" style="text-align:center; padding: 30px; color:#999;">
            No visitors registered yet.
        </td>
    </tr>
<?php else: ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['visitor_name']) ?></td>
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

    <!-- ========================================== -->
    <!-- MODAL (POP-UP FORM)                        -->
    <!-- ========================================== -->
    <div id="visitorModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        
        <div style="background: white; width: 550px; max-width: 90%; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #c8102e; margin: 0;">Add Visitor</h2>
                <i class="fa-solid fa-xmark" onclick="closeModal()" style="cursor: pointer; font-size: 24px; color: #666;"></i>
            </div>

            <<form action="actions/add_visitor.php" method="POST">

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div>
            <label style="display:block; margin-bottom:5px; font-weight:500;">Visitor Name *</label>
            <input type="text" name="visitor_name" placeholder="e.g. Vishal" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
        </div>
        <div>
            <label style="display:block; margin-bottom:5px; font-weight:500;">Phone Number *</label>
            <input type="tel" name="phone" placeholder="e.g. 0712 345 678" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
        <div>
            <label style="display:block; margin-bottom:5px; font-weight:500;">ID / Passport Number</label>
            <input type="text" name="id_number" placeholder="e.g. 12345678" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
        </div>
        <div>
            <label style="display:block; margin-bottom:5px; font-weight:500;">Company</label>
            <input type="text" name="company" placeholder="e.g. Safaricom" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
        <div>
            <label style="display:block; margin-bottom:5px; font-weight:500;">Meeting Person *</label>
            <input type="text" name="meet_person" placeholder="e.g. Ivin" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px;">
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
        <textarea name="purpose" rows="3" placeholder="e.g. Meeting with IT team" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-family: 'Poppins', sans-serif; resize:vertical;"></textarea>
    </div>

    <button type="submit" style="background: #c8102e; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 20px;">
        Save Visitor
    </button>
</form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- JAVASCRIPT                                 -->
    <!-- ========================================== -->
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

</body>
</html>