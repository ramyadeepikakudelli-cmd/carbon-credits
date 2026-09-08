<?php
session_start();

// Prevent caching/back-button redirect issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'db.php';
include 'assets/icons.php';

// Handle language selection
if (isset($_POST['lang'])) {
    $lang = $_POST['lang'];
    $allowed = ['eng', 'hin', 'telugu'];
    if (in_array($lang, $allowed)) $_SESSION['lang'] = $lang;
}

// Load translations
$lang = $_SESSION['lang'] ?? "eng";
$translations = include "lang/$lang.php";
function t($key) { global $translations; return $translations[$key] ?? $key; }

// Check login
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = $_SESSION['user_id'];

// Handle accept/reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['negotiation_id'], $_POST['response'])) {
    $negotiation_id = intval($_POST['negotiation_id']);
    $response = $_POST['response']; // accepted / rejected

    // Update negotiation status
    $stmt = $conn->prepare("UPDATE negotiations SET status=? WHERE id=?");
    $stmt->bind_param("si", $response, $negotiation_id);
    $stmt->execute();

    // If accepted, mark listing as completed and assign buyer
    if ($response === "accepted") {
        $stmt2 = $conn->prepare("
            UPDATE marketplace 
            SET status='completed', buyer_id=(SELECT buyer_id FROM negotiations WHERE id=?)
            WHERE id=(SELECT listing_id FROM negotiations WHERE id=?)
        ");
        $stmt2->bind_param("ii", $negotiation_id, $negotiation_id);
        $stmt2->execute();
    }

    $_SESSION['msg'] = t("offer_response_msg") . " " . t($response) . "!";
    header("Location: PENDING_OFFERC.php");
    exit;
}

// Fetch pending offers for user's listings
$offers = $conn->query("
    SELECT n.*, u.name AS buyer_name, m.type, m.credits AS listing_credits, m.price AS listing_price
    FROM negotiations n
    JOIN users u ON n.buyer_id = u.id
    JOIN marketplace m ON n.listing_id = m.id
    WHERE m.seller_id = $user_id AND n.status = 'pending'
    ORDER BY n.created_at DESC
");

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t("pending_offers") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/theme.css">
</head>
<body>

<div class="container fade-in">

<!-- Language selector -->
<div class="lang-select">
<form method="POST">
    <select name="lang" class="lang-input" onchange="this.form.submit()">
        <option value="eng" <?= ($lang=='eng')?'selected':'' ?>>English</option>
        <option value="hin" <?= ($lang=='hin')?'selected':'' ?>>हिंदी</option>
        <option value="telugu" <?= ($lang=='telugu')?'selected':'' ?>>తెలుగు</option>
    </select>
</form>
</div>

<h2><?= icon('offers') ?> <?= t("pending_offers") ?></h2>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= icon('check') ?><span><?= htmlspecialchars($msg) ?></span></div>
<?php endif; ?>

<div class="card">
<?php if ($offers->num_rows > 0): ?>
    <div class="table-wrap">
    <table class="styled-table">
        <tr>
            <th><?= t("buyer") ?></th>
            <th><?= t("listing_type") ?></th>
            <th><?= t("listing_credits") ?></th>
            <th><?= t("listing_price") ?></th>
            <th><?= t("offer_credits") ?></th>
            <th><?= t("offer_price") ?></th>
            <th><?= t("action") ?></th>
        </tr>
        <?php while ($offer = $offers->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($offer['buyer_name']); ?></td>
            <td><span class="badge badge-neutral"><?= ucfirst(htmlspecialchars($offer['type'])); ?></span></td>
            <td><?= $offer['listing_credits']; ?></td>
            <td><?= $offer['listing_price']; ?></td>
            <td><?= $offer['offer_credits']; ?></td>
            <td><?= $offer['offer_price']; ?></td>
            <td>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="negotiation_id" value="<?= $offer['id'] ?>">
                    <button class="btn btn-primary btn-sm" name="response" value="accepted"><?= icon('check') ?><?= t("accept") ?></button>
                    <button class="btn btn-danger btn-sm" name="response" value="rejected"><?= icon('close') ?><?= t("reject") ?></button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>
<?php else: ?>
    <div class="empty-state"><?= icon('offers') ?><p><?= t("no_pending_offers") ?></p></div>
<?php endif; ?>
</div>

<a class="back-link" href="CORPORATE_DASHBOARD.php"><?= icon('back') ?><?= t("back") ?></a>

</div>

</body>
</html>
