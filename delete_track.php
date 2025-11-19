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
    'SELECT p.user_id FROM playlist_tracks pt JOIN playlists p ON pt.playlist_id = p.id WHERE pt.id = ? LIMIT 1'
);
$stmt->execute([$id]);
$owner = $stmt->fetchColumn();

if (!$owner) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit;
}
if ($owner != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['error' => 'not_allowed']);
    exit;
}

try {
    $del = $pdo->prepare('DELETE FROM playlist_tracks WHERE id = ?');
    $del->execute([$id]);
    echo json_encode(['ok' => true]);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'msg' => $e->getMessage()]);
}
?>