<?php
  require 'connect.php';

  session_start();

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
      $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);

      try {
          $statement = $pdo->prepare("INSERT INTO comments (username, user_id, content) VALUES (:username, $_SESSION[user_id], :comment)");

          $statement->bindValue(':username', $_SESSION['username']);

          $statement->bindValue(':comment', $comment);
          
          $statement->execute();

          $comment_posted = true;

      } catch (PDOException $e) {
          $error = "Error: " . $e->getMessage();
      }
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Comment Submitted!</title>
    <link rel="stylesheet" href="styles/main.css">
    <link rel="stylesheet" href="styles/forms.css">
    <link rel="stylesheet" href="styles/buttons.css">

    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
  </head>
  <body class="comment-processed">
    <?php if($comment_posted == true) : ?>
      <h2>Comment submitted!</h2>
      <br>
      <p class="user">User: <strong><?= $_SESSION['username'] ?></strong></p>
      <p class="playlist-content">Comment:<?= $comment ?></p>
      <p><a class="button" href="dashboard.php">Go back to Dashboard</a></p>
    <?php else :?>
      <p><?= $error ?></p>
    <?php endif ?>  
  </body>
</html>