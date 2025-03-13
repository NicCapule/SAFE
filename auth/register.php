<?php
session_start();
require '../config/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);

    if ($stmt->execute()) {
        echo "Registration successful. <a href='login.php'>Login here</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="../assets/stylesheet1.css">
    </head>
<body>
    <div class="limiter">
        <div class="container-login100">
            <div class="wrap-login100">
                <div class="login100-form-title">
                    Register
                </div>
                
                <form class="login100-form validate-form" method="POST">
                    <div class="wrap-input100 validate-input" data-validate="Full Name is required">
                        <input class="input100" type="text" name="name" placeholder="Full Name">
                        <span class="focus-input100" data-placeholder="&#128100;"></span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                        <input class="input100" type="email" name="email" placeholder="Email">
                        <span class="focus-input100" data-placeholder="&#9993;"></span>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Password is required">
                        <input class="input100" type="password" name="password" placeholder="Password">
                        <span class="focus-input100" data-placeholder="&#128274;"></span>
                    </div>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn" type="submit">
                            Register
                        </button>
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
