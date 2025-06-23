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

<div class="dashboard-container">  
    <aside>
    <button id="btn-upcoming" class="active" onclick="showContent('upcoming')">Upcoming Events</button>
    <button id="btn-pending" onclick="showContent('pending')">Registered Events</button>
    <button id="btn-new" onclick="showContent('new')">My Comments</button>
    <button onclick="logout()">Log Out</button>
  </aside>

  <main>
    <div id="upcoming" class="content-section active">
      <h2>Upcoming Events</h2>
      <p>List of upcoming events will be here.</p>
    </div>

    <div id="pending" class="content-section">
      <h2>Registered Events</h2>
      <p>Events registered for.</p>
    </div>

    <div id="new" class="content-section">
      <h2>My comments</h2>

    </div>
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

?>