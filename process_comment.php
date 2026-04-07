<?php
  require 'connect.php';

  session_start();

  if ($_SERVER["REQUEST_METHOD"] == "POST") {


      $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_SANITIZE_NUMBER_INT);    
      $playlist_id = filter_input(INPUT_POST, 'playlist_id', FILTER_SANITIZE_NUMBER_INT);

      if (!$playlist_id) {
          $playlist_id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
      }


      $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);

      if (isset($_POST['delete'])) {

        if (!$playlist_id && $comment_id) {
            $s = $pdo->prepare("SELECT playlist_id FROM comments WHERE id = :id");
            $s->bindValue(':id', $comment_id, PDO::PARAM_INT);
            $s->execute();
            $playlist_id = $s->fetchColumn();
        }
        if ($comment_id) {
            $statement = $pdo->prepare("DELETE FROM comments WHERE id = :id");
            $statement->bindValue(":id", $comment_id, PDO::PARAM_INT);
            $statement->execute();
        }
            header("Location: playlist.php?id=$playlist_id");
            exit();
      
        } 

      try {
          $statement = $pdo->prepare("INSERT INTO comments (username, user_id, playlist_id, content) VALUES (:username, $_SESSION[user_id], :playlist_id, :comment)");

          $statement->bindValue(':username', $_SESSION['username']);

          $statement->bindValue(':playlist_id', $playlist_id);

          $statement->bindValue(':comment', $comment);
          
          $statement->execute();

          $comment_posted = true;

          header("Location: playlist.php?id=$playlist_id");
          exit();

      } catch (PDOException $e) {
          $error = "Error: " . $e->getMessage();
      }
  }
?>
