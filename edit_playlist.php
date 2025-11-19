<?php
require('connect.php');
require('authenticate.php');

if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $statement = $pdo->prepare("DELETE FROM playlists WHERE id = :id");
    $statement->bindValue(":id", $id);
    if ($statement->execute()) {
        header("Location: dashboard.php");
    }
} else if ($_POST && isset($_POST['name']) && isset($_POST['description']) && isset($_POST['id'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    if($_POST['delete-image'] === true) {
      $imagePath = NULL;
    } else {
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
    }
    
    

    $query = "UPDATE playlists SET name = :name, description = :description, image = :image WHERE id = :id";
    $statement = $pdo->prepare($query);
    $statement->bindValue(':name', $name);        
    $statement->bindValue(':description', $description);
    $statement->bindValue(':image', $imagePath);
    $statement->bindValue(':id', $id, PDO::PARAM_INT);

    $statement->execute();

    header("Location: playlist.php?id=$id");
    exit;
} else if (isset($_GET['id'])) {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

    $query = "SELECT * FROM playlists WHERE id = :id";
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
            <h2>Edit Playlist</h2>
            <p>Logged in as: <strong><?=$_SESSION['role']?></strong></p>
        </div>
        <div class="header-nav">
            <a class="button" href="playlist.php?id=<?= $id ?>">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <?php if ($id): ?>
    <form class="edit-form" method="post">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        
        <label for="name">Playlist Name</label>
        <input class="text-input" id="name" name="name" value="<?= $row['name'] ?>">
        <label for="description">Description</label>
        <input class="text-input" id="description" name="description" value="<?= $row['description'] ?>">

        <label>
          Playlist image (optional):
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" value="<?= $row['image'] ?>">
        </label>
        <label for="delete-image">Delete image?</label>
        <input name="delete-image" type="checkbox">

        
        
        <input class="button" type="submit" name="submit" value="submit">
        <input class="button" type="submit" name="delete" value="delete">
    </form>
    <?php else: ?>
        <p>No user selected. <a href="dashboard.php">Back</a></p>
    <?php endif ?>

    <!-- Remember that alternative syntax is good and html inside php is bad -->
    
</body>
</html>