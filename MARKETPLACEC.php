<?php
session_start();

// Prevent caching issues (fix back button redirect)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include DB
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
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$user_id = $_SESSION['user_id'];

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === "post_offer") {
        $type = $_POST['type'];
        $credits = intval($_POST['credits']);
        $price = floatval($_POST['price']);

        $stmt = $conn->prepare("INSERT INTO marketplace (seller_id, type, credits, price, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("isid", $user_id, $type, $credits, $price);
        $stmt->execute();

        $_SESSION['msg'] = t("offer_posted_success");
        header("Location: marketplace.php");
        exit;
    }

    if ($action === "send_offer") {
        $listing_id = intval($_POST['listing_id']);
        $offer_credits = intval($_POST['offer_credits']);
        $offer_price = floatval($_POST['offer_price']);

        $stmt = $conn->prepare("INSERT INTO negotiations (listing_id, buyer_id, offer_credits, offer_price, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiii", $listing_id, $user_id, $offer_credits, $offer_price);
        $stmt->execute();

        $_SESSION['msg'] = t("offer_sent_success");
        header("Location: marketplace.php");
        exit;
    }

    if ($action === "delete_listing") {
        $listing_id = intval($_POST['listing_id']);
        $stmt = $conn->prepare("UPDATE marketplace SET status='deleted' WHERE id=? AND seller_id=?");
        $stmt->bind_param("ii", $listing_id, $user_id);
        $stmt->execute();
        echo json_encode(["success"=>true,"listing_id"=>$listing_id]);
        exit;
    }

    if ($action === "undo_delete") {
        $listing_id = intval($_POST['listing_id']);
        $stmt = $conn->prepare("UPDATE marketplace SET status='active' WHERE id=? AND seller_id=?");
        $stmt->bind_param("ii", $listing_id, $user_id);
        $stmt->execute();
        echo json_encode(["success"=>true,"listing_id"=>$listing_id]);
        exit;
    }
}

// Fetch listings (others' active listings)
$listings = $conn->query("SELECT m.*, u.name FROM marketplace m JOIN users u ON m.seller_id=u.id WHERE m.seller_id!=$user_id AND m.status='active' ORDER BY m.id DESC");

// Fetch my listings
$my_listings = $conn->query("SELECT * FROM marketplace WHERE seller_id=$user_id ORDER BY id DESC");

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= t("marketplace") ?></title>
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

<h2><?= icon('cart') ?> <?= t("marketplace") ?></h2>

<!-- Post new listing -->
<div class="card">
<h3><?= icon('plus') ?> <?= t("postoffer") ?></h3>
<form method="POST" class="form-row">
    <input type="hidden" name="action" value="post_offer">
    <select name="type">
        <option value="sell"><?= t("sell") ?></option>
        <option value="buy"><?= t("buy") ?></option>
    </select>
    <input type="number" name="credits" placeholder="<?= t("credit") ?>" required>
    <input type="number" step="0.01" name="price" placeholder="<?= t("price") ?>" required>
    <button type="submit" class="btn btn-primary"><?= icon('plus') ?><?= t("postoffer") ?></button>
</form>
</div>

<div class="card">
<h3><?= t("listings") ?></h3>
<div class="table-wrap">
<table class="styled-table">
    <tr>
        <th>S.No</th>
        <th><?= t("user") ?></th>
        <th><?= t("type") ?></th>
        <th><?= t("credits") ?></th>
        <th><?= t("price") ?></th>
        <th><?= t("action") ?></th>
    </tr>
    <?php $sn=1; while($row=$listings->fetch_assoc()){ ?>
    <tr>
        <td><?= $sn++; ?></td>
        <td><?= htmlspecialchars($row['name']) ?></td>
        <td><span class="badge badge-neutral"><?= ucfirst(htmlspecialchars($row['type'])) ?></span></td>
        <td><?= $row['credits'] ?></td>
        <td><?= $row['price'] ?></td>
        <td>
            <form method="POST" class="inline-form">
                <input type="hidden" name="action" value="send_offer">
                <input type="hidden" name="listing_id" value="<?= $row['id'] ?>">
                <input type="number" name="offer_credits" placeholder="<?= t("credits") ?>" required>
                <input type="number" step="0.01" name="offer_price" placeholder="<?= t("price") ?>" required>
                <button type="submit" class="btn btn-secondary btn-sm"><?= t("sendoffer") ?></button>
            </form>
        </td>
    </tr>
    <?php } ?>
