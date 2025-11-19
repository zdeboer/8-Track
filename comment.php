<?php
require_once __DIR__ . '/connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /index.html");
    exit();
}

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$id) {
    echo 'Missing id';
    exit;
}

$query = "SELECT * FROM comments WHERE id = :id";
$statement = $pdo->prepare($query);
$statement->bindValue(':id', $id, PDO::PARAM_INT);
$statement->execute();
$row = $statement->fetch();

if (isset($_POST['delete'])) {
    $del = $pdo->prepare("DELETE FROM comments WHERE id = :id");
    $del->bindValue(':id', $id, PDO::PARAM_INT);
    if ($del->execute()) {
        header("Location: comments.php");
        exit;
    } else {
        $error = 'Delete failed';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comment Admin Page</title>
    <link rel="stylesheet" href="/styles/main.css">
    <link rel="stylesheet" href="/styles/header.css">
    <link rel="stylesheet" href="/styles/buttons.css">
    <link rel="stylesheet" href="/styles/lists.css">
    <link rel="icon" type="image/x-icon" href="/images/8.svg.svg">
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>Comment Admin Page</h2>
            <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['role'] ?? '') ?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="comments.php">Back</a>
            <a class="button" href="logout.php">Logout</a>
        </div>
    </header>

    <?php if ($row): ?>
      <div class="user-standalone">
          <p class="username"><?= htmlspecialchars($row['username'] ?? '') ?></p>
          <p class="user-email">Comment: <?=$row['content'] ?></p>
          <p class="user-date-joined">Comment Made: <?= htmlspecialchars(date("M d y", strtotime($row['timestamp'] ?? 'now'))) ?></p>
          <br>
          <form method="post"><input class="delete-button" type="submit" value="Delete" name="delete"></form>
      </div>
    <?php else: ?>
      <p>Comment not found.</p>
    <?php endif; ?>
</body>
</html>