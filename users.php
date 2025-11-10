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
    <title>Home Page</title>
</head>
<body>
    <h2>User Admin Page</h2>
    <p>logged in as: <?=$_SESSION['role']?></p>
    <a href="logout.php">Logout</a>

    <a href="dashboard.php">Back</a>
    <br>

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
        <input type="submit" value="Filter">
    </form>

    <?php if($statement->rowCount() > 0): ?>
        <ul>
        <?php while($row = $statement->fetch()): ?>
            <li class="user">
                <p class="username"><a href="user.php?id=<?=$row['id']?>"><?= $row['username'] ?></a></p>
                <p class="user-email"><?=$row['email']?></p>
                <p><?= $row['role'] ?></p>
                <p class="user-date-joined"><?= date("M d y", strtotime($row['joined_at'])) ?></p>
            </li>
        <?php endwhile ?>
        </ul>
    <?php else: ?>
        <p>No users on platform.</p>
    <?php endif ?>


</body>
</html>