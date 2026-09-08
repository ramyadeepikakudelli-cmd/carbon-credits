<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

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

function t($key, $params = []) {
    global $translations;
    $str = $translations[$key] ?? $key;
    if (!empty($params)) $str = vsprintf($str, $params);
    return $str;
}

// Check login & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'community') {
    header("Location: login.php");
    exit;
}

$community_id = $_SESSION['user_id'];

// Handle approval/rejection POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // approve / reject

    // Validate request belongs to this community & pending
    $check = $conn->query("SELECT id FROM users WHERE id=$request_id AND panchayat_id=$community_id AND role='user' AND status='pending'");
    
    if ($check->num_rows > 0) {
        if ($action === 'approve') {
            $conn->query("UPDATE users SET status='approved' WHERE id=$request_id");
            $_SESSION['msg'] = t("user_approved", ["User"]);
        } elseif ($action === 'reject') {
            $conn->query("UPDATE users SET status='rejected' WHERE id=$request_id");
            $_SESSION['msg'] = t("user_rejected", ["User"]);
        }
    }

    header("Location: pending_users.php");
    exit;
}

// Fetch pending user requests
$pending_requests = $conn->query("
    SELECT id, name, email, role 
    FROM users 
    WHERE panchayat_id=$community_id 
      AND role='user' 
      AND status='pending' 
    ORDER BY created_at ASC
");

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= t("pending_user_requests") ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<div class="container fade-in">
    <div class="lang-select">
        <form method="POST">
            <select name="lang" class="lang-input" onchange="this.form.submit()">
                <option value="eng" <?= ($lang=='eng')?'selected':'' ?>>English</option>
                <option value="hin" <?= ($lang=='hin')?'selected':'' ?>>हिंदी</option>
                <option value="telugu" <?= ($lang=='telugu')?'selected':'' ?>>తెలుగు</option>
            </select>
        </form>
    </div>

    <h2><?= icon('users') ?> <?= t("pending_user_requests") ?></h2>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= icon('check') ?><span><?= htmlspecialchars($msg) ?></span></div>
    <?php endif; ?>

    <div class="card">
    <?php if ($pending_requests->num_rows > 0): ?>
    <div class="table-wrap">
    <table class="styled-table">
        <tr>
            <th><?= t("name") ?></th>
            <th><?= t("email") ?></th>
            <th><?= t("role") ?></th>
            <th><?= t("actions") ?></th>
        </tr>
        <?php while ($row = $pending_requests->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><span class="badge badge-neutral"><?= htmlspecialchars($row['role']) ?></span></td>
            <td>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-primary btn-sm"><?= icon('check') ?><?= t("approve") ?></button>
                </form>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="btn btn-danger btn-sm"><?= icon('close') ?><?= t("reject") ?></button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>
    <?php else: ?>
        <div class="empty-state"><?= icon('users') ?><p><?= t("no_pending_requests") ?></p></div>
    <?php endif; ?>
    </div>

    <a class="back-link" href="community_dashboard.php"><?= icon('back') ?><?= t("back") ?></a>
</div>

</body>
</html>
