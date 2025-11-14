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
    $statement = $pdo->prepare("DELETE FROM playlists WHERE id = :id");
    $statement->bindValue(":id", $id);
    if ($statement->execute()) {
        header("Location: dashboard.php");
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home Page</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>You have successfully logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a href="dashboard.php">Back</a>
            <a href="logout.php">Logout</a>  
        </div>
    </header>
    <main>
        <h2><?=$row['name']?></h2>
        <p><?=$row['description']?></p>

        <?php
        $query = "SELECT * FROM playlist_tracks WHERE playlist_id = :id";
        $statement = $pdo->prepare($query);
        $statement->bindValue('id', $id, PDO::PARAM_INT);

        $statement->execute();

        if($statement->rowCount() > 0): ?>
            <ul>
            <?php while($row = $statement->fetch()): ?>
                <li class="track">
                    <div class="img-container">
                        <img src="images/sebtp.jpg">
                    </div>
                    <div class="track-info">
                        <p class="track-title"><?= $row['title'] ?></p>
                        <p class="artist"><?=$row['artist']?></p>
                        <p class="track-timestamp"><?= date("M d y", strtotime($row['added_at'])) ?></p>
                    </div>
                </li>
            <?php endwhile ?>
            </ul>
        <?php else: ?>
            <ul>
                <p>No songs.</p>
            </ul>
        <?php endif ?>
        <form method="post"><input class="delete-button" type="submit" value="Delete Playlist" name="delete"></form>
        <form method="post" action="process_comment.php">
            <textarea id="comment" maxlength="255" placeholder="Comment here..." rows="4" col="50" name="comment"></textarea>
            <input type="submit" class="button">
        </form>
    </main> 
</body>
</html>