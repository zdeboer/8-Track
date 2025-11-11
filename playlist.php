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
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>You have successfully logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <?php if($_SESSION['role'] == 'admin') :?>
            <a href="users.php">User Admin Page</a>
            <?php endif ?>
            <a href="logout.php">Logout</a>  
        </div>
    </header>
    <main>
        <a href="dashboard.php">Back</a>

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
                    <p class="track-title"><?= $row['title'] ?></p>
                    <p><?=$row['artist']?></p>
                    <p class="track-id"><?=$row['spotify_track_id']?></p>
                    <p class="track-timestamp"><?= date("M d y", strtotime($row['added_at'])) ?></p>
                </li>
            <?php endwhile ?>
            </ul>
            
        <?php else: ?>
            <ul>
                <p>No songs.</p>
            </ul>
        <?php endif ?>
        <form method="post"><input class="delete-button" type="submit" value="Delete Playlist" name="delete"></form>
    </main> 
</body>
</html>