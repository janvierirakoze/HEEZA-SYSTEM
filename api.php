<?php
// HEZA SYSTEM storage API for XAMPP (Apache + PHP + MySQL/MariaDB).
// The database and table are created automatically on first use.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$cfg = ['host' => '127.0.0.1', 'user' => 'root', 'pass' => '', 'db' => 'heza_system'];
$allowed = ['currency', 'products', 'expenses', 'repayments'];

function out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $pdo = new PDO("mysql:host={$cfg['host']};charset=utf8mb4", $cfg['user'], $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['db']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$cfg['db']}`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS records (
        collection VARCHAR(32) NOT NULL,
        id VARCHAR(64) NOT NULL,
        data LONGTEXT NOT NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (collection, id)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $c = $_GET['c'] ?? '';
    if (!in_array($c, $allowed, true)) out(['error' => 'Unknown collection.'], 400);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $stmt = $pdo->prepare('SELECT id, data FROM records WHERE collection = ?');
        $stmt->execute([$c]);
        $rows = [];
        foreach ($stmt as $r) {
            $d = json_decode($r['data'], true);
            if (!is_array($d)) $d = [];
            $d['id'] = $r['id'];
            $rows[] = $d;
        }
        out($rows);
    }

    if ($method === 'POST') {
        $id = $_GET['id'] ?? '';
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $id)) out(['error' => 'Invalid id.'], 400);
        if (($_GET['action'] ?? '') === 'delete') {
            $pdo->prepare('DELETE FROM records WHERE collection = ? AND id = ?')->execute([$c, $id]);
            out(['ok' => true]);
        }
        $doc = json_decode(file_get_contents('php://input'), true);
        if (!is_array($doc)) out(['error' => 'Invalid JSON body.'], 400);
        $doc['id'] = $id;
        $pdo->prepare('INSERT INTO records (collection, id, data) VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE data = VALUES(data)')
            ->execute([$c, $id, json_encode($doc, JSON_UNESCAPED_UNICODE)]);
        out(['ok' => true]);
    }

    out(['error' => 'Method not allowed.'], 405);
} catch (Throwable $e) {
    out(['error' => 'Database error: ' . $e->getMessage()], 500);
}
