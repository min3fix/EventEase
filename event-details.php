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
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php?form=login");
    exit;
}
$event_id = intval($_GET['event_id']); // Sanitize ID
$query = "SELECT events.*, users.username, users.name AS host_name 
          FROM events 
          JOIN users ON events.user_id = users.id 
          WHERE event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();
if ($_SESSION['role'] === 'manager') {
    $manager_id = $_SESSION['user_id'];

    // Check if the manager is the one who created the event
    $event_owner_check = $conn->prepare("SELECT * FROM events WHERE event_id = ? AND user_id = ?");
    $event_owner_check->bind_param("ii", $event_id, $manager_id);
    $event_owner_check->execute();
    $owner_result = $event_owner_check->get_result();

    if ($owner_result->num_rows > 0) {
        // Fetch registered students
        $registered_stmt = $conn->prepare("SELECT r.user_id, r.attended, u.username, u.name 
                                           FROM registration r
                                           JOIN users u ON r.user_id = u.id
                                           WHERE r.event_id = ?");
        $registered_stmt->bind_param("i", $event_id);
        $registered_stmt->execute();
        $registered_result = $registered_stmt->get_result();
    }
}

if ($result->num_rows === 0) {
    echo "Event not found.";
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $form_type = $_POST['form_type'] ?? '';
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];

    if ($form_type === 'comment') {
        $rating = $_POST['rating'];
        $comment = trim($_POST['comment']);

        $comment_stmt = mysqli_prepare($conn, 
            "INSERT INTO comments (user_id, event_id, rating, comment) VALUES (?, ?, ?, ?)"
        );

        if ($comment_stmt) {
            mysqli_stmt_bind_param($comment_stmt, "iiis", $user_id, $event_id, $rating, $comment);
            mysqli_stmt_execute($comment_stmt);
            mysqli_stmt_close($comment_stmt);
        } else {
            echo "Error preparing comment statement: " . mysqli_error($conn);
        }

        header("Location: event-details.php?event_id=" . $event_id);
        exit();
    }
    elseif ($form_type === 'cancel_registration') {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];

    $cancel_stmt = $conn->prepare("DELETE FROM registration WHERE user_id = ? AND event_id = ?");
    $cancel_stmt->bind_param("ii", $user_id, $event_id);
    $cancel_stmt->execute();

    header("Location: event-details.php?event_id=" . $event_id);
    exit();
    }

    elseif ($form_type === 'register') {
        // First check if the user is already registered
        $check_stmt = mysqli_prepare($conn, 
            "SELECT * FROM registration WHERE user_id = ? AND event_id = ?"
        );
        mysqli_stmt_bind_param($check_stmt, "ii", $user_id, $event_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            // Already registered
            mysqli_stmt_close($check_stmt);
            echo "<script>alert('You are already registered for this event.');</script>";
        } else {
            mysqli_stmt_close($check_stmt);
            // Proceed to register
            $register_stmt = mysqli_prepare($conn, 
                "INSERT INTO registration (user_id, event_id, registration_time) VALUES (?, ?, NOW())"
            );

            if ($register_stmt) {
                mysqli_stmt_bind_param($register_stmt, "ii", $user_id, $event_id);
                mysqli_stmt_execute($register_stmt);
                mysqli_stmt_close($register_stmt);
                header("Location: event-details.php?event_id=" . $event_id);
                exit();
            } else {
                echo "Error preparing registration statement: " . mysqli_error($conn);
            }
        }
    }
    elseif ($form_type === 'approve' && $is_admin) {
    $event_id = intval($_POST['event_id']);
    $stmt = $conn->prepare("UPDATE events SET is_approved = 1 WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    header("Location: event-details.php?event_id=" . $event_id);
    exit();
}

elseif ($form_type === 'disapprove' && $is_admin) {
    $event_id = intval($_POST['event_id']);
    $stmt = $conn->prepare("UPDATE events SET is_approved = 0 WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    header("Location: event-details.php?event_id=" . $event_id);
    exit();
}
elseif ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['form_type'] === 'toggle_attendance') {
    $user_id = $_POST['user_id'];
    $event_id = $_POST['event_id'];

    // Check current status
    $check = $conn->prepare("SELECT attended FROM registration WHERE user_id = ? AND event_id = ?");
    $check->bind_param("ii", $user_id, $event_id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();
    $current = $res['attended'];

    // Toggle it
    $newStatus = $current ? 0 : 1;
    $update = $conn->prepare("UPDATE registration SET attended = ? WHERE user_id = ? AND event_id = ?");
    $update->bind_param("iii", $newStatus, $user_id, $event_id);
    $update->execute();

    header("Location: event-details.php?event_id=$event_id");
    exit();
}
    else {
        echo "Invalid form submission.";
    }
}

$row = $result->fetch_assoc();
$is_registered = false;
if (isset($_SESSION['user_id'])) {
    $check_stmt = $conn->prepare("SELECT * FROM registration WHERE user_id = ? AND event_id = ?");
    $check_stmt->bind_param("ii", $_SESSION['user_id'], $event_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $is_registered = $check_result->num_rows > 0;
}
$is_attended = false;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $attend_query = "SELECT attended FROM registration WHERE user_id = ? AND event_id = ?";
    $stmt = mysqli_prepare($conn, $attend_query);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $event_id);
    mysqli_stmt_execute($stmt);
    $attend_result = mysqli_stmt_get_result($stmt);

    if ($attend_row = mysqli_fetch_assoc($attend_result)) {
        $is_attended = $attend_row['attended'] == 1;
    }
}
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="  height: auto; overflow-y: auto;">
    <div class="event-object">
    <div class="event-graphics">
    <?php if (!empty($row['image'])): ?>
    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>" 
    alt="Event Image" style="max-width: 500px; height: auto;">
    <?php endif; ?>
    <h1><?php echo htmlspecialchars($row['name']); ?></h1>
    <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
    </div>
    <div class="event-details">
    <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($row['date'])); ?></p>
    <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
    <p><strong>Type:</strong> <?php echo htmlspecialchars($row['type']); ?></p>
    <p><em>Posted by <?php echo htmlspecialchars($row['host_name']); ?></em></p>
    <?php if ($is_admin): ?>
    <form method="post" class="approval-form">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <input type="hidden" name="form_type" value="approve">
        <button type="submit" class="approve-btn">Approve</button>
    </form>

    <form method="post" class="approval-form">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <input type="hidden" name="form_type" value="disapprove">
        <button type="submit" class="disapprove-btn">Disapprove</button>
    </form>
    <?php endif; ?>
    <form id="register-form" method="post" onsubmit="return confirmRegistration();">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <?php if ($is_registered && $_SESSION['role'] === 'student'):?>
        <input type="hidden" name="form_type" value="cancel_registration">
        <div class="reg-buttons">
            <button type="submit" class="cancel-btn">Cancel Registration</button>
        </div>
        <?php elseif (!($is_registered) && $_SESSION['role'] === 'student'): ?>
            <input type="hidden" name="form_type" value="register">
            <div class="reg-buttons">
                <button type="submit" class="register-btn">Register</button>
            </div>
        <?php endif; ?>
    </form>
    </div>
    </div>

