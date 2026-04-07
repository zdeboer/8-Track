<?php
require __DIR__ . '/connect.php';

$adminUsername = getenv('ADMIN_USERNAME') ?: 'zdeboer';
$adminEmail    = getenv('ADMIN_EMAIL') ?: 'zackdb2005@gmail.com';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'AlphaArrow77*';
$adminRole     = 'admin';

function hasColumn(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT 1'
        . ' FROM INFORMATION_SCHEMA.COLUMNS'
        . ' WHERE TABLE_SCHEMA = DATABASE()'
        . ' AND TABLE_NAME = ?'
        . ' AND COLUMN_NAME = ?'
        . ' LIMIT 1'
    );
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

$hasRole = hasColumn($pdo, 'users', 'role');
$hasJoinedAt = hasColumn($pdo, 'users', 'joined_at');

$hash = password_hash($adminPassword, PASSWORD_DEFAULT);

$pdo->beginTransaction();

$find = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$find->execute([$adminUsername]);
$existingId = $find->fetchColumn();

if ($existingId) {
    $sql = "UPDATE users SET email = ?, password = ?"
         . ($hasRole ? ", role = ?" : "")
         . " WHERE id = ?";
    $params = [$adminEmail, $hash];
    if ($hasRole) $params[] = $adminRole;
    $params[] = $existingId;

    $upd = $pdo->prepare($sql);
    $upd->execute($params);
    echo "Updated admin user '{$adminUsername}' (id={$existingId})\n";
} else {
    $cols = ["username", "email", "password"];
    $vals = ["?", "?", "?"];
    $params = [$adminUsername, $adminEmail, $hash];

    if ($hasRole) {
        $cols[] = "role";
        $vals[] = "?";
        $params[] = $adminRole;
    }
    if ($hasJoinedAt) {
        $cols[] = "joined_at";
        $vals[] = "NOW()";
    }

    $sql = "INSERT INTO users (" . implode(",", $cols) . ") VALUES (" . implode(",", $vals) . ")";
    $ins = $pdo->prepare($sql);
    $ins->execute($params);

    echo "Inserted admin user '{$adminUsername}' (new id=" . $pdo->lastInsertId() . ")\n";
}

$pdo->commit();