<?php
require_once __DIR__ . '/connect.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// require login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

// read JSON body (also accepts form POST fallback)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$playlist_id = isset($input['playlist_id']) ? intval($input['playlist_id']) : 0;
$spotify_track_id = isset($input['spotify_track_id']) ? trim($input['spotify_track_id']) : '';
$title = isset($input['title']) ? trim($input['title']) : '';
$artist = isset($input['artist']) ? trim($input['artist']) : '';
$album_image = isset($input['album_image']) ? trim($input['album_image']) : '';

if (!$playlist_id || $spotify_track_id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing_parameters']);
    exit;
}

// verify playlist exists and belongs to current user
$stmt = $pdo->prepare('SELECT user_id FROM playlists WHERE id = ? LIMIT 1');
$stmt->execute([$playlist_id]);
$owner = $stmt->fetchColumn();

if (!$owner) {
    http_response_code(404);
    echo json_encode(['error' => 'playlist_not_found']);
    exit;
}
if ($owner != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'not_allowed']);
    exit;
}

// insert the spotify track id + title + artist + album_image into playlist_tracks
try {
    $stmt = $pdo->prepare('INSERT INTO playlist_tracks (playlist_id, spotify_track_id, title, artist, album_image, added_by, added_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$playlist_id, $spotify_track_id, $title, $artist, $album_image, $_SESSION['user_id']]);
    echo json_encode(['ok' => true]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'msg' => $e->getMessage()]);
}
?>