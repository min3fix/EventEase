<?php
   include("header.php");
   include("connection.php");
   if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
            <div class="checkbox-wrapper">
                <label for="remember_me">
                    <input type="checkbox" name="remember_me" id="remember_me">
                    <span>Remember me</span>
                </label>
            </div>
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
    $form_type = $_POST['form_type'] ?? '';

    if ($form_type === 'register') {
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $name = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = $_POST['email'];
        $sanitized_email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];

        // Auto-approve only students
        $is_approved = ($role === 'student') ? 1 : 0;

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
            $insert_query = "INSERT INTO users (name, username, email, password, role, is_approved) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $insert_query);

            if (!$stmt) {
                $error = "Prepare failed: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt, "sssssi", $name, $username, $sanitized_email, $password, $role, $is_approved);

                if (mysqli_stmt_execute($stmt)) {
                    $user_id = mysqli_insert_id($conn);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;
                    $_SESSION['is_approved'] = $is_approved;

                    header("Location: student.php"); // Everyone lands here first
                    exit();
                } else {
                    $error = "Execute failed: " . mysqli_error($conn);
                }
            }
        }
    }

    elseif ($form_type === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $query = "SELECT id, username, password, role, is_approved FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                // session_start();
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['is_approved'] = $row['is_approved'];

                // If "Remember Me" was checked:
                if (isset($_POST['remember_me'])) {
                    setcookie("remember_user", $_SESSION['user_id'], time() + (30 * 24 * 60 * 60), "/"); // 30 days
                    setcookie("remember_role", $_SESSION['role'], time() + (30 * 24 * 60 * 60), "/");
                }

                // Check approval status
                if ($row['is_approved'] == 0) {
                    // Not approved — limit to student.php
                    header("Location: student.php");
                } else {
                    // Approved — redirect based on role
                    if ($row['role'] === 'manager') {
                        header("Location: manager.php");
                    } elseif ($row['role'] === 'admin') {
                        header("Location: admin.php");
                    } else {
                        header("Location: student.php");
                    }
                }
                exit();
            } else {
                echo "Incorrect password.";
            }
        } else {
            echo "No user found with that username.";
        }
    }
}

?>
