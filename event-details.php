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
<body>
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
    <p><em>Posted by <?php echo htmlspecialchars($row['host_name']); ?></em></p>
    <form action = "" method= "post">
    <div class="reg-buttons">
        <button>Reister</button>
        <button>Register</button>
    </div>
    </form>


    </div>

    </div>
    
</body>
</html>
<?php
include("footer.html");
?>