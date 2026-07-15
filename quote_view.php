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
<link rel="stylesheet" href="assets/style.css"><link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32.png"><link rel="shortcut icon" href="assets/favicon.ico"><link rel="apple-touch-icon" href="assets/apple-touch-icon.png"><meta name="theme-color" content="#c6f02e"></head><body class="docbg">
<div class="qbar">
  <a class="btn btn-ghost btn-sm" href="index.php"><?= icon('back') ?> חזרה</a>
  <button class="btn btn-sm" onclick="window.print()"><?= icon('print') ?> הדפס / שמור PDF</button>
  <?php if (greenapi_enabled() && $q['phone']): ?>
  <form method="post" action="send.php" style="display:inline" onsubmit="return confirm('לשלוח בוואטסאפ אל <?= e($q['phone']) ?>?')"><?= csrf_field() ?><input type="hidden" name="quote_id" value="<?= (int)$q['id'] ?>"><button class="btn btn-green btn-sm" type="submit"><?= icon('send') ?> שלח בוואטסאפ</button></form>
  <?php endif; ?>
  <?php if ($signLink): ?><button type="button" class="btn btn-ghost btn-sm" onclick="copyLink()"><?= icon('link') ?> העתק קישור</button><input type="hidden" id="signLinkVal" value="<?= e($signLink) ?>"><?php endif; ?>
</div>
<script>function copyLink(){var el=document.getElementById('signLinkVal');if(!el)return;var v=el.value;if(navigator.clipboard){navigator.clipboard.writeText(v);}else{var t=document.createElement('textarea');t.value=v;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);}alert('הקישור הועתק:\n'+v);}</script>
</div>
<?php include __DIR__ . '/lib/quote_document.php'; ?>
</body></html>
