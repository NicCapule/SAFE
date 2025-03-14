<?php
session_start();
require '../config/config.php';

// Add User
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    $stmt->execute();
}

 // Remove User
if (isset($_POST['delete_user_id'])) {
    $id = $_POST['delete_user_id'];
    $conn->query("DELETE FROM users WHERE id = $id");
}

header("Location: ../dashboard/admin_dashboard.php");
exit;
?>
