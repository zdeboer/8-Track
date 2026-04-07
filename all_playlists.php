<?php
require('connect.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// whitelist allowed ORDER BY values
$allowed = [
    'created_at DESC',
    'updated_at DESC',
    'name ASC',
    'name DESC'
];
$selected_value = $_POST['filter'] ?? 'updated_at DESC';
$filter = in_array($selected_value, $allowed, true) ? $selected_value : 'updated_at DESC';

// fetch playlists joined with owner username
$query = "SELECT playlists.id, playlists.name, playlists.description, playlists.image, playlists.created_at, playlists.updated_at, users.username
          FROM playlists
          INNER JOIN users ON playlists.user_id = users.id
          ORDER BY $filter";
$statement = $pdo->prepare($query);
$statement->execute();
$playlists = $statement->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discover Playlists</title>
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
            <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
            <p>You have successfully logged in as: <strong><?= htmlspecialchars($_SESSION['role']) ?></strong></p>
        </div>
        <div class="header-nav">
            <?php if($_SESSION['role'] == 'admin') :?>
            <a class="button" href="users.php">User Admin Page</a>
            <?php endif ?>
            <?php if($_SESSION['user_id'] == "GUEST"): ?>
            <a class="button" href="index.html">Log In</a>
            <?php else: ?>
            <a class="button" href="logout.php">Logout</a>
            <?php endif ?>  
        </div>
    </header>
    <main>
        <h2>Discover</h2>

        <br>
        
        <a class="button" href="songs.php">All Songs</a>
        
        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] !== "GUEST") : ?>
        <a class="button" href="dashboard.php">Your Library</a>
        <?php endif ?>

        <div class="filter-header">
            <form action="all_playlists.php" method="post">
                <?php
                $options = [
                    'created_at DESC' => 'Created At',
                    'updated_at DESC' => 'Recently Updated',
                    'name ASC' => 'A-Z',
                    'name DESC' => 'Z-A'
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
                <input type="submit" class="button" id="filter-button" value="Filter">
            </form>
            <div id="playlist-search-root"></div>
        </div>
        <!-- server rendered list (will be replaced by search results) -->
        <?php if(count($playlists) > 0): ?>
            <ul id="playlist-list">
            <?php foreach($playlists as $row): ?>
                <li class="playlist">
                    <div class="img-container">
                        <img src="<?= htmlspecialchars($row['image'] ?? 'images/placeholder.png') ?>" alt="">
                    </div>
                    <div class="playlist-info">
                        <p class="playlist-title">
                            <a href="playlist.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></a>
                            • <?= htmlspecialchars($row['username']) ?>
                        </p>
                        <p class="playlist-content"><?= htmlspecialchars($row['description']) ?></p>
                        <p class="playlist-timestamp"><?= date("M d y", strtotime($row['created_at'])) ?></p>
                    </div>
                </li>
            <?php endforeach ?>
            </ul>
        <?php else: ?>
            <p id="no-playlists">No playlists.</p>
        <?php endif ?>

        <!-- expose playlists to JS safely -->
        <script>
          window.INIT_PLAYLISTS = <?= json_encode($playlists, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>;
        </script>

        <!-- React (CDN) + Babel for quick integration (dev only) -->
        <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
        <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
        <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

        <script type="text/babel">
        const { useState, useEffect, useMemo } = React;

        function escapeHtml(s) {
          return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
        }

        // render playlist array into the existing UL
        function renderPlaylistList(list) {
          const ul = document.getElementById('playlist-list');
          const noEl = document.getElementById('no-playlists');
          if ((!list || list.length === 0) && noEl) {
            if (ul) ul.remove();
            return;
          }
          let container = ul;
          if (!container) {
            container = document.createElement('ul');
            container.id = 'playlist-list';
            const root = document.getElementById('playlist-search-root');
            root.insertAdjacentElement('afterend', container);
          }
          container.innerHTML = list.map(p => {
            const img = p.image ? p.image : 'images/placeholder.png';
            const name = p.name || '';
            const desc = p.description || '';
            const owner = p.username || '';
            const created = p.created_at ? new Date(p.created_at).toLocaleDateString(undefined, { month:'short', day:'2-digit', year:'2-digit' }) : '';
            return `
              <li class="playlist">
                <div class="img-container">
                  <img src="${escapeHtml(img)}" alt="">
                </div>
                <div class="playlist-info">
                  <p class="playlist-title"><a href="playlist.php?id=${encodeURIComponent(p.id)}">${name}</a> • ${owner}</p>
                  <p class="playlist-content">${desc}</p>
                  <p class="playlist-timestamp">${created}</p>
                </div>
              </li>
            `;
          }).join('');
          if (noEl) noEl.remove();
        }

        function PlaylistSearch({ initialPlaylists = [] }) {
          const [query, setQuery] = useState('');
          const [debounced, setDebounced] = useState('');
          useEffect(() => {
            const t = setTimeout(() => setDebounced(query.trim()), 200);
            return () => clearTimeout(t);
          }, [query]);

          const results = useMemo(() => {
            if (!debounced) return [];
            const q = debounced.toLowerCase();
            return initialPlaylists.filter(p =>
              (p.name||'').toLowerCase().includes(q) ||
              (p.description||'').toLowerCase().includes(q) ||
              (p.username||'').toLowerCase().includes(q)
            );
          }, [debounced, initialPlaylists]);

          useEffect(() => {
            if (!debounced) {
              renderPlaylistList(initialPlaylists);
            } else {
              renderPlaylistList(results);
            }
          }, [debounced, results, initialPlaylists]);

          return (
            <div className="playlist-search">
              <input
                class="text-input"
                type="search"
                placeholder="Search playlists"
                value={query}
                onChange={e => setQuery(e.target.value)}
                aria-label="Search playlists"
              />
            </div>
          );
        }

        // mount immediately
        const init = window.INIT_PLAYLISTS || [];
        const rootEl = document.getElementById('playlist-search-root');
        if (rootEl) {
          ReactDOM.createRoot(rootEl).render(
            <PlaylistSearch initialPlaylists={init} />
          );
        }
        </script>

    </main>
</body>
</html>