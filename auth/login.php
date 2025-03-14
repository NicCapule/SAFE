<?php
session_start();
require '../config/config.php';

$error_message = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, is_admin FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password, $is_admin);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['is_admin'] = $is_admin;

            if ($is_admin) {
                header("Location: ../dashboard/admin_dashboard.php");
            } else {
                header("Location: ../dashboard/user_dashboard.php");
            }
            exit;
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        $error_message = "User not found.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>BFP: SAFE | Login</title>
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
                    Login Form
                </div>
                
                <form class="login100-form validate-form" method="POST">
                    <div class="wrap-input100 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                        <input class="input100" type="email" name="email" placeholder="Email">
                        <span class="focus-input100" data-placeholder="&#9993;"></span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Password is required">
                        <input class="input100" type="password" name="password" placeholder="Password">
                        <span class="focus-input100" data-placeholder="&#128274;"></span>
                    </div>

                    <!-- Display error message here -->
                    <?php if (!empty($error_message)): ?>
                        <p style="color: red; text-align: center; margin-top: 5px;"><?php echo $error_message; ?></p>
                    <?php endif; ?>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn" type="submit">
                            Login
                        </button>
                    </div>

                    <!-- Registration link -->
                    <div style="text-align: center; margin-top: 15px;">
                        <p>Not yet a User? <a href="../auth/register.php" style="color: rgb(196, 30, 58); text-decoration: none; font-weight: bold;">Register Now!</a></p>
                    </div>
                </form>
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