</table>
</div>
</div>

<div class="card">
<h3><?= t("my_listings") ?></h3>
<div class="table-wrap">
<table class="styled-table">
    <tr>
        <th>S.No</th>
        <th><?= t("type") ?></th>
        <th><?= t("credits") ?></th>
        <th><?= t("price") ?></th>
        <th><?= t("status") ?></th>
        <th><?= t("action") ?></th>
    </tr>
    <?php $sn=1; while($my=$my_listings->fetch_assoc()){ 
        $deleted = $my['status']==='deleted' ? '1' : '0';
    ?>
    <tr id="mylist-<?= $my['id'] ?>" data-deleted="<?= $deleted ?>">
        <td><?= $sn++ ?></td>
        <td><span class="badge badge-neutral"><?= ucfirst(htmlspecialchars($my['type'])) ?></span></td>
        <td><?= $my['credits'] ?></td>
        <td><?= $my['price'] ?></td>
        <td><span class="badge <?= $my['status']==='active' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst($my['status']) ?></span></td>
        <td>
            <?php if($my['status']==='active'){ ?>
            <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $my['id'] ?>"><?= icon('trash') ?> <?= t("delete") ?></button>
            <?php } else { ?>
            <button class="btn btn-secondary btn-sm delete-btn" data-id="<?= $my['id'] ?>"><?= icon('undo') ?> <?= t("undo") ?></button>
            <?php } ?>
        </td>
    </tr>
    <?php } ?>
</table>
</div>
</div>

<a class="back-link" href="CORPORATE_DASHBOARD.php"><?= icon('back') ?><?= t("back") ?></a>
</div>

<div id="popup" class="popup">
    <div class="popup-content">
        <p id="popup-msg"></p>
    </div>
</div>

<script>
const popup = document.getElementById("popup");
const popupMsg = document.getElementById("popup-msg");

function showPopup(msg) {
    popupMsg.innerHTML = msg + '<br><br><button class="btn btn-primary" onclick="closePopup()"><?= t("ok") ?></button>';
    popup.style.display = "flex";
}
function closePopup() { popup.style.display="none"; }

function undoDelete(id,row,btn) {
    fetch("", {
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`action=undo_delete&listing_id=${id}`
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            row.dataset.deleted="0";
            row.querySelector("td:nth-child(5)").innerHTML='<span class="badge badge-success"><?= t("active") ?></span>';
            showPopup("✅ <?= t('listing_restored') ?>");
            btn.remove();
        }
    });
}

function confirmDelete(id,row,btn) {
    popupMsg.innerHTML = `🗑️ <?= t('confirm_delete') ?><br><br>
        <button class="btn btn-danger" id="confirm-delete"><?= t('yes') ?></button>
        <button class="btn btn-secondary" onclick="closePopup()"><?= t('no') ?></button>`;
    popup.style.display="flex";

    document.getElementById("confirm-delete").onclick = () => {
        fetch("", {
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:`action=delete_listing&listing_id=${id}`
        })
        .then(res=>res.json())
        .then(data=>{
            if(data.success){
                row.dataset.deleted="1";
                row.querySelector("td:nth-child(5)").innerHTML='<span class="badge badge-warning"><?= t("deleted") ?></span>';
                btn.innerHTML='<?= icon("undo") ?> <?= t("undo") ?>';
                btn.classList.remove("btn-danger");
                btn.classList.add("btn-secondary");
                closePopup();
                btn.onclick = () => undoDelete(id,row,btn);
            }
        });
    };
}

// Init delete buttons
document.querySelectorAll(".delete-btn").forEach(btn=>{
    const id = btn.dataset.id;
    const row = document.getElementById(`mylist-${id}`);
    const isDeleted = row.dataset.deleted==="1";
    if(isDeleted) btn.onclick = ()=>undoDelete(id,row,btn);
    else btn.onclick = ()=>confirmDelete(id,row,btn);
});

// Show any session messages
<?php if(!empty($msg)){ ?>
showPopup("<?= addslashes($msg) ?>");
<?php } ?>
</script>

</body>
</html>
