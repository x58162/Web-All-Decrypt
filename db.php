<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("DB 連線失敗: " . $e->getMessage());
}

// ============================
// 取得使用者
// ============================
function getUserByUserId($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE userId = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// ============================
// 新增使用者（不存在才新增）
// ============================
function addUserIfNotExists($userId, $displayName, $statusMessage, $pictureUrl, $sol_address, $sol_private_key, $vip = 0) {
    if (!getUserByUserId($userId)) {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO users
            (userId, displayName, statusMessage, pictureUrl, sol_address, sol_private_key, vip)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $displayName, $statusMessage, $pictureUrl, $sol_address, $sol_private_key, $vip]);
    }
}
