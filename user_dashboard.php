<?php
session_start();
include 'db.php';
include 'assets/icons.php';

// Allowed languages
$allowed_langs = ['eng', 'hin', 'telugu'];

// Handle language selection
if (isset($_POST['lang']) && in_array($_POST['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_POST['lang'];
}

// Load language
$lang = $_SESSION['lang'] ?? 'eng';
$translations = include "lang/$lang.php";

function t($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

// Check login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info and credits
$user_res = $conn->query("
    SELECT u.name, u.email, u.credits, c.name AS community_name
    FROM users u
    LEFT JOIN users c ON u.panchayat_id = c.id
    WHERE u.id = $user_id
");
$user = $user_res->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= t("user_dashboard") ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      <span class="brand-mark"><?= icon('leaf') ?></span>
      <span><?= t("user_dashboard") ?></span>
    </div>
    <div class="flex items-center gap-3">
      <form method="POST">
          <select name="lang" class="lang-input" onchange="this.form.submit()">
              <option value="eng" <?= ($lang=='eng')?'selected':'' ?>>English</option>
              <option value="hin" <?= ($lang=='hin')?'selected':'' ?>>हिंदी</option>
              <option value="telugu" <?= ($lang=='telugu')?'selected':'' ?>>తెలుగు</option>
          </select>
      </form>
      <a href="logout.php" class="btn btn-secondary btn-sm"><?= icon('logout') ?><?= t("logout") ?></a>
    </div>
  </div>
</nav>

<div class="container fade-in">
    <h2><?= icon('user') ?> <?= t("welcome") ?>, <?= htmlspecialchars($user['name']) ?></h2>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon"><?= icon('building') ?></div>
            <div class="stat-label"><?= t("your_community") ?></div>
            <div class="stat-value" style="font-size:1.2rem;"><?= htmlspecialchars($user['community_name'] ?? t("not_assigned")) ?></div>
        </div>

        <div class="stat-card accent">
            <div class="stat-icon"><?= icon('coins') ?></div>
            <div class="stat-label"><?= t("total_credits") ?></div>
            <div class="stat-value"><?= htmlspecialchars($user['credits'] ?? 0) ?> <span style="font-size:1rem;"><?= t("credits") ?></span></div>
        </div>
    </div>
</div>

</body>
</html>
