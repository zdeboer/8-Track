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
        <h2>Your Library</h2>
       
        <a class="create-playlist" href="new_playlist.php">Create playlist</a>

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
            <input type="submit" id="filter-button" value="Filter">
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
    </main>
</body>
</html>