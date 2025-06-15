<?php
   include("header.html");
   include("connection.php");
   session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <div class="form-box active" id="register-form">
        <h2>Create an Account</h2>
        <form method="POST" action="<?php htmlspecialchars($_SERVER["PHP_SELF"])?>">
            <input type="text" name="fullname" placeholder="Enter your first two names" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="text" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="" disabled selected>Select your role</option>
                <option value="student">Student</option>
                <option value="manager">Event Manager</option>
                <option value="admin">Admin</option>

            </select>
            <input type="text" name="position" placeholder="Your Department(If Event Manager or Admin)" required>
            <input type="hidden" name="form_type" value="register">

            <button type="submit">Register</button>
        </form>
        <p style="text-align: center;">Already have an account? <a href="register.php?form=login">Login</a></p>

    </div>

    
    <div class="form-box" id="login-form">
        <h2>Login</h2>

        <form method="POST" action="<?php htmlspecialchars($_SERVER["PHP_SELF"])?>">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="hidden" name="form_type" value="login">
            <button type="submit">Login</button>
        </form>

        <p style="text-align: center;">Don't have an account? <a href="register.php?form=register">Register above</a></p>
    </div>
</div>

<footer id="contact" style="padding: 20px; background: #222; color: #fff; text-align: center;">
    <p>Contact us at <a href="mailto:info@eventease.com" style="color: #4dabf7;">info@eventease.com</a></p>
</footer>

<script>
    const registerForm = document.getElementById("register-form");
    const loginForm = document.getElementById("login-form");

    function showForm(formType) {
        if (formType === 'login') {
            loginForm.classList.add("active");
            registerForm.classList.remove("active");
        } else {
            registerForm.classList.add("active");
            loginForm.classList.remove("active");
        }
    }

    // Read URL parameter
    const params = new URLSearchParams(window.location.search);
    const form = params.get('form') || 'register'; // default to register

    // Show correct form on load
    showForm(form);
</script>
</body>

</html>

<?php
$success = "";
$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check which form was submitted
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'register') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $name = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = $_POST['email'];
    $sanitized_email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Check if username already exists
    $check_query = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Username already taken.";
        echo "<script>alert('" . addslashes($error) . "');</script>";
    } else {
        // Correct use of prepared statement
        $insert_query = "INSERT INTO users (name, username, email, password, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_query);

        if (!$stmt) {
            $error = "Prepare failed: " . mysqli_error($conn);
        } 
        else {
            mysqli_stmt_bind_param($stmt, "sssss",$name, $username, $sanitized_email, $password, $role);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                if ($role === 'manager') {
                header("Location: manager.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit(); // Always call exit after a header redirect
            } else {
                $error = "Execute failed: " . mysqli_error($conn);
            }
        }
        }
    }
     elseif ($form_type === 'login') {
        // Login logic
        $username = $_POST['username'];
        // $sanitized_email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        $query = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                // Start session and set user info
                session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                // Redirect to dashboard or appropriate page
            if ($row['role'] === 'manager') {
                header("Location: manager.php");
            } else {
                header("Location: student_dashboard.php");
            }
            exit(); // Always call exit after a header redirect
                    } else {
                        $error = "Incorrect password.";
                        echo $error;
                    }
        } else {
            $error = "No user found with that email.";
            echo $error;
        }
    }
}

?>
