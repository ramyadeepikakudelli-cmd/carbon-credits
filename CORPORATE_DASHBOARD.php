<?php
session_start();

// Prevent caching issues (fix back button / navigation redirect)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include database
include 'db.php';
include 'assets/icons.php';

// Handle language selection
if (isset($_POST['lang'])) {
    $lang = $_POST['lang'];
    $allowed = ['eng', 'hin', 'telugu'];
    if (in_array($lang, $allowed)) {
        $_SESSION['lang'] = $lang;
    }
}

// Load translations
$lang = $_SESSION['lang'] ?? "eng";
$translations = include "lang/$lang.php";
function t($key) { 
    global $translations; 
    return $translations[$key] ?? $key; 
}

// Check login & role
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'corporate') {
    header("Location: login.php");
    exit;
}

$corporate_id = $_SESSION['user_id'];

// Fetch corporate stats
$pooled_credits = $conn->query("SELECT credits FROM users WHERE id=$corporate_id")->fetch_assoc()['credits'] ?? 0;
$total_listings = $conn->query("SELECT COUNT(*) as cnt FROM marketplace WHERE seller_id=$corporate_id")->fetch_assoc()['cnt'] ?? 0;
$total_transactions = $conn->query("SELECT COUNT(*) as cnt FROM transactions WHERE seller_id=$corporate_id OR buyer_id=$corporate_id")->fetch_assoc()['cnt'] ?? 0;

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t("corporate_dashboard") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      <span class="brand-mark"><?= icon('leaf') ?></span>
      <span><?= t("corporate_dashboard") ?></span>
      <span class="navbar-meta">&middot; <?= htmlspecialchars($_SESSION['name']) ?></span>
    </div>
    <button class="navbar-toggle" type="button" aria-label="Menu"><?= icon('menu') ?></button>
    <div class="navbar-links">
        <a href="CORPORATE_DASHBOARD.php" class="active"><?= icon('dashboard') ?><?= t("corporate_dashboard") ?></a>
        <a href="PENDING_OFFERC.php"><?= icon('offers') ?><?= t("pending_offers") ?></a>
        <a href="MARKETPLACEC.php"><?= icon('cart') ?><?= t("marketplace") ?></a>
        <a href="TRANSACTIONC.php"><?= icon('transactions') ?><?= t("transaction") ?></a>
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
    <h2><?= t("corporate_statistics") ?></h2>
    <div class="stat-grid">
        <div class="stat-card accent">
            <div class="stat-icon"><?= icon('coins') ?></div>
            <div class="stat-value"><?= $pooled_credits ?></div>
            <div class="stat-label"><?= t("total_credits") ?></div>
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
// Popup functions
function showPopup(msg) {
    document.getElementById("popup-msg").innerText = msg;
    document.getElementById("popup").style.display = "flex";
}
function closePopup() {
    document.getElementById("popup").style.display = "none";
}

// Show any session message
<?php if (!empty($msg)) { ?>
    showPopup("<?= addslashes($msg) ?>");
<?php } ?>
</script>

</body>
</html>
