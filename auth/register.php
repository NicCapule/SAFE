<?php
session_start();
require '../config/config.php';

$success_message = ""; // Initialize success message
$error_message = "";   // Initialize error message
$redirect_script = ""; // Initialize redirect script

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if email already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $error_message = "Email is already registered.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);

        if ($stmt->execute()) {
            $success_message = "Registration Successful! Redirecting to login...";
            // JavaScript redirect after 3 seconds
            $redirect_script = "<script>setTimeout(function() { window.location.href = '../auth/login.php'; }, 1500);</script>";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BFP: SAFE | Register</title>
    <link rel="stylesheet" href="../assets/stylesheet1.css">
</head>
<body>
    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">
                <div class="logo-header">
                    <img src="../assets/bfp.jpg" alt="BFP Logo" class="logo-image">
                    <div>
                        <span class="main-title">BFP: SAFE</span>
                        <br>
                        <span class="sub-title">Security Assistance, Facility, and Emergency</span>
                    </div>
                </div>

                <div class="login100-form-title">
                    Registration Form
                </div>

                <form class="login100-form validate-form" method="POST">
                    <!-- Display success message -->
                    <?php if (!empty($success_message)): ?>
                        <p style="color: green; text-align: center; font-weight: bold; margin-bottom: 10px;"><?php echo $success_message; ?></p>
                    <?php endif; ?>

                    <!-- Display error message -->
                    <?php if (!empty($error_message)): ?>
                        <p style="color: red; text-align: center; margin-bottom: 10px;"><?php echo $error_message; ?></p>
                    <?php endif; ?>

                    <div class="wrap-input100 validate-input" data-validate="Full Name is required">
                        <input class="input100" type="text" name="name" placeholder="Full Name" required>
                        <span class="focus-input100" data-placeholder="&#128100;"></span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                        <input class="input100" type="email" name="email" placeholder="Email" required>
                        <span class="focus-input100" data-placeholder="&#9993;"></span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Password is required">
                        <input class="input100" type="password" name="password" placeholder="Password" required>
                        <span class="focus-input100" data-placeholder="&#128274;"></span>
                    </div>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn" type="submit">
                            Register
                        </button>
                    </div>

                    <!-- Login link -->
                    <div style="text-align: center; margin-top: 15px;">
                        <p>Already a User? <a href="../auth/login.php" style="color: rgb(196, 30, 58); text-decoration: none; font-weight: bold;">Login Now!</a></p>
                    </div>
                </form>

                <!-- Inject redirect script if successful -->
                <?php echo $redirect_script; ?>
            </div>
        </div>
    </div>

    <script>
        // Add has-val class to inputs that have value
        const inputs = document.querySelectorAll('.input100');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if(this.value.trim() !== "") {
                    this.classList.add('has-val');
                } else {
                    this.classList.remove('has-val');
                }
            });

            // Check on page load
            if(input.value.trim() !== "") {
                input.classList.add('has-val');
            }
        });
    </script>
</body>
</html>
