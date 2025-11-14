<?php
require('connect.php');
require('spotify_config.php');
require_once __DIR__ . '/src/SpotifyClient.php';


session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
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
        <div class="header-nav">
            <a class="button" href="dashboard.php">Back</a>
            <a class="button" href="logout.php">Logout</a>  
        </div>
    </header>
    <main>
        <h2><?=$row['name']?></h2>
        <p><?=$row['description']?></p>

        </form>
            <form id="spotify-search-form">
            <input id="spotify-search-input" name="q" type="search" placeholder="Search Spotify tracks" />
            <button class="button" type="submit">Search</button>
        </form>

        <?php
        $query = "SELECT * FROM playlist_tracks WHERE playlist_id = :id";
        $statement = $pdo->prepare($query);
        $statement->bindValue('id', $id, PDO::PARAM_INT);

        $statement->execute();

        if($statement->rowCount() > 0): ?>
            <ul>
            <?php while($row = $statement->fetch()): ?>
                <li class="track">
                    <div class="img-container">
                        <img src="<?= $row['album_image'] ?>">
                    </div>
                    <div class="track-info">
                        <p class="track-title"><?= $row['title'] ?></p>
                        <p class="artist"><?=$row['artist']?></p>
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
        <form method="post"><input class="delete-button" type="submit" value="Delete Playlist" name="delete"></form>
        <form method="post" action="process_comment.php">
            <textarea id="comment" maxlength="255" placeholder="Comment here..." rows="4" col="50" name="comment"></textarea>
            <input type="submit" class="button">
        </form>
            <form id="spotify-search-form">
            <input id="spotify-search-input" name="q" type="search" placeholder="Search Spotify tracks" />
            <button class="button" type="submit">Search</button>
        </form>

        <div id="spotify-results"></div>

        <script>
            /* minimal: fetch JSON into a variable without redirect */
            (function(){
                const form = document.getElementById('spotify-search-form');
                const input = document.getElementById('spotify-search-input');
                const resultsEl = document.getElementById('spotify-results');

                // this will hold the JSON returned from spotify_search.php
                window.spotifySearchResults = [];

                form.addEventListener('submit', async function(e){
                    e.preventDefault(); // stop normal navigation
                    const q = input.value.trim();
                    if (!q) return;
                    resultsEl.textContent = 'Searching...';

                    const resp = await fetch('/spotify_search.php?q=' + encodeURIComponent(q) + '&limit=30', { credentials: 'same-origin' });
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
                        const d = document.createElement('div');
                        d.className = 'spotify-track';
                        d.innerHTML = `
                            <img src="${track.album_image || ''}" style="width:48px;height:48px;object-fit:cover;margin-right:8px;">
                            <strong>${escapeHtml(track.name)}</strong>
                            <div>${escapeHtml((track.artists || []).join(', '))}</div>
                            <button data-track-id="${escapeHtml(track.id)}" class="add-track-btn">Add</button>
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
                
                function escapeHtml(s){ return String(s||'').replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
            })();
        </script>
    </main> 
</body>
</html>