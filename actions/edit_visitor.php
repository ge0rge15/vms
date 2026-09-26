<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Only admins can edit visitors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../visitor-admin.php");
    exit();
}

// Get form data
$id           = intval($_POST['id'] ?? 0);
$visitor_name = trim($_POST['visitor_name'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$id_number    = trim($_POST['id_number'] ?? '');
$company      = trim($_POST['company'] ?? '');
$meet_person  = trim($_POST['meet_person'] ?? '');
$department   = trim($_POST['department'] ?? '');
$purpose      = trim($_POST['purpose'] ?? '');

// Validate
if ($id <= 0) {
    $_SESSION['error'] = "Invalid visitor ID.";
    header("Location: ../visitor-admin.php");
    exit();
}

if (empty($visitor_name) || empty($phone) || empty($meet_person) || empty($department)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: ../visitor-admin.php");
    exit();
}

// Update the visitor
$stmt = $conn->prepare("
    UPDATE visitors 
    SET visitor_name = ?, phone = ?, id_number = ?, company = ?, 
        meet_person = ?, department = ?, purpose = ?
    WHERE id = ?
");
$stmt->bind_param("sssssssi", 
    $visitor_name, $phone, $id_number, $company, 
    $meet_person, $department, $purpose, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Visitor updated successfully!";
} else {
    $_SESSION['error'] = "Failed to update visitor: " . $stmt->error;
}

header("Location: ../visitor-admin.php");
exit();
?>