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

function t($key, $params = []) {
    global $translations;
    $str = $translations[$key] ?? $key;
    if (!empty($params)) $str = vsprintf($str, $params);
    return $str;
}

// Fetch active communities
$communities = $conn->query("SELECT id, name FROM users WHERE role='community' AND status='approved'");

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['role'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Validate role
    if (!in_array($role, ['user', 'community', 'corporate'])) {
        $message = t("invalid_role");
    } else {
        $panchayat_id = ($role === 'user') ? intval($_POST['panchayat_id'] ?? 0) : 0;

        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, panchayat_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("ssssi", $name, $email, $password, $role, $panchayat_id);

        if ($stmt->execute()) {
            $message = t("registration_sent");
        } else {
            $message = t("error_occurred") . ": " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= t("registration_request") ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/theme.css">
<script>
function toggleCommunitySelection() {
    var role = document.getElementById('role').value;
    document.getElementById('communitySelection').style.display = (role === 'user') ? 'block' : 'none';
}
</script>
</head>
<body>

<div class="auth-shell">
  <div class="auth-card fade-in" style="max-width:520px;">

    <div class="lang-select">
        <form method="POST">
            <select name="lang" class="lang-input" onchange="this.form.submit()">
                <option value="eng" <?= ($lang=='eng')?'selected':'' ?>>English</option>
                <option value="hin" <?= ($lang=='hin')?'selected':'' ?>>हिंदी</option>
                <option value="telugu" <?= ($lang=='telugu')?'selected':'' ?>>తెలుగు</option>
            </select>
        </form>
    </div>

    <div class="auth-logo">
        <span class="brand-mark"><?= icon('leaf') ?></span>
        <span>Carbon Credit Platform</span>
    </div>

    <h2><?= t("registration_request") ?></h2>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= icon('check') ?><span><?= htmlspecialchars($message) ?></span></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label><?= t("name") ?></label>
        <input type="text" name="name" required>

        <label><?= t("email") ?></label>
        <input type="email" name="email" required>

        <label><?= t("password") ?></label>
        <input type="password" name="password" required>

        <label><?= t("role") ?></label>
        <select name="role" id="role" onchange="toggleCommunitySelection()" required>
            <option value=""><?= t("select") ?></option>
            <option value="user"><?= t("user") ?></option>
            <option value="community"><?= t("community") ?></option>
            <option value="corporate"><?= t("corporate") ?></option>
        </select>

        <div id="communitySelection" style="display:none;">
            <label><?= t("select_community") ?></label>
            <select name="panchayat_id">
                <option value=""><?= t("select_community") ?></option>
                <?php while($row = $communities->fetch_assoc()): ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block mt-4"><?= icon('user') ?><?= t("send_request") ?></button>
    </form>

    <p class="auth-foot"><a href="login.php"><?= icon('back') ?> <?= t("login") ?></a></p>
  </div>
</div>

</body>
</html>
