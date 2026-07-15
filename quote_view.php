<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);
$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT * FROM quotes WHERE id=?"); $st->execute([$id]); $q=$st->fetch();
if(!$q){ http_response_code(404); die('לא נמצא.'); }
$its=$pdo->prepare("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order"); $its->execute([$id]); $items=$its->fetchAll();
$signLink=$q['public_token']?rtrim(APP_URL,'/').'/sign.php?t='.$q['public_token']:'';
?><!DOCTYPE html><html lang="he" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($q['mode']==='order'?'הזמנת עבודה':'הצעת מחיר') ?> · <?= e($q['client_name']) ?></title>
<link rel="stylesheet" href="assets/style.css"></head><body class="docbg">
<div class="qbar">
  <a class="btn btn-ghost btn-sm" href="index.php">→ חזרה</a>
  <button class="btn btn-sm" onclick="window.print()">🖨 הדפס / שמור PDF</button>
  <?php if ($signLink): ?><a class="btn btn-green btn-sm" href="<?= e($signLink) ?>" target="_blank">🔗 קישור חתימה</a><?php endif; ?>
</div>
<?php include __DIR__ . '/lib/quote_document.php'; ?>
</body></html>
