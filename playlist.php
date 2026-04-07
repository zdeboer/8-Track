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
  $noOfResults = "";
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
            if ($row['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
                $fallback = 'all_playlists.php';
            } elseif ($row['user_id'] != $_SESSION['user_id']) {
                $fallback = 'user.php?id=' . urlencode($row['user_id']);
            } else {
                $fallback = 'dashboard.php';
            }
        
            $backUrl = $fallback;
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $refHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
                $selfHost = $_SERVER['HTTP_HOST'];
                if ($refHost === $selfHost) {
                    $refPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?: '';
                    $refBase = basename($refPath);
                    $ignored = ['edit_playlist.php', 'playlist.php', 'process_comment.php', 'add_track.php', 'delete_track.php', 'upload.php', 'process_*'];
                    $isIgnored = in_array($refBase, $ignored, true) || preg_match('#process_#', $refBase);
                    if (!$isIgnored) {
                        $backUrl = $_SERVER['HTTP_REFERER'];
                    }
                }
            }
            ?>
        <div class="header-nav">
            <a class="button" href="<?= htmlspecialchars($backUrl, ENT_QUOTES) ?>">Back</a>
            <?php if($_SESSION['user_id'] == "GUEST"): ?>
            <a class="button" href="index.html">Log In</a>
            <?php else: ?>
            <a class="button" href="logout.php">Logout</a>
            <?php endif ?>   
        </div>
    </header>
    <main>
        <div class="playlist-page">
            <?php if($row['image'] != NULL): ?>
            <div class="img-container">
                <img style="height: 115px;" src="<?= htmlspecialchars($row['image'] ?? 'images/placeholder.png') ?>" alt="#">
            </div>
            <?php else: ?>
            <div class="placeholder-img-container">
                <img style="height: 115px;" src="images/placeholder.png" alt="#">
            </div>
            <?php endif ?>
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
                    <?php if($_SESSION['role'] == 'admin' || $row['user_id'] == $_SESSION['user_id']): ?>
                    <button type="button" class="delete-track-btn" data-row-id="<?= (int)$track_row_id ?>">Delete</button>
                    <?php endif ?>
                </li>
            <?php endwhile ?>
            </ul>
            
        <?php else: ?>
            <ul>
                <p>No songs.</p>
            </ul>
        <?php endif ?>
        <?php if($_SESSION['role'] == 'admin' || $row['user_id'] == $_SESSION['user_id']): ?>
        <br>
        <a class="button" href="edit_playlist.php?id=<?= $id ?>">Edit Playlist</a>
        <br><br>
            <h2>Add Songs: </h2>
            <form id="spotify-search-form">
                <input id="spotify-search-input" name="q" type="search" placeholder="Search to add songs">
                <button class="button" type="submit">Search</button>
            </form>
            
        
        
        <?php endif ?>
        <br><br>

        <h2>Comments</h2>

        
        <?php
        $query = "SELECT * FROM comments WHERE playlist_id = :id ORDER BY timestamp DESC;";
        $statement = $pdo->prepare($query);
        $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $statement->bindValue('id', $id, PDO::PARAM_INT);

        $statement->execute();
        ?>

        <?php if($_SESSION['user_id'] != 'GUEST'): ?>
        <form id="comment-form" method="post" action="process_comment.php">
            <input type="hidden" id="playlist-id" name="playlist_id" value=<?= $id ?>>
            <textarea style="max-width: 75vw; min-width: 75vw;" id="comment" maxlength="255" placeholder="Comment here..." rows="1" name="comment" required></textarea>
            <input type="submit" class="button" value="Comment">
        </form>
        <?php endif ?>
        <br>

        <?php
        if($statement->rowCount() > 0): ?>
            <ul id="comments">
            <?php while($row = $statement->fetch()): ?>
                <li class="comment">
                    <div class="comment-info">
                        <p class="comment-user"><strong><?= htmlspecialchars($row['username']) ?></strong> • <?= htmlspecialchars(date("M d y", strtotime($row['timestamp']))) ?></p>
                        <p class="comment-content"><?= $row['content'] ?></p>
                    </div>
                    <?php if($_SESSION['role'] == 'admin' || $row['user_id'] == $_SESSION['user_id']): ?>
                    <form method="post" action="process_comment.php" style="display:inline">
                        <input type="hidden" name="comment_id" value="<?= intval($row['id']) ?>">
                        <input type="hidden" name="playlist_id" value="<?= intval($_GET['id']) ?>">
                        <button class="delete-comment-button" type="submit" name="delete">Delete</button>
                    </form>
                    <?php endif ?>
                </li>
            <?php endwhile ?>
            </ul>
            
        <?php else: ?>
            <ul>
                <p>No comments on this playlist.</p>
            </ul>
        <?php endif ?>
        <br><br>

        <script>
            (function(){
                const form = document.getElementById('spotify-search-form');
                const input = document.getElementById('spotify-search-input');

                window.spotifySearchResults = [];

                // helper to create results container once
                function ensureResultsContainer() {
                    let container = document.querySelector('.add-results');
                    if (!container) {
                        container = document.createElement('div');
                        container.className = 'add-results';
                        const inner = document.createElement('div');
                        inner.id = 'spotify-results';
                        container.appendChild(inner);
                        form.insertAdjacentElement('afterend', container);
                    }
                    return container.querySelector('#spotify-results');
                }

                // render results and ensure click handler is attached once
                function renderResults(data) {
                    const resultsEl = ensureResultsContainer();
                    resultsEl.innerHTML = '';
                    if (!Array.isArray(data) || data.length === 0) {
                        resultsEl.textContent = 'No results';
                        return;
                    }

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
                            <style>
                                .add-track-btn {
                                    display: inline-block;
                                    border-radius: 16px;
                                    text-decoration: none;
                                    font-weight: bold;
                                    margin-right: 8px;
                                    transition: transform 0.07s ease-in-out, background-color 0.07s ease-in-out;
                                    font-size: 0.95em;
                                    padding: 6px 8px 6px 8px;
                                    background-color: #313131;
                                    border: 2px solid green;
                                    color: white;
                                }

                                .add-track-btn:hover {
                                    transform: scale(1.01);
                                    background-color:green;
                                    cursor: pointer;
                                }
                            </style>
                            <button data-track-id="${escapeHtml(track.id)}" class="add-track-btn">Add</button>
                        `;
                        resultsEl.appendChild(d);
                    });

                    // store globally for add handler lookup
                    window.spotifySearchResults = data;
                }

                form.addEventListener('submit', async function(e){
                    e.preventDefault();
                    const q = input.value.trim();
                    if (!q) return;
                    const resultsEl = ensureResultsContainer();
                    resultsEl.textContent = 'Searching...';

                    try {
                        const resp = await fetch('spotify_search.php?q=' + encodeURIComponent(q) + '&limit=30', { credentials: 'same-origin' });
                        const data = await resp.json();
                        renderResults(data);
                    } catch (err) {
                        console.error(err);
                        const resultsEl = ensureResultsContainer();
                        resultsEl.textContent = 'Search failed';
                    }
                });

                // delegate Add button clicks from document (works even when container is created later)
                document.addEventListener('click', async function(e){
                    const btn = e.target.closest('.add-track-btn');
                    if (!btn) return;
                    const spotifyId = btn.getAttribute('data-track-id');
                    if (!spotifyId) return;

                    const track = (window.spotifySearchResults || []).find(t => t.id === spotifyId);
                    const title = track ? track.name : '';
                    const artist = track ? (Array.isArray(track.artists) ? track.artists.join(', ') : (track.artists || '')) : '';
                    const album_image = track ? (track.album_image || '') : '';

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