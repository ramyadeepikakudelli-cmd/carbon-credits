<?php
session_start();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include DB
include 'db.php';
include 'assets/icons.php';

// Handle language selection
$allowed_langs = ['eng', 'hin', 'telugu'];
if (isset($_POST['lang']) && in_array($_POST['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_POST['lang'];
}

// Default language = English
$lang = $_SESSION['lang'] ?? 'eng';
$translations = include "lang/$lang.php";
function t($key) { global $translations; return $translations[$key] ?? $key; }

$error = "";

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {

            // Check approval status
            if ($user['role'] === 'user' && $user['status'] !== 'approved') {
                $error = t("pending_community_approval");
            } elseif (in_array($user['role'], ['community','corporate']) && $user['status'] !== 'approved') {
                $error = t("pending_admin_approval");
            } else {
                // Login successful
                session_regenerate_id(true); // Security: regenerate session ID
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'admin': header("Location: admin_dashboard.php"); break;
                    case 'community': header("Location: community_dashboard.php"); break;
                    case 'corporate': header("Location: CORPORATE_DASHBOARD.php"); break;
                    case 'user': header("Location: user_dashboard.php"); break;
                    default: $error = t("unknown_role");
                }
                exit;
            }
        } else {
            $error = t("incorrect_password");
        }
    } else {
        $error = t("email_not_found");
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t("login") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<div class="auth-shell">
  <div class="auth-card fade-in">

    <div class="lang-select">
        <form method="POST">
            <select name="lang" class="lang-input" onchange="this.form.submit()">
                <option value="eng" <?= ($lang==='eng')?'selected':'' ?>>English</option>
                <option value="hin" <?= ($lang==='hin')?'selected':'' ?>>हिंदी</option>
                <option value="telugu" <?= ($lang==='telugu')?'selected':'' ?>>తెలుగు</option>
            </select>
        </form>
    </div>

    <div class="auth-logo">
        <span class="brand-mark"><?= icon('leaf') ?></span>
        <span>Carbon Credit Platform</span>
    </div>

    <h2><?= t("login") ?></h2>

    <?php if($error): ?>
        <div class="alert alert-danger"><?= icon('alert') ?><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label><?= t("email") ?></label>
        <input type="email" name="email" required placeholder="<?= t("email") ?>">

        <label><?= t("password") ?></label>
        <input type="password" name="password" required placeholder="<?= t("password") ?>">

        <button type="submit" class="btn btn-primary btn-block mt-4"><?= icon('lock') ?><?= t("login") ?></button>
    </form>

    <p class="auth-foot"><?= t("dont_have_account") ?> <a href="signup.php"><?= t("register_here") ?></a></p>
  </div>
</div>

</body>
</html>
