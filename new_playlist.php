<?php
require('connect.php');

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: index.html");
  exit();
}

if ($_POST && !empty($_POST['name']) && !empty($_POST['description'])) {
  $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
  
  $query = "INSERT INTO playlists (user_id, name, description) VALUES ($_SESSION[user_id],:name, :description)";
  $statement = $pdo->prepare($query);

  $statement->bindValue(':name', $name);
  $statement->bindValue(':description', $description);

  $posted = false;

  if($statement->execute()){
    $posted = true;
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Playlist</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
  <p>You have successfully logged in as: <?=$_SESSION['role']?></p>
  <a href="logout.php">Logout</a>

  <a href="dashboard.php">Back</a>

  <form method="post" action="new_playlist.php">
    <label for="name">Playlist Name:</label>
    <input type="text" id="name" name="name">
    <label for="description">Description:</label>
    <input type="text" id="description" name="description">
    <input type="submit" value="Create"></button>
  </form>
  <?php if (isset($posted) && $posted == true) {
    header("Location: dashboard.php");
  } 
  ?>
</body>
</html>