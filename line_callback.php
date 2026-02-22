<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'db.php';

// -----------------------
// 驗證 LINE 登入 code / state
// -----------------------
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    header("Location: index.php");
    exit;
}

if ($_GET['state'] !== ($_SESSION['line_state'] ?? '')) {
    die("❌ state 驗證失敗");
}

$code = $_GET['code'];

// -----------------------
// 取得 LINE Access Token
// -----------------------
$token_url = "https://api.line.me/oauth2/v2.1/token";
$data = [
    "grant_type" => "authorization_code",
    "code" => $code,
    "redirect_uri" => LINE_REDIRECT_URI,
    "client_id" => LINE_CLIENT_ID,
    "client_secret" => LINE_CLIENT_SECRET
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded"]);

$response = curl_exec($ch);
$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    die("❌ 取得 access token 失敗: " . htmlspecialchars($response));
}

$access_token = $token_data['access_token'];

// -----------------------
// 取得使用者 Profile
// -----------------------
$ch2 = curl_init("https://api.line.me/v2/profile");
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
$profile_response = curl_exec($ch2);
$profile_data = json_decode($profile_response, true);

$userId = $profile_data['userId'] ?? null;
$displayName = $profile_data['displayName'] ?? '';
$statusMessage = $profile_data['statusMessage'] ?? '';
$pictureUrl = $profile_data['pictureUrl'] ?? '';

if (!$userId) {
    die("❌ 取得 LINE 使用者 ID 失敗");
}

// -----------------------
// 檢查使用者是否存在
// -----------------------
$stmt = $pdo->prepare("SELECT * FROM users WHERE userId = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// -----------------------
// 需要生成冷錢包的情況
// -----------------------
if (!$user || empty($user['sol_address'])) {
    // 呼叫 Node 生成錢包 (dotenv debug 關閉)
    $walletJsonRaw = shell_exec("node " . escapeshellarg(__DIR__ . "/js/generate_wallet.js") . " 2>&1");

    // 取最後一行作為 JSON
    $lines = explode("\n", trim($walletJsonRaw));
    $walletJson = end($lines);

    $wallet = json_decode($walletJson, true);
    if (!$wallet || !isset($wallet['address'], $wallet['private_key'])) {
        die("❌ Node JSON 解析失敗: " . htmlspecialchars($walletJsonRaw));
    }

    $sol_address = $wallet['address'];
    $sol_private_key = $wallet['private_key'];

    if (!$user) {
        // 新增使用者
        $stmt = $pdo->prepare("
            INSERT INTO users
            (userId, displayName, statusMessage, pictureUrl, sol_address, sol_private_key, vip)
            VALUES (?, ?, ?, ?, ?, ?, 0)
        ");
        $stmt->execute([$userId, $displayName, $statusMessage, $pictureUrl, $sol_address, $sol_private_key]);
    } else {
        // 更新 sol 地址/私鑰
        $stmt = $pdo->prepare("
            UPDATE users
            SET sol_address = ?, sol_private_key = ?
            WHERE userId = ?
        ");
        $stmt->execute([$sol_address, $sol_private_key, $userId]);
    }
}

// -----------------------
// 記錄 session
// -----------------------
$_SESSION['userId'] = $userId;
$_SESSION['displayName'] = $displayName;

// -----------------------
// 導回首頁
// -----------------------
header("Location: index.php");
exit;
