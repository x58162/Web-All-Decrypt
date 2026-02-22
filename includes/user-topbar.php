<?php
session_start();

$isLoggedIn = isset($_SESSION['userId']);
$displayName = '';
$pictureUrl = 'images/default-avatar.png';

if ($isLoggedIn) {
    $user = getUserByUserId($_SESSION['userId']);
    if ($user) {
        $displayName = $user['displayName'];
        if ($user['pictureUrl']) $pictureUrl = $user['pictureUrl'];
    }
} else {
    $state = $_SESSION['line_state'] ?? bin2hex(random_bytes(16));
    $_SESSION['line_state'] = $state;
    $line_login_url = "https://access.line.me/oauth2/v2.1/authorize?" .
        "response_type=code&client_id=" . LINE_CLIENT_ID .
        "&redirect_uri=" . urlencode(LINE_REDIRECT_URI) .
        "&state=$state&scope=profile openid email";
}
?>

<?php if($isLoggedIn): ?>
<div class="user-container" onclick="toggleDropdown()">
    <img src="<?= htmlspecialchars($pictureUrl) ?>" class="user-avatar" alt="頭像">
    <span class="user-name"><?= htmlspecialchars($displayName) ?></span>
    <div id="dropdownMenu" class="dropdown-content">
        <a href="profile.php">個人資料</a>
        <a href="analysis.php">技術分析</a>
        <a href="logout.php">登出</a>
    </div>
</div>
<?php else: ?>
<a href="<?= $line_login_url ?>" class="login-btn">
    <img src="images/line-logo.svg" alt="LINE Logo">用 LINE 登入
</a>
<?php endif; ?>
