<?php
session_start();
include "conn.php"; // Database connection

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "Staff";

    $checkSql = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($checkSql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $message = "<div class='alert alert-danger'>Email already exists!</div>";
    } else {
        $insertSql = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->bindParam(':username', $username);
        $insertStmt->bindParam(':email', $email);
        $insertStmt->bindParam(':password', $password);
        $insertStmt->bindParam(':role', $role);

        if ($insertStmt->execute()) {
            $message = "<div class='alert alert-success'>Registration successful! <a href='login.php'>Login here</a></div>";
        } else {
            $message = "<div class='alert alert-danger'>Error occurred!</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Registration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
  <h2 class="mb-4 text-center">Staff Registration</h2>

  <?= $message; ?>

  <form action="" method="POST" class="shadow p-4 rounded bg-light">
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input type="text" name="username" id="username" class="form-control" required>
    </div>
    

    <div class="mb-3">
      <label for="email" class="form-label">Email</label>
      <input type="email" name="email" id="email" class="form-control" required>
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" id="password" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary w-100">Register</button>
  </form>

  <p class="text-center mt-3">Already registered? <a href="login.php">Login here</a></p>
</div>

</body>
</html>