<?php
if ($_SESSION['role'] === 'manager' && $_SESSION['user_id'] == $row['user_id']): ?>
  <div class="registered-students-section">
    <h3>Registered Students</h3>

    <?php if ($registered_result->num_rows > 0): ?>
      <div class="student-card-row">
        <?php while ($student = $registered_result->fetch_assoc()): ?>
          <div class="student-card">
            <img src="profile.png" alt="Profile" class="student-profile-pic">
            <div class="student-info">
              <p><strong><?php echo htmlspecialchars($student['name']); ?></strong></p>
              <p>@<?php echo htmlspecialchars($student['username']); ?></p>
              <p>Status: <?php echo $student['attended'] ? "✅ Attended" : "❌ Not Attended"; ?></p>
            </div>
            <form method="post" class="student-attendance-form">
              <input type="hidden" name="form_type" value="toggle_attendance">
              <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
              <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
              <button type="submit" class="<?php echo $student['attended'] ? 'disapprove-btn' : 'approve-btn'; ?>">
                <?php echo $student['attended'] ? 'Unmark Attendance' : 'Mark as Attended'; ?>
              </button>
            </form>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p style="margin-left: 20px;">No students have registered for this event yet.</p>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if ($is_attended): ?>
<div id="comment-section">
  <h3>Leave a Rating & Comment</h3>
  <form id="comment-form" method="post" action="event-details.php?event_id=<?php echo $event_id; ?>">
    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
    <input type="hidden" name="form_type" value="comment">
    
    <label for="rating">Rating (1–5 stars)</label>
    <select name="rating" id="rating" required>
      <option value="" disabled selected>Select a rating</option>
      <option value="1">★☆☆☆☆</option>
      <option value="2">★★☆☆☆</option>
      <option value="3">★★★☆☆</option>
      <option value="4">★★★★☆</option>
      <option value="5">★★★★★</option>
    </select>

    <label for="comment">Optional Comment</label>
    <textarea name="comment" id="comment" placeholder="Write your thoughts... (optional)"></textarea>

    <button type="submit">Submit Feedback</button>
  </form>

  <div id="comments-display">
    <?php
    $comment_query = "SELECT comments.*, users.username
                      FROM comments
                      JOIN users ON comments.user_id = users.id
                      WHERE comments.event_id = ?
                      ORDER BY comments.submitted_at DESC";
    $stmt = mysqli_prepare($conn, $comment_query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) :
      $stars = str_repeat("★", $row['rating']) . str_repeat("☆", 5 - $row['rating']);
    ?>
      <div class="comment-box">
        <p><strong><?php echo htmlspecialchars($row['username']); ?></strong> 
           <small><?php echo date("F j, Y, g:i a", strtotime($row['submitted_at'])); ?></small></p>
        <p class="stars"><?php echo $stars; ?></p>
        <?php if (!empty($row['comment'])): ?>
          <p><?php echo nl2br(htmlspecialchars($row['comment'])); ?></p>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  </div>
</div>
<?php else: ?>
  <div id="comment-section">
    <p><em>Comments will be available after you attend the event.</em></p>
  </div>
<?php endif; ?>
    
</body>
<script>
function confirmAction() {
    const formType = document.querySelector('#register-form input[name="form_type"]').value;
    if (formType === 'register') {
        return confirm("Do you want to register for this event?");
    } else if (formType === 'cancel_registration') {
        return confirm("Do you want to cancel your registration?");
    }
    return true; // fallback
}
</script>
</html>
<?php

include("footer.html");
?>