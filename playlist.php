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
    $filter = "title ASC";
}

if (isset($_POST['no-of-results'])) {
  $noOfResults = $_POST['no-of-results'];
} else {
  $noOfResults = "LIMIT 10";
}

$query = "SELECT * FROM playlists WHERE id = :id";
$statement = $pdo->prepare($query);

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$statement->bindValue('id', $id, PDO::PARAM_INT);

$statement->execute();

$row = $statement->fetch();

if (isset($_POST['delete'])) {
    $statement = $pdo->prepare("DELETE FROM playlists WHERE id = :id");
    $statement->bindValue(":id", $id);
    if ($statement->execute()) {
        header("Location: dashboard.php");
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
        <?php 
            if($row['user_id'] != $_SESSION['user_id']) {
                $redirect = "user.php?id=$row[user_id]";
            } else {
                $redirect = "dashboard.php";
            }
        ?>
        <div class="header-nav">
            <a class="button" href=<?= $redirect ?>>Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <main>
        <div class="playlist-page">
            <div class="img-container">
                <img style="height: 115px;" src="<?= htmlspecialchars($row['image'] ?? 'images/placeholder.png') ?>" alt="#">
            </div>
            <div class="playlist-info-page">
                <h2><?=$row['name']?></h2>
                <br>
                <p><?=$row['description']?></p>
            </div>
        </div>
    
        <br>

        <p>Add songs at the bottom of the page</p>

        <form action="playlist.php?id=<?= $id ?>" method="post">
            <?php
            $selected_value = $_POST['filter'] ?? 'added_at DESC';
            

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
              $selected_num = $_POST['no-of-results'] ?? ' LIMIT 10';
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
        $query = "SELECT * FROM playlist_tracks WHERE playlist_id = :id ORDER BY $filter $noOfResults;";
        $statement = $pdo->prepare($query);
        $statement->bindValue('id', $id, PDO::PARAM_INT);

        $statement->execute();

        if($statement->rowCount() > 0): ?>
            <ul id="playlist-tracks">
            <?php while($row = $statement->fetch()): ?>
                <?php $track_row_id = $row['id']; ?>
                <li class="track" data-row-id="<?= (int)$track_row_id ?>">
                    <div class="img-container">
                        <img src="<?= htmlspecialchars($row['album_image'] ?? 'images/placeholder.png') ?>" alt="#">
                    </div>
                    <div class="track-info">
                        <p class="track-title"><?= htmlspecialchars($row['title']) ?></p>
                        <p class="artist"><?= htmlspecialchars($row['artist']) ?></p>
                        <p class="artist"><?= htmlspecialchars( ($row['genre'] ?? '') ? ucwords(explode(',', $row['genre'])[0]) : '' ) ?></p>
                        <p class="track-timestamp"><?= htmlspecialchars(date("M d y", strtotime($row['added_at']))) ?></p>
                    </div>
                    <button type="button" class="delete-track-btn" data-row-id="<?= (int)$track_row_id ?>">Delete</button>
                </li>
            <?php endwhile ?>
            </ul>
            <br><br>
            <h2>Add Songs: </h2>
            <form id="spotify-search-form">
                <input id="spotify-search-input" name="q" type="search" placeholder="Search to add songs">
                <button class="button" type="submit">Search</button>
            </form>
            
            <div class="add-results">
                <div id="spotify-results"></div>
            </div>
        <?php else: ?>
            <ul>
                <p>No songs.</p>
            </ul>
            <h2>Add Songs: </h2>

            <form id="spotify-search-form">
                <input id="spotify-search-input" name="q" type="search" placeholder="Search to add songs" />
                <button class="button" type="submit">Search</button>
            </form>
            
            <ul class="add-results">
                <div id="spotify-results"></div>
            </ul>
        <?php endif ?>
        <form method="post"><input class="delete-button" type="submit" value="Delete Playlist" name="delete">
            <a class="button" href="edit_playlist.php?id=<?= $id ?>">Edit</a>
        </form>

        
        <form method="post" action="process_comment.php">
            <textarea id="comment" maxlength="255" placeholder="Comment here..." rows="4" name="comment"></textarea>
            <input type="submit" class="button">
        </form>

        <script>
            (function(){
                const form = document.getElementById('spotify-search-form');
                const input = document.getElementById('spotify-search-input');
                const resultsEl = document.getElementById('spotify-results');

                window.spotifySearchResults = [];

                form.addEventListener('submit', async function(e){
                    e.preventDefault(); // stop normal navigation
                    const q = input.value.trim();
                    if (!q) return;
                    resultsEl.textContent = 'Searching...';

                    const resp = await fetch('spotify_search.php?q=' + encodeURIComponent(q) + '&limit=30', { credentials: 'same-origin' });
                    // if spotify_search.php returns raw JSON array/object this will parse it
                    const data = await resp.json();

                    // store it in a global variable you can use elsewhere in this page
                    window.spotifySearchResults = data;

                    // simple render so you can see results (adjust as needed)
                    if (!Array.isArray(data) || data.length === 0) {
                        resultsEl.textContent = 'No results';
                        return;
                    }

                    resultsEl.innerHTML = '';
                    data.forEach(track => {
                        const d = document.createElement('li');
                        d.className = 'track';
                        d.innerHTML = `
                            <div class="img-container">
                                <img src="${track.album_image || ''}">
                            </div>
                            <div class="track-info">
                                <strong class="track-title">${escapeHtml(track.name)}</strong>
                                <div class="artist">${escapeHtml((track.artists || []).join(', '))}</div>
                            </div>
                            <button data-track-id="${escapeHtml(track.id)}" 
                            class="add-track-btn" class="button">Add</button>
                        `;
                        resultsEl.appendChild(d);
                    });
                });

                // example: attach handler to "Add" buttons that reads window.spotifySearchResults if needed

                resultsEl.addEventListener('click', async function(e){
                    const btn = e.target.closest('.add-track-btn');
                    if (!btn) return;
                    const spotifyId = btn.getAttribute('data-track-id');
                    if (!spotifyId) return;

                    // find track object from the last search results
                    const track = (window.spotifySearchResults || []).find(t => t.id === spotifyId);
                    const title = track ? track.name : '';
                    const artist = track ? (Array.isArray(track.artists) ? track.artists.join(', ') : (track.artists || '')) : '';
                    const album_image = track ? (track.album_image || '') : '';

                    // Disable UI while adding
                    btn.disabled = true;
                    const prevText = btn.textContent;
                    btn.textContent = 'Adding...';

                    try {
                        const resp = await fetch('/add_track.php', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                playlist_id: <?= json_encode((int)$id) ?>,
                                spotify_track_id: spotifyId,
                                title: title,
                                artist: artist,
                                album_image: album_image
                            })
                        });
                        const json = await resp.json();
                        if (resp.ok && json.ok) {
                            btn.textContent = 'Added';
                            btn.disabled = true;
                        } else {
                            btn.disabled = false;
                            btn.textContent = prevText;
                            alert(json.error || 'Add failed');
                        }
                    } catch (err) {
                        console.error(err);
                        btn.disabled = false;
                        btn.textContent = prevText;
                        alert('Network error');
                    }
                });
                
                const playlistTracksEl = document.getElementById('playlist-tracks');
                if (playlistTracksEl) {
                    playlistTracksEl.addEventListener('click', async function(e){
                        const btn = e.target.closest('.delete-track-btn');
                        if (!btn) return;
                        const rowId = btn.dataset.rowId;
                        if (!rowId) return;
                        if (!confirm('Delete this track?')) return;

                        btn.disabled = true;
                        const prevText = btn.textContent;
                        btn.textContent = 'Deleting...';

                        try {
                            const resp = await fetch('/delete_track.php', {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ id: parseInt(rowId, 10) })
                            });
                            const json = await resp.json();
                            if (resp.ok && json.ok) {
                                const li = btn.closest('li');
                                if (li) li.remove();
                            } else {
                                alert(json.error || 'Delete failed');
                                btn.disabled = false;
                                btn.textContent = prevText;
                            }
                        } catch (err) {
                            console.error(err);
                            alert('Network error');
                            btn.disabled = false;
                            btn.textContent = prevText;
                        }
                    });
                }
                function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
            })();
        </script>
    </main> 
</body>
</html>