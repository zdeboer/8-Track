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
    <link rel="icon" type="image/x-icon" href="images/8.svg.svg">
  </head>
  <body>
    <?php if($comment_posted == true) : ?>
      <p>Comment submitted!</p>
      <br>
      <p>User: <strong><?= $_SESSION['username'] ?></strong></p>
      <p>Comment:<?= $comment ?></p>
      <p><a href="dashboard.php">Go back to Dashboard</a></p>
    <?php else :?>
      <p><?= $error ?></p>
    <?php endif ?>  
  </body>
</html>