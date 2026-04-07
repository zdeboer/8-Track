<?php
require('connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if (isset($_POST['delete'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $statement = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $statement->bindValue(":id", $id);
    if ($statement->execute()) {
        header("Location: users.php");
    }
} else if ($_POST && isset($_POST['username']) && isset($_POST['role']) && isset($_POST['id'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    if(!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET username = :username, email = :email, role = :role, password = :password WHERE id = :id";
        $statement = $pdo->prepare($query);
        $statement->bindValue(':password', $hashed_password);
        
    } else {
        $query = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id";
        $statement = $pdo->prepare($query);
    }

    
    $statement->bindValue(':username', $username);
    $statement->bindValue(':email', $email);
    $statement->bindValue(':role', $role);
    
    $statement->bindValue(':id', $id, PDO::PARAM_INT);

    $statement->execute();

    header("Location: users.php");
    exit;
} else if (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $pdo->prepare($query);
    $statement->bindValue(':id', $id, PDO::PARAM_INT);

    $statement->execute();
    $row = $statement->fetch();
} else {
    $id = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Admin Page</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/buttons.css">
    <link rel="stylesheet" href="styles/lists.css">
    <link rel="stylesheet" href="styles/forms.css">
    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>User Admin Page</h2>
            <p>Logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="user.php?id=<?= $_GET['id'] ?>">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <?php if ($id): ?>
    <form class="edit-form" method="post">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        
        <label for="username">Username</label>
        <input class="text-input" id="username" name="username" value="<?= $row['username'] ?>">
        <input class="text-input" id="email" name="email" value="<?= $row['email'] ?>">
        <label for="role">Role</label>
        <select name="role" id="role">
          <option value="admin" 
          <?php if($row['role'] === 'admin'){
            echo 'selected';
            }?>>Admin</option>
          <option value="listener"
          <?php if($row['role'] === 'listener'){
            echo 'selected';
            }?>>Listener</option>
        </select>

        <label for="password">Password</label>
        <input type="password" class="text-input" id="password" name="password">
        
        <input class="button" type="submit" name="submit" value="submit">
        <input class="button" type="submit" name="delete" value="delete">
    </form>
    <?php else: ?>
        <p>No user selected. <a href="users.php">Back</a></p>
    <?php endif ?>
</body>
</html>