<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    exit("Unauthorized access");
}

// Get current status
$status_result = $conn->query("SELECT status FROM system_status WHERE id = 1");
$status = $status_result->fetch_assoc()['status'];

// Toggle status
$new_status = ($status === "Active") ? "Busy" : "Active";
$conn->query("UPDATE system_status SET status = '$new_status' WHERE id = 1");

header("Location: ../dashboard/admin_dashboard.php");
exit;
?>
