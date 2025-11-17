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
            <a href="dashboard.php">Back</a>
            <a href="logout.php">Logout</a>  
        </div>
    </header>

  
  <div id="login-body">
    <div class="login-panel">
      <h2>Create a Playlist</h2>
      <form method="post" action="new_playlist.php">
        <input type="text" id="name" name="name" placeholder="Playlist Name">
        <input type="text" id="description" name="description" placeholder="Description">
        <input type="submit" value="Create"></button>
      </form>
      <?php if (isset($posted) && $posted == true) {
        header("Location: dashboard.php");
      } 
      ?>
    </div>
  </div>
</body>
</html>