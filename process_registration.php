<?php
// process_registration.php
require 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Password is raw input before hashing

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        die("Please fill in all fields.");
    }

    // 1. Hash the password securely using PASSWORD_DEFAULT (bcrypt)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // 2. Prepare the SQL statement using PDO placeholders (?)
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        
        // 3. Execute the statement with user data
        $stmt->execute([$username, $email, $hashed_password]);

        echo "Registration successful! You can now <a href='index.html'>log in</a>.";

    } catch (PDOException $e) {
        // Handle potential errors, such as duplicate username/email
        if ($e->getCode() == 23000) {
            echo "Error: Username or Email already exists. Please choose another.";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>