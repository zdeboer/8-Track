<?php
require 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $retype_password = $_POST['retype-password'];

    if (empty($username) || empty($email) || empty($password)) {
        die("Please fill in all fields.");
    }

    if($retype_password != $password) {
        die("Passwords do not match. Try again.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        
        $stmt->execute([$username, $email, $hashed_password]);

        if (!isset($_SESSION['user_id'])) {
            header("Location: users.php");
            exit();
        } else {
            echo "Registration successful! You can now <a href='index.html'>log in</a>.";
        }

    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo "Error: Username or Email already exists. Please choose another.";
        } else {
            echo "Error: " . $e->getMessage();
        }
    }
}
?>