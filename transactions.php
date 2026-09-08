<?php
session_start();
include 'db.php';
include 'assets/icons.php';

// --- Language Handling ---
$allowed_langs = ['eng', 'hin', 'telugu'];
if (isset($_POST['lang']) && in_array($_POST['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_POST['lang'];
}
$lang = $_SESSION['lang'] ?? 'eng';
$translations = include "lang/$lang.php";
function t($key) { global $translations; return $translations[$key] ?? $key; }

// --- Check Login & Role ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'community') {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$msg = "";

// --- Handle Add Money ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_money'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $msg = $stmt->execute() ? t("success_add_money") . " ₹$amount!" : t("fail_add_money");
    } else {
        $msg = t("invalid_amount");
    }
}

// --- Fetch Balance ---
$balance = $conn->query("SELECT balance FROM users WHERE id=$user_id")->fetch_assoc()['balance'] ?? 0;

// --- Fetch Transactions ---
$transactions = $conn->query("
    SELECT t.*, u1.name AS buyer_name, u2.name AS seller_name
    FROM transactions t
    JOIN users u1 ON t.buyer_id = u1.id
    JOIN users u2 ON t.seller_id = u2.id
    WHERE t.buyer_id=$user_id OR t.seller_id=$user_id
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= t("transactions_title") ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      <span class="brand-mark"><?= icon('leaf') ?></span>
      <span><?= ucfirst($role) ?></span>
      <span class="navbar-meta">&middot; <?= htmlspecialchars($_SESSION['name']) ?></span>
    </div>
    <button class="navbar-toggle" type="button" aria-label="Menu"><?= icon('menu') ?></button>
    <div class="navbar-links">
        <a href="community_dashboard.php"><?= icon('dashboard') ?><?= t("dashboard") ?></a>
        <a href="marketplace.php"><?= icon('cart') ?><?= t("marketplace") ?></a>
        <a href="pending_offers.php"><?= icon('offers') ?><?= t("pending_offers") ?></a>
        <a href="transactions.php" class="active"><?= icon('transactions') ?><?= t("transactions_title") ?></a>
        <form method="POST" style="margin:6px 12px;">
            <select name="lang" class="lang-input" onchange="this.form.submit()">
                <option value="eng" <?= $lang=='eng'?'selected':'' ?>>English</option>
                <option value="hin" <?= $lang=='hin'?'selected':'' ?>>हिंदी</option>
                <option value="telugu" <?= $lang=='telugu'?'selected':'' ?>>తెలుగు</option>
            </select>
        </form>
        <a href="logout.php"><?= icon('logout') ?><?= t("logout") ?></a>
    </div>
  </div>
</nav>

<div class="container fade-in">
    <h2><?= t("transactions_title") ?></h2>

    <div class="card">
        <h3><?= icon('wallet') ?> <?= t("current_balance") ?>: ₹<?= number_format($balance,2) ?></h3>
        <form method="POST" class="inline-form">
            <input type="number" step="0.01" name="amount" placeholder="<?= t("enter_amount") ?>" required>
            <button type="submit" name="add_money" class="btn btn-primary"><?= icon('plus') ?><?= t("add_money") ?></button>
        </form>
    </div>

    <div class="card">
        <h3><?= t("previous_transactions") ?></h3>
        <?php if($transactions->num_rows > 0): ?>
        <div class="table-wrap">
        <table class="styled-table">
            <tr>
                <th><?= t("id") ?></th>
                <th><?= t("buyer") ?></th>
                <th><?= t("seller") ?></th>
                <th><?= t("credits") ?></th>
                <th><?= t("price") ?></th>
                <th><?= t("date") ?></th>
            </tr>
            <?php while($t = $transactions->fetch_assoc()): ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><?= htmlspecialchars($t['buyer_name']) ?></td>
                <td><?= htmlspecialchars($t['seller_name']) ?></td>
                <td><?= $t['credits'] ?></td>
                <td>₹<?= number_format($t['price'],2) ?></td>
                <td><?= $t['created_at'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
        <?php else: ?>
        <div class="empty-state"><?= icon('transactions') ?><p><?= t("no_transactions") ?></p></div>
        <?php endif; ?>
    </div>
</div>

<div class="popup" id="popup">
    <div class="popup-content">
        <p id="popup-msg"></p>
        <button class="btn btn-primary" onclick="closePopup()"><?= t("ok") ?></button>
    </div>
</div>

<script src="assets/nav.js"></script>
<script>
function showPopup(msg){
    document.getElementById("popup-msg").innerText = msg;
    document.getElementById("popup").style.display = "flex";
}
function closePopup(){
    document.getElementById("popup").style.display = "none";
}
<?php if(!empty($msg)): ?>
showPopup("<?= addslashes($msg) ?>");
<?php endif; ?>
</script>

</body>
</html>
