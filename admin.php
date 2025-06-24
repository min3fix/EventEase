<?php
include("header.php");
include("connection.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'], $_COOKIE['remember_role'])) {
    $_SESSION['user_id'] = $_COOKIE['remember_user'];
    $_SESSION['role'] = $_COOKIE['remember_role'];
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: register.php?form=login");
    echo "You are not authorized to access this page.";
    exit();
}

// Fetch admin name
$admin_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin_data = $admin_result->fetch_assoc();
$admin_name = $admin_data['name'];

// Fetch events
$approved_query = "SELECT events.*, users.name AS host_name FROM events 
                   JOIN users ON events.user_id = users.id 
                   WHERE events.is_approved = 1 AND date >= CURDATE() 
                   ORDER BY date ASC";
$approved_result = mysqli_query($conn, $approved_query);

$pending_query = "SELECT events.*, users.name AS host_name FROM events 
                  JOIN users ON events.user_id = users.id 
                  WHERE events.is_approved = 0 
                  ORDER BY date ASC";
$pending_result = mysqli_query($conn, $pending_query);

$request_query = "SELECT id, name, email, role, position 
                  FROM users 
                  WHERE is_approved = 0 AND (role = 'manager' OR role = 'admin')";
$request_result = mysqli_query($conn, $request_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-header">
    <div class="profile-info">
        <img src="profile.png" alt="Profile" class="profile-icon">
        <span>Welcome, <?php echo htmlspecialchars($admin_name); ?>!</span>
    </div>
</div>
<div class="dashboard-container">
    <aside>
        <button id="btn-upcoming" class="active" onclick="showContent('upcoming')">Upcoming Events</button>
        <button id="btn-pending" onclick="showContent('pending')">Pending Events</button>
        <button id="btn-requests" onclick="showContent('requests')">Organiser/Admin Requests</button>
        <form action="logout.php" method="post">
        <button type="submit" class="logout-btn">Log Out</button>
        </form>
    </aside>
    <main>
        <div id="upcoming" class="content-section active">
            <h2>Approved Upcoming Events</h2>
            <div class="content-box">
                <?php while ($row = mysqli_fetch_assoc($approved_result)) : ?>
                    <div class="event-card">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($row['date'])); ?></p>
                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
                        <p><em>Hosted by <?php echo htmlspecialchars($row['host_name']); ?></em></p>
                        <form method="get" action="event-details.php">
                            <input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">
                            <button type="submit">View Event</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div id="pending" class="content-section">
            <h2>Pending Events (Needs Approval)</h2>
            <div class="content-box">
                <?php while ($row = mysqli_fetch_assoc($pending_result)) : ?>
                    <div class="event-card">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($row['date'])); ?></p>
                        <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
                        <p><em>Hosted by <?php echo htmlspecialchars($row['host_name']); ?></em></p>
                        <form method="get" action="event-details.php">
                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($row['event_id']); ?>">
                        <div class="button-row">
                        <button type="submit">View Event</button>
                        </div>
                        </form>
                        <form method="post" >
                            <input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">
                            <div class="button-row">
                            <input type="hidden" name="action" value="approve">
                            </div>
                            
                            <button type="submit">Approve</button>
                        </form>
                        <form method="post">
                            <input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">
                            <div class="button-row">
                                <button type="submit" class="disapprove-btn">Disapprove</button>
                            </div>
                            <input type="hidden" name="action" value="disapprove">
                            
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div id="requests" class="content-section">
            <h2>Organiser/Admin Requests</h2>

        <?php while ($user = mysqli_fetch_assoc($request_result)) : ?>
            <div class="request-card">
            <img src="profile.png" alt="Profile Icon" class="profile-icon">
            <div class="request-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role Requested:</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                <p><strong>Position Applied:</strong> <?php echo htmlspecialchars($user['position']); ?></p>
            </div>

            <form method="post" class="request-actions">
                <input type="hidden" name="form_type" value="handle_request">
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                <button type="submit" name="approve" class="approve-btn">Approve</button>
                <button type="submit" name="disapprove" class="disapprove-btn">Disapprove</button>
            </form>
            </div>
        <?php endwhile; ?>
        </div>
    </main>
</div>
<script>
function showContent(id) {
    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    document.querySelectorAll('aside button').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-' + id).classList.add('active');
}
function logout() {
    window.location.href = 'logout.php';
}
</script>
</body>
</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['action'])) {
    $event_id = intval($_POST['event_id']);
    if ($_POST['action'] === 'approve') {
        $stmt = $conn->prepare("UPDATE events SET is_approved = 1 WHERE event_id = ?");
    } elseif ($_POST['action'] === 'disapprove') {
        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    }
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    echo "<script>location.href=window.location.href;</script>"; // Refresh
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['form_type'] === 'handle_request') {
    $user_id = $_POST['user_id'];

    if (isset($_POST['approve'])) {
        $stmt = $conn->prepare("UPDATE users SET is_approved = 1 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } elseif (isset($_POST['disapprove'])) {
        $stmt = $conn->prepare("UPDATE users SET role = 'student', position = NULL, is_approved = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    header("Location: admin.php");
    exit();
}
include("footer.html");
?>
