<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    exit("Unauthorized access");
}

// Update Request Status
if (isset($_POST['request_id']) && isset($_POST['status'])) {
    $id = $_POST['request_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}

header("Location: ../dashboard/admin_dashboard.php");
exit;
?>
