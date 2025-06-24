<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$dashboardLink = "register.php?form=login"; // default if not logged in

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'student':
            $dashboardLink = "student.php";
            break;
        case 'manager':
            $dashboardLink = "manager.php";
            break;
        case 'admin':
            $dashboardLink = "admin.php"; // or whatever your admin dashboard is
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <header>
    <div class="logo">
        <a href="index.php"><img src="EventEase.png" alt="EventEase Logo" class="logo-img"></a>
    </div>
    <nav>
        <a href="<?php echo $dashboardLink; ?>">Dashboard</a>
        <a href="register.php?form=login">Login</a>
        <a href="register.php?form=register">Register</a>
        <a href="#contact">Contact</a>
        <a href="events.php">Browse Events</a>
    </nav>
</header>

</body>
</html>