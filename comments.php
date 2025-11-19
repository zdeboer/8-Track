<?php
require('connect.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ./index.html");
    exit();
}

if (isset($_POST['filter'])) {
    $filter = $_POST['filter'];
} else {
    $filter = "timestamp DESC";
}

$query = "SELECT * FROM comments ORDER BY $filter LIMIT 20";

$statement = $pdo->prepare($query);

$statement->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comment Admin Page</title>
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
            <h2>Comment Admin Page</h2>
            <p>Logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="users.php">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <main>
        <form action="comments.php" method="post">
            <?php
            $selected_value = $_POST['filter'] ?? '';

            $options = [
                'timestamp DESC' => 'Recent',
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
        </form>

        

        <?php if($statement->rowCount() > 0): ?>
            <ul>
            <?php while($row = $statement->fetch()): ?>
                <li class="user">
                    <p class="username"><a href="comment.php?id=<?=$row['id']?>"><?= $row['username'] ?></a></p>
                    <p class="user-email"><?=$row['content']?></p>
                    <p class="user-role"><?= $_SESSION['role'] ?></p>
                    <p class="user-date-joined"><?= date("M d y", strtotime($row['timestamp'])) ?></p>
                </li>
            <?php endwhile ?>
            </ul>
        <?php else: ?>
            <p>No users on platform.</p>
        <?php endif ?>       
    </main>
</body>
</html>