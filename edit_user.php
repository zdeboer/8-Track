<?php
require('connect.php');
require('authenticate.php');

if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $statement = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $statement->bindValue(":id", $id);
    if ($statement->execute()) {
        header("Location: users.php");
    }
} else if ($_POST && isset($_POST['username']) && isset($_POST['role']) && isset($_POST['id'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    $query = "UPDATE users SET username = :username, role = :role WHERE id = :id";
    $statement = $pdo->prepare($query);
    $statement->bindValue(':username', $username);        
    $statement->bindValue(':role', $role);
    $statement->bindValue(':id', $id, PDO::PARAM_INT);

    $statement->execute();

    header("Location: users.php");
    exit;
} else if (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    $query = "SELECT * FROM users WHERE id = :id";
    $statement = $pdo->prepare($query);
    $statement->bindValue(':id', $id, PDO::PARAM_INT);

    $statement->execute();
    $row = $statement->fetch();
} else {
    $id = false;
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
    <link rel="stylesheet" href="styles/forms.css">
    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
</head>
<body>
    <header>
        <div class="header-user-info">
            <h2>User Admin Page</h2>
            <p>Logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="user.php?id=<?= $_GET['id'] ?>">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <?php if ($id): ?>
    <form class="edit-form" method="post">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        
        <label for="username">Username</label>
        <input class="text-input" id="username" name="username" value="<?= $row['username'] ?>">
        <label for="role">Role</label>
        <select name="role" id="role">
          <option value="admin" 
          <?php if($row['role'] === 'admin'){
            echo 'selected';
            }?>>Admin</option>
          <option value="listener"
          <?php if($row['role'] === 'listener'){
            echo 'selected';
            }?>>Listener</option>
        </select>
        
        <input class="button" type="submit" name="submit" value="submit">
        <input class="button" type="submit" name="delete" value="delete">
    </form>
    <?php else: ?>
        <p>No user selected. <a href="users.php">Back</a></p>
    <?php endif ?>

    <!-- Remember that alternative syntax is good and html inside php is bad -->
    
</body>
</html>