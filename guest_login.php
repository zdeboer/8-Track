<?php
  session_start();
  require 'connect.php';

  $_SESSION['user_id'] = "GUEST";
  $_SESSION['username'] = "Guest";
  $_SESSION['role'] = "listener";

  header("Location: songs.php");
  exit();
?>