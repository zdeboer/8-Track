<?php
require('connect.php');
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: index.html");
  exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';

    $imagePath = null;

    if (!empty($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $maxBytes = 2 * 1024 * 1024;
            if ($_FILES['image']['size'] > $maxBytes) {
                $error = 'Image too large (max 2MB).';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['image']['tmp_name']);
                $ext = null;
                if ($mime === 'image/jpeg') $ext = 'jpg';
                elseif ($mime === 'image/png') $ext = 'png';
                elseif ($mime === 'image/webp') $ext = 'webp';

                if ($ext === null) {
                    $error = 'Unsupported image type. Use JPG, PNG or WEBP.';
                } else {
                    $uploadDir = __DIR__ . '/uploads/playlist_images';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                    $dest = $uploadDir . '/' . $filename;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        $error = 'Failed to move uploaded file.';
                    } else {
                        $imagePath = 'uploads/playlist_images/' . $filename;
                    }
                }
            }
        } else {
            $error = 'Upload error.';
        }
    }

    if ($error === '') {
        $query = "INSERT INTO playlists (user_id, name, description, image) VALUES (:user_id, :name, :description, :image)";
        $statement = $pdo->prepare($query);
        $statement->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $statement->bindValue(':name', $name);
        $statement->bindValue(':description', $description);
        $statement->bindValue(':image', $imagePath);
        if ($statement->execute()) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Database insert failed.';
        }
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
            <a class="button" href="dashboard.php">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>

  <div id="login-body">
    <div class="login-panel">
      <br>
      <h2>Create a Playlist</h2>
      <br>
      <?php if ($error): ?><p class="error"><?=htmlspecialchars($error)?></p><?php endif; ?>
      <form class="edit-form" method="post" action="new_playlist.php" enctype="multipart/form-data">
        <input class="text-input" type="text" id="name" name="name" placeholder="Playlist Name" required>
        <input class="text-input" type="text" id="description" name="description" placeholder="Description" required>
        <label>Playlist image (optional):</label>
          
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
        
        <input class="button" type="submit" value="Create">
      </form>
    </div>
  </div>
</body>
</html>