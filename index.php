<?php
include('connection.php');
session_start();
$query = "SELECT events.*, users.username, users.name AS host_name FROM events 
          JOIN users ON events.user_id = users.id 
          WHERE date >= CURDATE() 
          ORDER BY date ASC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet"  href="style.css">
</head>
<body>

<header>
    <div class="logo">
        <a href="index.php"><img src="EventEase.png" alt="EventEase Logo" class="logo-img"></a>
    </div>
    <nav>
        <a href="register.php?form=login">Login</a>
        <a href="register.php?form=register">Register</a>
        <a href="#contact">Contact</a>
        <a href="events.php">Browse Events</a>
    </nav>
</header>

<section class="hero">
    <div class="event">
        <h1>Welcome to EventEase</h1>
    </div>
    <div class="welcome">
        <p>Manage and register for campus events effortlessly!</p>
    </div>

</section>

<section class="events-section">
    <h2>Upcoming Events</h2>
    <div class="events-container">
        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
            <div class="event-card">
                <?php if (!empty($row['image'])): ?>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode($row['image']); ?>" 
                         alt="Event Image" style="max-width: 300px; height: auto;">
                <?php endif; ?>
                <h2><?php echo htmlspecialchars($row['name']); ?></h2>
                <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($row['date'])); ?></p>
                <p><strong>Venue:</strong> <?php echo htmlspecialchars($row['venue']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                <p><em>Posted by <?php echo htmlspecialchars($row['host_name']); ?></em></p>
                <form method="get" action="event-details.php">
                <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($row['event_id']); ?>">
                <div class="button-row">
                    <button type="submit">View Event</button>
                </div>
                
                </form>
                
            </div>
        <?php endwhile; ?>
    </div>

</section>

<footer id="contact" style="padding: 20px; background: #222; color: #fff; text-align: center;">
    <p>Contact us at <a href="mailto:info@eventease.com" style="color: #4dabf7;">info@eventease.com</a></p>
</footer>

</body>
</html>

