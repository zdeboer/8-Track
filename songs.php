<?php
require('connect.php');
require('spotify_config.php');
require_once __DIR__ . '/src/SpotifyClient.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if (isset($_POST['filter'])) {
    $filter = $_POST['filter'];
} else {
    $filter = " added_at DESC";
}

if (isset($_POST['no-of-results'])) {
  $noOfResults = $_POST['no-of-results'];
} else {
  $noOfResults = "";
}

$backFallback = 'dashboard.php';
$backUrl = $backFallback;
if (!empty($_SERVER['HTTP_REFERER'])) {
    $refHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $selfHost = $_SERVER['HTTP_HOST'];
    if ($refHost === $selfHost) {
        $refPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?: '';
        $refBase = basename($refPath); // e.g. "songs.php"
        $currentBase = basename($_SERVER['PHP_SELF']); // current script basename
        // files to ignore as referrers (processors, upload endpoints, or pages you don't want to use)
        $ignored = ['process_comment.php', 'add_track.php', 'delete_track.php', 'upload.php'];
        $isIgnored = in_array($refBase, $ignored, true) || preg_match('#^process_#', $refBase);
        // also ignore if the referrer is this same page (filtering/pagination POST)
        if (!$isIgnored && $refBase !== $currentBase) {
            $backUrl = $_SERVER['HTTP_REFERER'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home Page</title>
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
            <?php if($_SESSION['user_id'] == "GUEST") : ?>
              <a class="button" href="index.html">Log In</a>
            <?php else: ?>
              <a class="button" href="<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>">Back</a>
              <a class="button" href="logout.php">Logout</a>
            <?php endif ?>
        </div>
    </header>
    <main>
        <h2>All Songs</h2>
        <p>Here is all the songs ever uploaded by all users on 8-Track</p>
        <br>
        <a class="button" href="all_playlists.php">Discover</a>
        <br><br>
        <?php if($_SESSION['user_id'] == "GUEST") : ?>
          <p style="color: red;">As a guest, you can only view the full songs list, register to create your own playlists.</p>
        <?php endif ?>

        <form method="post">
            <?php
            $selected_value = $_POST['filter'] ?? 'Recent';

            $options = [
                'added_at DESC' => 'Recent',
                'title ASC' => 'A-Z',
                'title DESC' => 'Z-A',
                'genre ASC' => 'Genre'
            ];
            ?>

            <select name="filter">
                <?php foreach ($options as $value => $text): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>"
                        <?php if ($value === $selected_value) echo 'selected="selected"'; ?>>
                        <?php echo htmlspecialchars($text); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
              $selected_num = $_POST['no-of-results'] ?? '';
              $resultNum = [
                ' LIMIT 10' => '10',
                ' LIMIT 25' => '25',
                ' LIMIT 50' => '50',
                ' LIMIT 100' => '100',
                '' => 'All'
              ]
            ?>
            <select name="no-of-results">
                <?php foreach ($resultNum as $value => $text): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>"
                        <?php if ($value === $selected_num) echo 'selected="selected"'; ?>>
                        <?php echo htmlspecialchars($text); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" class="button" id="filter-button" value="Filter">
        </form>
        <?php
        $query = "SELECT *
          FROM (
              SELECT
                  *,
                  ROW_NUMBER() OVER (PARTITION BY spotify_track_id ORDER BY id) as rn
              FROM
                  playlist_tracks
          ) as subquery
          WHERE
              rn = 1
          ORDER BY $filter $noOfResults;";
        $statement = $pdo->prepare($query);

        $statement->execute();

        if($statement->rowCount() > 0): ?>
            <ul>
                <?php if($noOfResults == ''): ?>
                <li style="font-size: 18px; background: none; padding: 0; padding-left: 16px;"><strong>Results: <?= $statement->rowCount() ?></strong></li>
                <?php endif ?>
            <?php while($row = $statement->fetch()): ?>
                <li class="track">
                    <div class="img-container">
                        <img src="<?= $row['album_image'] ?>" alt="#">
                    </div>
                    <div class="track-info">
                        <p class="track-title"><?= $row['title'] ?></p>
                        <p class="artist"><?=$row['artist']?></p>
                        <p class="artist"><?= ucwords(substr($row['genre'], 0, strpos($row['genre'], ','))) ?></p>
                        <p class="track-timestamp"><?= date("M d y", strtotime($row['added_at'])) ?></p>
                    </div>
                </li>
            <?php endwhile ?>
            </ul>
        <?php else: ?>
            <ul>
                <p>No songs.</p>
            </ul>
        <?php endif ?>
        <br><br>
    </main> 
    
</body>
</html>