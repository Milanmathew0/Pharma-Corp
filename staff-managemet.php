<?php
// Include the PDO connection file
include "conn.php"; // Ensure this is your PDO connection

// SQL query to fetch user details
$sql = "SELECT user_id, username, role, email FROM users";
$stmt = $pdo->query($sql); // Execute the query

$users = [];

if ($stmt->rowCount() > 0) {
    // Fetch all rows and store them in the $users array
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = $row;
    }
}

// Handle the role update request (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $userId = $_POST['user_id'];
    $role = $_POST['role'];

    // Prepare SQL query to update the role
    $updateSql = "UPDATE users SET role = :role WHERE user_id = :user_id";
    $updateStmt = $pdo->prepare($updateSql);
    
    // Bind parameters
    $updateStmt->bindParam(':role', $role);
    $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

    // Execute the statement and return a JSON response
    if ($updateStmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update role"]);
    }
    exit; // End the script execution after handling the update
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User List</title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Font Awesome (for icons) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
  
</head>
<body>

<div class="container mt-5">
  <h2 class="mb-4">User List</h2>

  <!-- 🔍 Search Bar -->
   <!--  <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search users..." onkeyup="searchUsers()">-->

  <!-- 📋 User Table -->
  <table class="table table-bordered table-striped">
    <thead class="table-dark">
      <tr>
        <th>User ID</th>
        <th>Username</th>
        <th>Role</th>
        <th>Email</th>
      </tr>
    </thead>
    <tbody id="userTableBody">
    <?php
      // Loop through each user and create a table row for each one
      foreach ($users as $user) {
          echo "<tr>";
          echo "<td>{$user['user_id']}</td>";
          echo "<td>{$user['username']}</td>";
          echo "<td>";
          // Add a dropdown for selecting the role
          echo "<select class='form-select roleSelect' data-user-id='{$user['user_id']}'>
                  <option value='Staff' " . ($user['role'] == 'Staff' ? 'selected' : '') . ">Staff</option>
                  <option value='Customer' " . ($user['role'] == 'Customer' ? 'selected' : '') . ">Customer</option>
                </select>";
          echo "</td>";
          echo "<td>{$user['email']}</td>";
          echo "</tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<!-- ✅ JavaScript for Fetching Data and Role Change -->
<script>
  // Function to Fetch Users from Database (PHP)
  function fetchUsers() {
    fetch(window.location.href) // Fetch data from the same PHP file
      .then(response => response.text()) // Get the response text
      .then(data => {
        let tableBody = document.getElementById("userTableBody");
        tableBody.innerHTML = data.match(/<tbody[^>]*>(.*?)<\/tbody>/s)[1]; // Extract and insert the updated table body
        attachRoleChangeListeners(); // Reattach event listeners to the new dropdowns
      })
      .catch(error => console.error("Error fetching users:", error)); // Handle any errors
  }

  // Function to attach role change listeners to dropdowns
  function attachRoleChangeListeners() {
    document.querySelectorAll('.roleSelect').forEach(select => {
      select.addEventListener('change', function() {
        updateRole(this);
      });
    });
  }

  // Function to update the role for a user when dropdown changes
  function updateRole(selectElement) {
    const userId = selectElement.getAttribute('data-user-id');
    const newRole = selectElement.value;

    // Send the AJAX request to update the role in the database
    fetch(window.location.href, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: `user_id=${userId}&role=${newRole}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        console.log(`User ID: ${userId} role updated to ${newRole}`);
      } else {
        console.error("Failed to update role");
      }
    })
    .catch(error => console.error("Error updating role:", error));
  }

  // ✅ Search Function to Filter Users
  function searchUsers() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let tableRows = document.getElementById("userTableBody").getElementsByTagName("tr");

    // Loop through table rows and hide/show based on the search input
    for (let row of tableRows) {
      let text = row.textContent.toLowerCase();
      row.style.display = text.includes(input) ? "" : "none";
    }
  }

  // Load users when the page is loaded
  window.onload = fetchUsers;
</script>

</body>
</html>
