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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/stylist.css">
    <style>
        :root {
            --primary-color:rgb(102, 8, 13); /* Riot Games red */
            --secondary-color: #1D1D1D; /* Dark gray */
            --background-color: #121212; /* Dark background */
            --text-color: #FFFFFF; /* White text */
            --accent-color: #FFD700; /* Gold accent */
            --border-radius: 8px;
            --padding: 15px;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: var(--padding);
        }

        h2, h3, p, a {
            color: var(--text-color);
            font-family: 'Roboto', sans-serif;
        }

        h2 {
            font-size: 2em;
            margin-bottom: 20px;
        }

        h3 {
            font-size: 1.5em;
            margin-bottom: 15px;
        }

        p {
            font-size: 1em;
            margin-bottom: 10px;
        }

        a {
            color: var(--accent-color);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid var(--primary-color);
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: var(--primary-color);
            color: var(--text-color);
        }

        tr:nth-child(even) {
            background-color: var(--secondary-color);
        }

        tr:hover {
            background-color: var(--primary-color);
            color: var(--text-color);
        }

        button {
            background-color: var(--primary-color);
            color: var(--text-color);
            border: none;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: var(--accent-color);
            color: var(--background-color);
        }

        input[type="text"], input[type="email"], input[type="password"], select {
            background-color: var(--secondary-color);
            color: var(--text-color);
            border: 1px solid var(--primary-color);
            padding: 10px;
            border-radius: var(--border-radius);
            margin-right: 10px;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--secondary-color);
            border-radius: var(--border-radius);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .system-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .system-status span {
            font-weight: bold;
        }

        .add-user-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .add-user-form input, .add-user-form button {
            width: 100%;
            max-width: 300px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="dashboard-section welcome-section">
            <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
        </div>

        <!-- System Status Section -->
        <div class="dashboard-section system-status-section">
            <h3>System Status: <span id="status"><?php echo $status; ?></span></h3>
            <form method="POST" action="../admin/toggle_status.php">
                <button type="submit">Toggle Status</button>
            </form>
        </div>

        <!-- Users Section -->
        <div class="dashboard-section users-section">
            <h3>Users</h3>
            <table>
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
        </div>

        <!-- Add New User Section -->
        <div class="dashboard-section add-user-section">
            <h3>Add New User</h3>
            <form method="POST" action="../admin/manage_users.php" class="add-user-form">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="add_user">Add User</button>
            </form>
        </div>

        <!-- Help Requests Section -->
        <div class="dashboard-section help-requests-section">
            <h3>Help Requests</h3>
            <table>
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
        </div>

        <!-- Logout Section -->
        <div class="dashboard-section logout-section">
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>
</body>
</html>
