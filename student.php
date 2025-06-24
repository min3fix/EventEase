<?php
include("header.php");
include("connection.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Restore session from cookies if available
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'], $_COOKIE['remember_role'])) {
    $_SESSION['user_id'] = $_COOKIE['remember_user'];
    $_SESSION['role'] = $_COOKIE['remember_role'];
}

// Redirect if not logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: register.php?form=login");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student's name
$name_query = $conn->prepare("SELECT name FROM users WHERE id = ?");
$name_query->bind_param("i", $user_id);
$name_query->execute();
$name_result = $name_query->get_result();
$student = $name_result->fetch_assoc();
$student_name = $student['name'];

// Fetch registered events
$registered_query = "SELECT events.*, users.username, users.name AS host_name 
                     FROM registration 
                     JOIN events ON registration.event_id = events.event_id 
                     JOIN users ON events.user_id = users.id
                     WHERE registration.user_id = ?";
$reg_stmt = $conn->prepare($registered_query);
$reg_stmt->bind_param("i", $user_id);
$reg_stmt->execute();
$registered_result = $reg_stmt->get_result();

// Fetch upcoming (not registered + approved) events
$upcoming_query = "SELECT events.*, users.username, users.name AS host_name 
                   FROM events 
                   JOIN users ON events.user_id = users.id
                   WHERE events.is_approved = 1 
                   AND events.event_id NOT IN (
                       SELECT event_id FROM registration WHERE user_id = ?
                   )
                   AND events.date >= CURDATE()
                   ORDER BY events.date ASC";
$up_stmt = $conn->prepare($upcoming_query);
$up_stmt->bind_param("i", $user_id);
$up_stmt->execute();
$upcoming_result = $up_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Header with welcome and profile -->
<div class="dashboard-header">
    <div class="profile-info">
        <img src="profile.png" alt="Profile" class="profile-icon">
        <span>Welcome, <?php echo htmlspecialchars($student_name); ?>!</span>
    </div>
</div>

<div class="dashboard-container">  
  <aside>
    <button id="btn-upcoming" class="active" onclick="showContent('upcoming')">Upcoming Events</button>
    <button id="btn-registered" onclick="showContent('registered')">Registered Events</button>
    <form action="logout.php" method="post">
      <button type="submit" class="logout-btn">Log Out</button>
    </form>
  </aside>

  <main>
    <!-- Upcoming Events -->
    <div id="upcoming" class="content-section active">
      <h2>Upcoming Events</h2>
      <div class="content-box">
        <?php while ($row = mysqli_fetch_assoc($upcoming_result)) : ?>
          <div class="event-card">
            <?php if (!empty($row['image'])): ?>
              <img src="data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>" alt="Event Image" style="max-width: 300px; height: auto;">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($row['name']); ?></h2>
            <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($row['date'])); ?></p>
            <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
            <p><em>Posted by <?php echo htmlspecialchars($row['host_name']); ?></em></p>

            <form method="get" action="event-details.php">
              <input type="hidden" name="form_type" value="register">
              <input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">
              <div class="button-row">
                <button type="submit" class="register-btn">Register</button>
              </div>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    </div>

    <!-- Registered Events -->
    <div id="registered" class="content-section">
      <h2>Registered Events</h2>
      <div class="content-box">
        <?php while ($row = mysqli_fetch_assoc($registered_result)) : ?>
          <div class="event-card">
            <?php if (!empty($row['image'])): ?>
              <img src="data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>" alt="Event Image" style="max-width: 300px; height: auto;">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($row['name']); ?></h2>
            <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($row['date'])); ?></p>
            <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
            <p><em>Posted by <?php echo htmlspecialchars($row['host_name']); ?></em></p>

            <form method="get" action="event-details.php">
              <input type="hidden" name="event_id" value="<?php echo $row['event_id']; ?>">
              <div class="button-row">
                <button type="submit">View Event</button>
              </div>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </main>
</div>

<script>
function showContent(sectionId) {
  document.querySelectorAll('.content-section').forEach(section => {
    section.classList.remove('active');
  });
  document.getElementById(sectionId).classList.add('active');

  document.querySelectorAll('aside button').forEach(btn => {
    btn.classList.remove('active');
  });
  document.getElementById('btn-' + sectionId).classList.add('active');
}
</script>

</body>
</html>

<?php include("footer.html"); ?>