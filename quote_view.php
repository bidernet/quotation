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
$notes=trim((string)($q['internal_notes']??''));
?><!DOCTYPE html><html lang="he" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($q['mode']==='order'?'הזמנת עבודה':'הצעת מחיר') ?> · <?= e($q['client_name']) ?></title>
<link rel="stylesheet" href="assets/style.css"></head><body class="docbg">
<div class="qbar">
  <a class="btn btn-ghost btn-sm" href="quotes.php">→ חזרה</a>
  <a class="btn btn-sm" href="pdf.php?id=<?= $id ?>" target="_blank">📄 PDF</a>
  <a class="btn btn-ghost btn-sm" href="pdf.php?id=<?= $id ?>&dl=1">⬇ הורדה</a>
  <?php if (greenapi_enabled() && $q['phone']): ?><a class="btn btn-green btn-sm" href="pdf.php?id=<?= $id ?>&action=wa" onclick="return confirm('לשלוח PDF בוואטסאפ ללקוח?')">💬 שלח PDF</a><?php endif; ?>
  <?php if ($signLink): ?><a class="btn btn-ghost btn-sm" href="<?= e($signLink) ?>" target="_blank">🔗 קישור חתימה</a><?php endif; ?>
</div>
<?php if ($notes!==''): ?>
<div class="card signcard" style="border-inline-start:4px solid var(--amber)"><strong>📌 הערה פנימית (לא נראית ללקוח):</strong><br><?= nl2br(e($notes)) ?></div>
<?php endif; ?>
<?php include __DIR__ . '/lib/quote_document.php'; ?>
<div class="foot">bidernet · הצעות מחיר</div>
</body></html>
