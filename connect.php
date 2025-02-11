<?php
// Database connection
$host = "localhost";
$dbname = "pharma";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_id = trim($_POST['user_id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm-password'];

        // Check if passwords match
        if ($password !== $confirmPassword) {
            die("Passwords do not match!");
        } // ✅ FIX: Removed the stray 's'

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $sql = "INSERT INTO users (user_id, username, email, password) VALUES (:user_id, :username, :email, :password)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        // Fetch the last inserted user_id
        $lastUserId = $pdo->lastInsertId();
        echo "Registration successful! Your User ID is: " . $lastUserId;
        header("Location: login.php");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
