<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Only guards can add visitors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guard') {
    header("Location: ../index.php");
    exit();
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../visitor.php");
    exit();
}

// Get form data
$visitor_name = trim($_POST['visitor_name'] ?? '');
$phone        = trim($_POST['phone'] ?? '');
$id_number    = trim($_POST['id_number'] ?? '');
$company      = trim($_POST['company'] ?? '');
$meet_person  = trim($_POST['meet_person'] ?? '');
$department   = trim($_POST['department'] ?? '');
$purpose      = trim($_POST['purpose'] ?? '');
$registered_by = $_SESSION['user_id'];

// Validate required fields
if (empty($visitor_name) || empty($phone) || empty($meet_person) || empty($department)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: ../visitor.php");
    exit();
}

// Insert into database
$stmt = $conn->prepare("
    INSERT INTO visitors 
    (visitor_name, phone, id_number, company, meet_person, department, purpose, registered_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssssi",
    $visitor_name,
    $phone,
    $id_number,
    $company,
    $meet_person,
    $department,
    $purpose,
    $registered_by
);

if ($stmt->execute()) {
    $_SESSION['success'] = "Visitor registered successfully!";
} else {
    $_SESSION['error'] = "Failed to register visitor: " . $stmt->error;
}

header("Location: ../visitor.php");
exit();
?>