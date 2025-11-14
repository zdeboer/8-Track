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
    $filter = "joined_at DESC";
}

$query = "SELECT * FROM users ORDER BY $filter LIMIT 20";

$statement = $pdo->prepare($query);

$statement->execute();
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
            <a class="button" href="dashboard.php">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <main>
        <form action="users.php" method="post">
            <?php
            $selected_value = $_POST['filter'] ?? '';

            $options = [
                'joined_at DESC' => 'Recent',
                'username ASC' => 'A-Z',
                'username DESC' => 'Z-A'
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
            <input class="button" type="submit" value="Filter" id="filter-button">
            <a style="float:right" href="comments.php" class="button">Comments</a>
        </form>

        

        <?php if($statement->rowCount() > 0): ?>
            <ul>
            <?php while($row = $statement->fetch()): ?>
                <li class="user">
                    <p class="username"><a href="user.php?id=<?=$row['id']?>"><?= $row['username'] ?></a></p>
                    <p class="user-email"><?=$row['email']?></p>
                    <p class="user-role"><?= $row['role'] ?></p>
                    <p class="user-date-joined"><?= date("M d y", strtotime($row['joined_at'])) ?></p>
                </li>
            <?php endwhile ?>
            </ul>
        <?php else: ?>
            <p>No users on platform.</p>
        <?php endif ?>  
        <form method="post" action="process_comment.php">
            <textarea id="comment" maxlength="255" placeholder="Comment here..." rows="4" col="50" name="comment"></textarea>
            <input type="submit" class="button">
        </form>      
    </main>
</body>
</html>