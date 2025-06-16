<?php
include("header.html");
include("connection.php");
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    // Redirect to login page or show unauthorized message
    header("Location: register.php?form=login");
    echo "You are not authorized to access this page, login first";
    exit();
}
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
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dashboard-container">  
    <aside>
    <button id="btn-upcoming" class="active" onclick="showContent('upcoming')">Upcoming Events</button>
    <button id="btn-pending" onclick="showContent('pending')">Pending Events</button>
    <button id="btn-new" onclick="showContent('new')">New Event</button>
    <!-- <button id="btn-analytics" onclick="showContent('analytics')">Analytics</button> -->
    <button onclick="logout()">Log Out</button>
  </aside>

  <main>
    <div id="upcoming" class="content-section active">
      <h2>Upcoming Events</h2>
    <div class="content-box">
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
    </div>

    <div id="pending" class="content-section">
    <div class="content-box">
      <h2>Pending Events</h2>
      <p>Events waiting for approval from admin.</p>
    </div>
    </div>

    <div id="new" class="content-section">
    <div class="content-box">
      <div class="form-container">
      <h2>Create New Event</h2>
      <form action ="<?php htmlspecialchars($_SERVER["PHP_SELF"])?>"  method="post" enctype="multipart/form-data">
        <input type="text" placeholder="Event Name" name="event_name" required>
        <textarea name="description" placeholder="Event Description" rows="4" cols="50" required></textarea>
        <select name="event_type" required>
          <option value="" disabled selected>Select Event Type</option>
          <option value="online">Online</option>
          <option value="On Site">On Site</option>
          <option value="Hybrid">Hybrid</option>
        <input type="date" placeholder="Event Date" name="event_date" required>
        <input type="text" placeholder="Venue" name="venue" required>
        <label for="event_image">Upload Event Photo:</label>
        <input type="file" name="event_image" accept="image/*" required>
        <div class="button-row">
        <button type="submit">Submit</button>
        <button type="reset">Reset</button>
        </div>
      </form>
      </div>
    </div>
    </div>

    <!-- <div id="analytics" class="content-section">
    <div class="content-box">
      <h2>Analytics</h2>
      <p>Graphs and stats about events.</p>
    </div>
    </div> -->
  </main>
</div>

  <script>
    function showContent(sectionId) {
      // Hide all content sections
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });
      // Show selected section
      document.getElementById(sectionId).classList.add('active');

      // Remove active class from all buttons
      document.querySelectorAll('aside button').forEach(btn => {
        btn.classList.remove('active');
      });
      // Add active class to clicked button
      document.getElementById('btn-' + sectionId).classList.add('active');
    }

    function logout() {
      // Just a placeholder - replace with your logout logic
      alert('Logging out...');
      window.location.href = 'logout.php'; // or wherever your logout handler is
    }
  </script>

</body>
</html>
<?php
include("footer.html");
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get other form values
    $event_name = $_POST['event_name'];
    $event_description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $event_type = $_POST['event_type'];
    $venue = $_POST['venue'];
    $userid = $_SESSION['user_id'];

    // Check if file is uploaded
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
        // Read the binary image data
        $imgData = file_get_contents($_FILES['event_image']['tmp_name']);

        // Prepare the query
        $stmt = $conn->prepare("INSERT INTO events (name, description, date, type, venue, image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssbi", $event_name, $event_description, $event_date, $event_type, $venue, $null, $userid);

        // Use send_long_data for the blob
        $stmt->send_long_data(5, $imgData);
        
        if ($stmt->execute()) {
            echo "Event created and image stored in database.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Image upload failed.";
        echo "Upload error code: ". $_FILES['event_image']['error'];   
       }
}
?>