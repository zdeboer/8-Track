<?php
require_once __DIR__ . '/connect.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

$id = isset($input['id']) ? intval($input['id']) : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'missing_id']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.user_id AS owner_id, pt.playlist_id AS playlist_id
     FROM playlist_tracks pt
     JOIN playlists p ON pt.playlist_id = p.id
     WHERE pt.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}

$owner = $row['owner_id'];
$playlistId = $row['playlist_id'];

if ($owner != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'not_allowed']);
    exit;
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM playlist_tracks WHERE id = ?');
    $del->execute([$id]);

    $up = $pdo->prepare('UPDATE playlists SET updated_at = NOW() WHERE id = ?');
    $up->execute([$playlistId]);

    $pdo->commit();

    echo json_encode(['ok' => true]);
} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'msg' => $e->getMessage()]);
}
?>