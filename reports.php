<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
require_once 'config/db.php';
$admin_name = $_SESSION['username'];

// ============================
// FILTERS
// ============================
$date_from = trim($_GET['date_from'] ?? date('Y-m-01'));
$date_to   = trim($_GET['date_to'] ?? date('Y-m-d'));
$status_filter = trim($_GET['status'] ?? '');
$dept_filter = trim($_GET['department'] ?? '');
$guard_filter = trim($_GET['guard_id'] ?? '');

// ============================
// BUILD MAIN QUERY
// ============================
$sql = "SELECT v.*, u.username AS guard_name 
        FROM visitors v 
        LEFT JOIN users u ON v.registered_by = u.id 
        WHERE DATE(v.in_time) BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$types = "ss";

if (!empty($status_filter) && in_array($status_filter, ['In', 'Out'])) {
    $sql .= " AND v.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
if (!empty($dept_filter)) {
    $sql .= " AND v.department = ?";
    $params[] = $dept_filter;
    $types .= "s";
}
if (!empty($guard_filter)) {
    $sql .= " AND v.registered_by = ?";
    $params[] = intval($guard_filter);
    $types .= "i";
}

$sql .= " ORDER BY v.in_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$visitors = [];
while ($row = $result->fetch_assoc()) {
    $visitors[] = $row;
}

// ============================
// SUMMARY STATS
// ============================
$total_visitors = count($visitors);
$currently_inside = 0;
$checked_out = 0;
$total_duration_minutes = 0;
$duration_count = 0;

foreach ($visitors as $v) {
    if ($v['status'] === 'In') {
        $currently_inside++;
    } else {
        $checked_out++;
        if (!empty($v['out_time'])) {
            $in = strtotime($v['in_time']);
            $out = strtotime($v['out_time']);
            $total_duration_minutes += ($out - $in) / 60;
            $duration_count++;
        }
    }
}

$avg_duration = $duration_count > 0 ? round($total_duration_minutes / $duration_count) : 0;

// ============================
// DAILY BREAKDOWN (for chart)
// ============================
$daily_sql = "SELECT DATE(v.in_time) AS day, COUNT(*) AS total 
              FROM visitors v 
              WHERE DATE(v.in_time) BETWEEN ? AND ?";
$daily_params = [$date_from, $date_to];
$daily_types = "ss";

if (!empty($dept_filter)) {
    $daily_sql .= " AND v.department = ?";
    $daily_params[] = $dept_filter;
    $daily_types .= "s";
}
if (!empty($guard_filter)) {
    $daily_sql .= " AND v.registered_by = ?";
    $daily_params[] = intval($guard_filter);
    $daily_types .= "i";
}

$daily_sql .= " GROUP BY DATE(v.in_time) ORDER BY day ASC";

$stmt2 = $conn->prepare($daily_sql);
$stmt2->bind_param($daily_types, ...$daily_params);
$stmt2->execute();
$daily_result = $stmt2->get_result();

$chart_labels = [];
$chart_data = [];
while ($d = $daily_result->fetch_assoc()) {
    $chart_labels[] = date('d M', strtotime($d['day']));
    $chart_data[] = (int)$d['total'];
}

$depts = $conn->query("SELECT department_name FROM departments ORDER BY department_name");
$guards = $conn->query("SELECT id, username FROM users WHERE role = 'guard' ORDER BY username");

// ============================
// HANDLE PDF EXPORT
// ============================
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {

    require_once 'vendor/autoload.php';

    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: DejaVu Sans, sans-serif;
                font-size: 11px;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .header {
                background: #c8102e;
                color: #fff;
                padding: 20px 25px;
                margin-bottom: 20px;
            }
            .header h1 {
                margin: 0;
                font-size: 20px;
            }
            .header p {
                margin: 5px 0 0 0;
                font-size: 12px;
                opacity: 0.9;
            }
            .meta {
                padding: 0 25px;
                margin-bottom: 20px;
                font-size: 11px;
                color: #555;
            }
            .meta span {
                margin-right: 25px;
            }
            .summary {
                padding: 0 25px;
                margin-bottom: 20px;
            }
            .summary table {
                width: 100%;
                border-collapse: collapse;
            }
            .summary td {
                border: 1px solid #ddd;
                padding: 12px;
                text-align: center;
                background: #f9f9f9;
            }
            .summary .label {
                font-size: 10px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .summary .value {
                font-size: 18px;
                font-weight: bold;
                color: #c8102e;
            }
            .records {
                padding: 0 25px;
            }
            table.data {
                width: 100%;
                border-collapse: collapse;
            }
            table.data thead th {
                background: #c8102e;
                color: #fff;
                padding: 8px 6px;
                font-size: 10px;
                text-align: left;
                text-transform: uppercase;
            }
            table.data tbody td {
                padding: 7px 6px;
                font-size: 10px;
                border-bottom: 1px solid #eee;
            }
            table.data tbody tr:nth-child(even) {
                background: #fafafa;
            }
            .status-in  { color: #28a745; font-weight: bold; }
            .status-out { color: #c8102e; font-weight: bold; }
            .footer {
                position: fixed;
                bottom: 15px;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 9px;
                color: #999;
            }
        </style>
    </head>
    <body>

        <div class="header">
            <h1>KPC Visitor Management System</h1>
            <p>Visitor Report</p>
        </div>

        <div class="meta">
            <span><strong>Period:</strong> <?= htmlspecialchars($date_from) ?> to <?= htmlspecialchars($date_to) ?></span>
            <span><strong>Generated:</strong> <?= date('d M Y, h:i A') ?></span>
            <span><strong>By:</strong> <?= htmlspecialchars($admin_name) ?></span>
        </div>

        <div class="summary">
            <table>
                <tr>
                    <td>
                        <div class="label">Total Visitors</div>
                        <div class="value"><?= $total_visitors ?></div>
                    </td>
                    <td>
                        <div class="label">Currently Inside</div>
                        <div class="value"><?= $currently_inside ?></div>
                    </td>
                    <td>
                        <div class="label">Checked Out</div>
                        <div class="value"><?= $checked_out ?></div>
                    </td>
                    <td>
                        <div class="label">Avg. Duration</div>
                        <div class="value"><?= $avg_duration ?> min</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="records">
            <table class="data">
                <thead>
                    <tr>
                        <th>Visitor</th>
                        <th>Phone</th>
                        <th>Meet Person</th>
                        <th>Department</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Status</th>
                        <th>Registered By</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total_visitors === 0): ?>
                    <tr>
                        <td colspan="8" style="text-align:center; padding:20px; color:#999;">
                            No visitors found for this filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($visitors as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['visitor_name']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['meet_person']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td><?= date('d M Y h:i A', strtotime($row['in_time'])) ?></td>
                            <td><?= $row['out_time'] ? date('d M Y h:i A', strtotime($row['out_time'])) : '-' ?></td>
                            <td class="<?= $row['status'] === 'In' ? 'status-in' : 'status-out' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </td>
                            <td><?= htmlspecialchars($row['guard_name'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="footer">
            KPC VMS — Confidential Report
        </div>

    </body>
    </html>
    <?php
    $html = ob_get_clean();

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'visitor_report_' . $date_from . '_to_' . $date_to . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - KPC VMS</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- SIDEBAR (Admin) -->
    <div class="sidebar">
        <h2>KPC Admin</h2>
        <ul>
            <li onclick="window.location.href='dashboard-admin.php'"><i class="fa-solid fa-gauge"></i> Dashboard</li>
            <li onclick="window.location.href='guard.php'"><i class="fa-solid fa-users-gear"></i> Guard</li>
            <li onclick="window.location.href='visitor-admin.php'"><i class="fa-solid fa-list"></i> Visitor</li>
            <li class="active"><i class="fa-solid fa-chart-column"></i> Reports</li>
            <li onclick="window.location.href='actions/logout.php'"><i class="fa-solid fa-right-from-bracket"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <h1>Reports</h1>
            <div class="profile">
                <span>Welcome, <?= htmlspecialchars($admin_name) ?></span>
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($admin_name) ?>&background=c8102e&color=fff" alt="Profile">
            </div>
        </div>

        <!-- BREADCRUMB -->
        <div style="margin-bottom: 20px;">
            <p style="color: #666;">
                <a href="dashboard-admin.php" style="color: #c8102e; text-decoration: none;">Dashboard</a> / Reports
            </p>
        </div>

        <!-- FILTER BAR -->
        <div class="table-section" style="margin-bottom: 25px;">
            <h2 style="margin: 0 0 15px 0;">Filter Reports</h2>
            <form method="GET" action="reports.php" style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
                
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" required
                           style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" required
                           style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Department</label>
                    <select name="department" style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px; background: #fff;">
                        <option value="">All</option>
                        <?php while ($d = $depts->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($d['department_name']) ?>" <?= $dept_filter === $d['department_name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['department_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">Guard</label>
                    <select name="guard_id" style="width: 100%; padding: 9px; border: 1px solid #ddd; border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px; background: #fff;">
                        <option value="">All</option>
                        <?php while ($g = $guards->fetch_assoc()): ?>
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
                <div style="display: flex; gap: 6px;">
                    <button type="submit" style="background: #c8102e; color: white; border: none; padding: 9px 16px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">
                        Apply
                    </button>
                </div>
            </form>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="cards" style="margin-bottom: 25px;">
            <div class="card">
                <h3>Total Visitors</h3>
                <h2><?= $total_visitors ?></h2>
            </div>
            <div class="card">
                <h3>Currently Inside</h3>
                <h2><?= $currently_inside ?></h2>
            </div>
            <div class="card">
                <h3>Checked Out</h3>
                <h2><?= $checked_out ?></h2>
            </div>
            <div class="card">
                <h3>Avg. Duration</h3>
                <h2><?= $avg_duration ?><span style="font-size: 16px; color:#666;"> min</span></h2>
            </div>
        </div>

        <!-- CHART -->
        <div class="table-section" style="margin-bottom: 25px;">
            <h2 style="margin: 0 0 15px 0;">Visitors Per Day</h2>
            <canvas id="visitorChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- EXPORT BUTTON -->
        <?php if ($total_visitors > 0): ?>
            <div class="table-section" style="text-align: right;">
                <a href="?date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&status=<?= urlencode($status_filter) ?>&department=<?= urlencode($dept_filter) ?>&guard_id=<?= urlencode($guard_filter) ?>&export=pdf"
                   style="background: #c8102e; color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-block;">
                    <i class="fa-solid fa-file-pdf"></i> Export Report (PDF)
                </a>
            </div>
        <?php endif; ?>

    </div>

    <!-- CHART SCRIPT -->
    <script>
        const ctx = document.getElementById('visitorChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Visitors',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: '#c8102e',
                    borderRadius: 6,
                    barThickness: 28
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#eee' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    </script>

</body>
</html>