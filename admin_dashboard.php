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

// --- Check Admin Access ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// --- Handle Approve/Reject Actions ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'], $_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if (in_array($action, ['approve', 'reject'])) {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE users SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $user_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['msg'] = t("user_status_updated") . " $status!";
        header("Location: admin_dashboard.php");
        exit;
    }
}

// --- Fetch Pending Requests ---
$pending_requests = $conn->query("SELECT * FROM users WHERE status='pending' ORDER BY created_at DESC");
$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= t("admin_dashboard") ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<nav class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">
      <span class="brand-mark"><?= icon('leaf') ?></span>
      <span><?= t("admin_dashboard") ?></span>
    </div>
    <div class="flex items-center gap-3">
      <form method="POST">
          <select name="lang" class="lang-input" onchange="this.form.submit()">
              <option value="eng" <?= $lang=='eng'?'selected':'' ?>>English</option>
              <option value="hin" <?= $lang=='hin'?'selected':'' ?>>हिंदी</option>
              <option value="telugu" <?= $lang=='telugu'?'selected':'' ?>>తెలుగు</option>
          </select>
      </form>
      <a href="logout.php" class="btn btn-secondary btn-sm"><?= icon('logout') ?><?= t("logout") ?></a>
    </div>
  </div>
</nav>

<div class="container fade-in">
    <h2><?= icon('users') ?> <?= t("pending_user_requests") ?></h2>

    <?php if($msg): ?>
        <div class="alert alert-success"><?= icon('check') ?><span><?= htmlspecialchars($msg) ?></span></div>
    <?php endif; ?>

    <div class="card">
    <?php if ($pending_requests->num_rows > 0): ?>
        <div class="table-wrap">
        <table class="styled-table">
            <thead>
                <tr>
                    <th><?= t("name") ?></th>
                    <th><?= t("email") ?></th>
                    <th><?= t("role") ?></th>
                    <th><?= t("actions") ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $pending_requests->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><span class="badge badge-neutral"><?= htmlspecialchars($row['role']) ?></span></td>
                    <td>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button class="btn btn-primary btn-sm"><?= icon('check') ?><?= t("approve") ?></button>
                        </form>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button class="btn btn-danger btn-sm"><?= icon('close') ?><?= t("reject") ?></button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><?= icon('users') ?><p><?= t("no_pending_requests") ?></p></div>
    <?php endif; ?>
    </div>
</div>

</body>
</html>
