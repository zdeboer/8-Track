<?php
require('connect.php');

session_start();

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
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/buttons.css">
    <link rel="stylesheet" href="styles/lists.css">
    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>User Admin Page</h2>
            <p>Logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="users.php">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>

    <?php
    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $pdo->prepare($query);
    $statement->bindValue('id', $id, PDO::PARAM_INT);

    $statement->execute();

    if($statement->rowCount() > 0): ?>
      <?php $row = $statement->fetch() ?>
      <div class="user-standalone">
          <p class="username"><?= $row['username'] ?></p>
          <p class="user-email">Email: <?=$row['email']?></p>
          <p class="user-role">Role: <?= $row['role'] ?></p>
          <p class="user-date-joined">Joined: <?= date("M d y", strtotime($row['joined_at'])) ?></p>
          <br>
          <div style="display:flex;">
            <form method="post"><input class="delete-button" type="submit" value="Delete" name="delete"></form>
            <a class="button" href="edit_user.php?id=<?= $row['id'] ?>">Edit User</a>
          </div>
          

      </div>
    <?php else: ?>
      <p>User not found.</p>
    <?php endif ?>

    <?php
    $query = "SELECT * FROM playlists WHERE user_id = :user_id";
    $statement = $pdo->prepare($query);
    $statement->bindValue(':user_id', $row['id'], PDO::PARAM_INT);

    $statement->execute();

    if($statement->rowCount() > 0): ?>
            <ul>
            <?php while($row = $statement->fetch()): ?>
                <li class="playlist">
                    <div class="img-container">
                        <img src="<?= htmlspecialchars($row['image'] ?? 'images/placeholder.png') ?>" alt="#">
                    </div>
                    <div class="playlist-info">
                        <p class="playlist-title"><a href="playlist.php?id=<?=$row['id']?>"><?= $row['name'] ?></a></p>
                        <p class="playlist-content"><?=$row['description']?></p>
                        <p class="playlist-timestamp"><?= date("M d y", strtotime($row['created_at'])) ?></p>
                    </div>
                </li>
            <?php endwhile ?>
            </ul>
        <?php else: ?>
            <ul>
                <p>No playlists.</p>
            </ul>
            
        <?php endif ?>
</body>
</html>