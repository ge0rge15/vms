<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prevent admin from deleting their own account
    if ($id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
        header("Location: ../guard.php");
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'guard'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: ../guard.php");
exit();
?>