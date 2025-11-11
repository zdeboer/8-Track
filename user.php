<?php
require('connect.php');

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$query = "SELECT * FROM playlists WHERE id = :id";
$statement = $pdo->prepare($query);

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$statement->bindValue('id', $id, PDO::PARAM_INT);

$statement->execute();

$row = $statement->fetch();

if (isset($_POST['delete'])) {
    $statement = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $statement->bindValue(":id", $id);
    if ($statement->execute()) {
        header("Location: users.php");
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Admin Page</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>User Admin Page</h2>
    <p>Logged in as: <?=$_SESSION['role']?></p>
    <a href="logout.php">Logout</a>

    <a href="users.php">Back</a>

    <?php
    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $pdo->prepare($query);
    $statement->bindValue('id', $id, PDO::PARAM_INT);

    $statement->execute();

    if($statement->rowCount() > 0): ?>
      <?php $row = $statement->fetch() ?>
      <div class="user">
          <p class="username"><?= $row['username'] ?></p>
          <p class="user-email">Email: <?=$row['email']?></p>
          <p>Role: <?= $row['role'] ?></p>
          <p class="user-date-joined">Joined: <?= date("M d y", strtotime($row['joined_at'])) ?></p>
          <form method="post"><input type="submit" value="Delete" name="delete"></form>
      </div>
    <?php else: ?>
      <p>User not found.</p>
    <?php endif ?>
</body>
</html>