<?php
require('connect.php');

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$query = "SELECT * FROM playlists WHERE user_id = $_SESSION[user_id] ORDER BY updated_at DESC LIMIT 20";

$statement = $pdo->prepare($query);

$statement->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home Page</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    <p>You have successfully logged in as: <?=$_SESSION['role']?></p>
    <a href="logout.php">Logout</a>

    <a href="new_playlist.php">Create a new playlist</a>

    <form>
        <select>
            <option value="recent">Recent</option>
            <option value="a-z">A-Z</option>
            <option value="z-a">Z-A</option>
        </select>
    </form>
    

    <?php if($statement->rowCount() > 0): ?>
        <ul>
        <?php while($row = $statement->fetch()): ?>
            <li class="playlist">
                <p class="playlist-title"><a href="playlist.php?id=<?=$row['id']?>"><?= $row['name'] ?></a></p>
                <p class="playlist-content"><?=$row['description']?></p>
                <p class="playlist-timestamp"><?= date("M d y", strtotime($row['created_at'])) ?></p>
            </li>
        <?php endwhile ?>
        </ul>
    <?php else: ?>
        <p>No playlists.</p>
    <?php endif ?>


</body>
</html>