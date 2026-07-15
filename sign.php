<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
$pdo = db();
ensure_schema($pdo);

$token = preg_replace('/[^a-f0-9]/', '', $_GET['t'] ?? '');
if ($token === '') { http_response_code(404); die('קישור לא תקין.'); }
$st = $pdo->prepare("SELECT * FROM quotes WHERE public_token=?"); $st->execute([$token]); $q=$st->fetch();
if (!$q) { http_response_code(404); die('המסמך לא נמצא.'); }
$its=$pdo->prepare("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order"); $its->execute([$q['id']]); $items=$its->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST' && $q['mode']==='order' && $q['status']!=='signed') {
    $sig=$_POST['signature']??''; $name=trim($_POST['signer_name']??'');
    if (strpos($sig,'data:image/')===0 && strlen($sig)<2000000) {
        $pdo->prepare("UPDATE quotes SET status='signed', signature_data=?, signer_name=?, signed_at=NOW() WHERE id=?")->execute([$sig,$name,$q['id']]);
        $st->execute([$token]); $q=$st->fetch();
    }
}
$isOrder=($q['mode']==='order'); $signed=($q['status']==='signed');
?><!DOCTYPE html><html lang="he" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($isOrder?'הזמנת עבודה':'הצעת מחיר') ?> · bidernet</title>
<link rel="stylesheet" href="assets/style.css"></head><body class="docbg">
<?php include __DIR__ . '/lib/quote_document.php'; ?>

<?php if ($isOrder): ?>
<div class="card signcard">
  <h2>חתימת הלקוח</h2>
  <?php if ($signed): ?>
    <div class="alert ok">✓ ההזמנה נחתמה ואושרה<?= $q['signed_at']?' · '.date('d/m/Y H:i',strtotime($q['signed_at'])):'' ?></div>
    <?php if ($q['signature_data']): ?><img src="<?= e($q['signature_data']) ?>" class="sigimg"><?php endif; ?>
    <?php if ($q['signer_name']): ?><div class="muted">חתם/ה: <?= e($q['signer_name']) ?></div><?php endif; ?>
  <?php else: ?>
    <form method="post" onsubmit="return doSign()">
      <div class="field"><label>שם החותם/ת</label><input type="text" name="signer_name" placeholder="שם מלא"></div>
      <div class="muted" style="margin:8px 0">חתום כאן באצבע:</div>
      <canvas id="pad" class="sigpad"></canvas>
      <input type="hidden" name="signature" id="signature">
      <div class="actions center"><button type="button" class="btn btn-ghost" onclick="clearPad()">נקה</button><button type="submit" class="btn">✍️ אני מאשר/ת וחותם/ת</button></div>
    </form>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card signcard"><div class="muted center">הצעת מחיר לצפייה. לאחר אישורכם תהפוך להזמנת עבודה לחתימה.</div></div>
<?php endif; ?>
<div class="foot">bidernet · הצעות מחיר</div>
<script>
let pad,ctx,drawing=false,dirty=false;
function initPad(){pad=document.getElementById('pad');if(!pad)return;const r=pad.getBoundingClientRect();pad.width=r.width;pad.height=r.height;ctx=pad.getContext('2d');ctx.lineWidth=2.5;ctx.lineCap='round';ctx.strokeStyle='#14180b';
 const pos=e=>{const b=pad.getBoundingClientRect();const t=e.touches?e.touches[0]:e;return{x:t.clientX-b.left,y:t.clientY-b.top};};
 const st=e=>{drawing=true;dirty=true;const p=pos(e);ctx.beginPath();ctx.moveTo(p.x,p.y);e.preventDefault();};
 const mv=e=>{if(!drawing)return;const p=pos(e);ctx.lineTo(p.x,p.y);ctx.stroke();e.preventDefault();};
 const en=()=>{drawing=false;};
 pad.addEventListener('mousedown',st);pad.addEventListener('mousemove',mv);window.addEventListener('mouseup',en);
 pad.addEventListener('touchstart',st,{passive:false});pad.addEventListener('touchmove',mv,{passive:false});pad.addEventListener('touchend',en);}
function clearPad(){if(ctx){ctx.clearRect(0,0,pad.width,pad.height);dirty=false;}}
function doSign(){if(!dirty){alert('נא לחתום קודם ✍️');return false;}document.getElementById('signature').value=pad.toDataURL('image/png');return true;}
initPad();
</script>
</body></html>
