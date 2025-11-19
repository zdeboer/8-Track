<?php
// GET https://8-track.iceiy.com/spotify_search.php?q=daft+punk&limit=10

require_once __DIR__ . '/spotify_config.php';
require_once __DIR__ . '/src/SpotifyClient.php';

use App\SpotifyClient;

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
$limit = intval($_GET['limit'] ?? 10);

if ($q === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing query parameter q']);
    exit;
}

if (SPOTIFY_CLIENT_ID === 'your_client_id_here' || SPOTIFY_CLIENT_SECRET === 'your_client_secret_here') {
    http_response_code(500);
    echo json_encode(['error' => 'set SPOTIFY_CLIENT_ID and SPOTIFY_CLIENT_SECRET in spotify_config.php']);
    exit;
}

try {
    $client = new SpotifyClient(SPOTIFY_CLIENT_ID, SPOTIFY_CLIENT_SECRET);
    $tracks = $client->searchTracks($q, $limit);
    echo json_encode($tracks);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>