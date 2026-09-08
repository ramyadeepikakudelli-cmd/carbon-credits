<?php
session_start();

// Prevent caching for session security
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

include 'db.php';
include 'assets/icons.php';

// Handle language selection
$allowed_langs = ['eng', 'hin', 'telugu'];
if (isset($_POST['lang']) && in_array($_POST['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_POST['lang'];
}

// Default language
$lang = $_SESSION['lang'] ?? 'eng';
$translations = include "lang/$lang.php";
function t($key) { global $translations; return $translations[$key] ?? $key; }

// Check login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'community') {
    header("Location: login.php");
    exit;
}

$community_id = $_SESSION['user_id'];
$community_name = htmlspecialchars($_SESSION['name']);

// Fetch stats
$member_count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE panchayat_id=$community_id AND status='approved'")->fetch_assoc()['total'];
$pooled_credits = $conn->query("SELECT SUM(credits) AS total FROM users WHERE panchayat_id=$community_id")->fetch_assoc()['total'] ?? 0;
$total_listings = $conn->query("SELECT COUNT(*) AS cnt FROM marketplace WHERE seller_id=$community_id")->fetch_assoc()['cnt'];
$total_transactions = $conn->query("SELECT COUNT(*) AS cnt FROM transactions WHERE seller_id=$community_id OR buyer_id=$community_id")->fetch_assoc()['cnt'];

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t("community_dashboard") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      <span class="brand-mark"><?= icon('leaf') ?></span>
      <span><?= t("community_dashboard") ?></span>
      <span class="navbar-meta">&middot; <?= $community_name ?></span>
    </div>
    <button class="navbar-toggle" type="button" aria-label="Menu"><?= icon('menu') ?></button>
    <div class="navbar-links">
        <a href="community_dashboard.php" class="active"><?= icon('dashboard') ?><?= t("community_dashboard") ?></a>
        <a href="pending_users.php"><?= icon('users') ?><?= t("pending_user_requests") ?></a>
        <a href="pending_offers.php"><?= icon('offers') ?><?= t("pending_offers") ?></a>
        <a href="marketplace.php"><?= icon('cart') ?><?= t("marketplace") ?></a>
        <a href="transactions.php"><?= icon('transactions') ?><?= t("transactions") ?></a>
        <form method="POST" style="margin:6px 12px;">
            <select name="lang" class="lang-input" onchange="this.form.submit()">
                <option value="eng" <?= ($lang=='eng')?'selected':'' ?>>English</option>
                <option value="hin" <?= ($lang=='hin')?'selected':'' ?>>हिंदी</option>
                <option value="telugu" <?= ($lang=='telugu')?'selected':'' ?>>తెలుగు</option>
            </select>
        </form>
        <a href="logout.php"><?= icon('logout') ?><?= t("logout") ?></a>
    </div>
  </div>
</nav>

<div class="container fade-in">
    <h2><?= t("community_statistics") ?></h2>
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon"><?= icon('users') ?></div>
            <div class="stat-value"><?= $member_count ?></div>
            <div class="stat-label"><?= t("approved_members") ?></div>
        </div>
        <div class="stat-card accent">
            <div class="stat-icon"><?= icon('coins') ?></div>
            <div class="stat-value"><?= $pooled_credits ?></div>
            <div class="stat-label"><?= t("total_credits_pooled") ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><?= icon('cart') ?></div>
            <div class="stat-value"><?= $total_listings ?></div>
            <div class="stat-label"><?= t("marketplace_listings") ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><?= icon('transactions') ?></div>
            <div class="stat-value"><?= $total_transactions ?></div>
            <div class="stat-label"><?= t("total_transactions") ?></div>
        </div>
    </div>
</div>

<!-- Popup for messages -->
<div class="popup" id="popup">
    <div class="popup-content">
        <p id="popup-msg"></p>
        <button class="btn btn-primary" onclick="closePopup()"><?= t("ok") ?></button>
    </div>
</div>

<script src="assets/nav.js"></script>
<script>
function showPopup(msg) {
    document.getElementById("popup-msg").innerText = msg;
    document.getElementById("popup").style.display = "flex";
}
function closePopup() {
    document.getElementById("popup").style.display = "none";
}
<?php if(!empty($msg)) { ?>
    showPopup("<?= addslashes($msg) ?>");
<?php } ?>
</script>

</body>
</html>
