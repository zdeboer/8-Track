<?php
require('connect.php');

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if (isset($_POST['filter'])) {
    $filter = $_POST['filter'];
} else {
    $filter = "updated_at DESC";
}

$query = "SELECT * FROM playlists WHERE user_id = $_SESSION[user_id] ORDER BY $filter LIMIT 20";

$statement = $pdo->prepare($query);

$statement->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home Page</title>
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
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
            <p>You have successfully logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <?php if($_SESSION['role'] == 'admin') :?>
            <a class="button" href="users.php">User Admin Page</a>
            <?php endif ?>
            <?php if($_SESSION['user_id'] == "GUEST") : ?>
            <a class="button" href="index.html">Log In</a>
            <?php else: ?>
            <a class="button" href="logout.php">Logout</a>
            <?php endif ?>  
        </div>
    </header>
    <main>
        <h2>Your Library</h2>

        <br>
        
        <a class="button" href="songs.php">All Songs</a>
       
        <a class="button" class="create-playlist" href="new_playlist.php">Create playlist</a>

        <form action="dashboard.php" method="post">
            <?php
            $selected_value = $_POST['filter'] ?? '';

            $options = [
                'updated_at DESC' => 'Recent',
                'name ASC' => 'A-Z',
                'name DESC' => 'Z-A'
            ];
            ?>

            <select name="filter">
                <?php foreach ($options as $value => $text): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>"
                        <?php if ($value === $selected_value) echo 'selected="selected"'; ?>>
                        <?php echo htmlspecialchars($text); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" class="button" id="filter-button" value="Filter">
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

        <form method="post" action="process_comment.php">
            <textarea id="comment" maxlength="255" placeholder="Comment here..." rows="4" col="50" name="comment"></textarea>
            <input type="submit" class="button">
        </form>
    </main>
</body>
</html>