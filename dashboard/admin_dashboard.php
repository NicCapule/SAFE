<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit;
}

// Update Request Status
if (isset($_POST['request_id']) && isset($_POST['status'])) {
    $id = $_POST['request_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
}

// Fetch users
$users = $conn->query("SELECT id, name, email FROM users");

// Fetch all request details
$requests = $conn->query("SELECT id, user, help_type, timestamp, status, station_name, user_lat, user_lng FROM requests");

// Fetch system status
$status_result = $conn->query("SELECT status FROM system_status WHERE id = 1");
$status_row = $status_result->fetch_assoc();
$status = $status_row ? $status_row['status'] : 'Unknown'; 

// Get current status
$status_result = $conn->query("SELECT status FROM system_status WHERE id = 1");
$status = $status_result->fetch_assoc()['status'];

// Toggle system status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $new_status = ($system_status === "Active") ? "Busy" : "Active";
    $conn->query("UPDATE system_status SET status = '$new_status' WHERE id = 1");
    $system_status = $new_status;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>

    <h3>System Status: <span id="status"><?php echo $status; ?></span></h3>
    <form method="POST" action="../admin/toggle_status.php">
        <button type="submit">Toggle Status</button>
    </form>

    <h3>Users</h3>
    <table border="1">
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Action</th></tr>
        <?php while ($row = $users->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td>
                    <form method="POST" action="../admin/manage_users.php">
                        <input type="hidden" name="delete_user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit">Remove</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>

    <h3>Add New User</h3>
    <form method="POST" action="../admin/manage_users.php">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="add_user">Add User</button>
    </form>

    <h3>Help Requests</h3>
    <table border="1">
        <tr><th>ID</th><th>User</th><th>Help Type</th><th>Timestamp</th><th>Status</th><th>Station Name</th><th>User Lat</th><th>User Lng</th><th>Action</th></tr>
        <?php while ($row = $requests->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['user']; ?></td>
                <td><?php echo $row['help_type']; ?></td>
                <td><?php echo $row['timestamp']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td><?php echo $row['station_name']; ?></td>
                <td><?php echo $row['user_lat']; ?></td>
                <td><?php echo $row['user_lng']; ?></td>
                <td>
                    <form method="POST" action="../admin/manage_requests.php">
                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                        <select name="status">
                            <option value="Pending" <?php echo ($row['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo ($row['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Resolved" <?php echo ($row['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved</option>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </table>

    <a href="../auth/logout.php">Logout</a>
</body>
</html>
