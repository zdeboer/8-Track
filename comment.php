<?php
require('connect.php');

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$query = "SELECT * FROM comments WHERE id = :id";
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
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/buttons.css">
    <link rel="stylesheet" href="styles/lists.css">
    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>Comment Admin Page</h2>
            <p>Logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="comments.php">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>

    <?php
    $query = "SELECT * FROM comments WHERE id = :id";
    $statement = $pdo->prepare($query);
    $statement->bindValue('id', $id, PDO::PARAM_INT);

    $statement->execute();

    if($statement->rowCount() > 0): ?>
      <?php $row = $statement->fetch() ?>
      <div class="user-standalone">
          <p class="username"><?= $row['username'] ?></p>
          <p class="user-email">Email: <?=$row['content']?></p>
          <p class="user-role">Role: <?= $row['role'] ?></p>
          <p class="user-date-joined">Joined: <?= date("M d y", strtotime($row['timestamp'])) ?></p>
          <br>
          <form method="post"><input class="delete-button" type="submit" value="Delete" name="delete"></form>
      </div>
    <?php else: ?>
      <p>User not found.</p>
    <?php endif ?>
      <form method="post" action="process_comment.php">
        <textarea id="comment" maxlength="255" placeholder="Comment here..." rows="4" col="50" name="comment"></textarea>
        <input type="submit" class="button">
    </form>
</body>
</html>