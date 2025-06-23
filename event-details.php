<?php
include("header.html");
include("connection.php");
session_start();
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

if ($result->num_rows === 0) {
    echo "Event not found.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'];
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);

    $stmt = mysqli_prepare($conn, "INSERT INTO comments (user_id, event_id, rating, comment) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "iiis", $user_id, $event_id, $rating, $comment);
    mysqli_stmt_execute($stmt);

    header("Location: event-details.php?event_id=" . $event_id);
    exit();
}

$row = $result->fetch_assoc();
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
    <form action = "" method= "post">
    <div class="reg-buttons">
        <button>Reister</button>
        <button>Register</button>
    </div>
    </form>
    </div>
    </div>
<div id="comment-section">
  <h3>Leave a Rating & Comment</h3>
  <form id="comment-form" method="post" action="event-details.php?event_id=<?php echo $event_id; ?>">
    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
    
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
    
</body>
</html>
<?php

include("footer.html");
?>