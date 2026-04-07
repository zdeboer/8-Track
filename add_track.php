<?php
require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/spotify_config.php';
require_once __DIR__ . '/src/SpotifyClient.php';

use App\SpotifyClient;

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$playlist_id = isset($input['playlist_id']) ? intval($input['playlist_id']) : 0;
$spotify_track_id = isset($input['spotify_track_id']) ? trim($input['spotify_track_id']) : '';
$title = isset($input['title']) ? trim($input['title']) : '';
$artist = isset($input['artist']) ? trim($input['artist']) : '';
$album_image = isset($input['album_image']) ? trim($input['album_image']) : '';
$genre = isset($input['genre']) ? trim($input['genre']) : '';

if (!$playlist_id || $spotify_track_id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing_parameters']);
    exit;
}

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

if ($genre === '') {
    try {
        if (!defined('SPOTIFY_CLIENT_ID') || !defined('SPOTIFY_CLIENT_SECRET')) {
            $genre = '';
        } else {
            $client = new SpotifyClient(SPOTIFY_CLIENT_ID, SPOTIFY_CLIENT_SECRET);
            $genre = $client->getTrackGenres($spotify_track_id);
        }
    } catch (\Throwable $e) {
        $genre = '';
    }
}

try {
    $stmt = $pdo->prepare('INSERT INTO playlist_tracks (playlist_id, spotify_track_id, title, artist, album_image, genre, added_by, added_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$playlist_id, $spotify_track_id, $title, $artist, $album_image, $genre, $_SESSION['user_id']]);

    $up = $pdo->prepare('UPDATE playlists SET updated_at = NOW() WHERE id = ?');
    $up->execute([$playlist_id]);

    echo json_encode(['ok' => true, 'genre' => $genre]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'msg' => $e->getMessage()]);
}
?>