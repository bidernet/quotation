<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

$notice='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrf_check();
    $act=$_POST['action']??'';
    if ($act==='save') {
        setting_set($pdo,'greenapi_id',trim($_POST['greenapi_id']??''));
        setting_set($pdo,'greenapi_token',trim($_POST['greenapi_token']??''));
        flash('ההגדרות נשמרו.'); header('Location: settings.php'); exit;
    }
    if ($act==='numbering') {
        $qs=(int)($_POST['quote_start']??1001); if($qs<=0)$qs=1001;
        $os=(int)($_POST['order_start']??5001); if($os<=0)$os=5001;
        setting_set($pdo,'quote_start',(string)$qs);
        setting_set($pdo,'order_start',(string)$os);
        flash('הגדרות המספור נשמרו. (חל על מסמכים חדשים)');
        header('Location: settings.php'); exit;
    }
    if ($act==='notify') {
        setting_set($pdo,'owner_phone',trim($_POST['owner_phone']??''));
        flash('הגדרות ההתראות נשמרו.');
        header('Location: settings.php'); exit;
    }
    if ($act==='wa_template') {
        setting_set($pdo,'wa_template',trim($_POST['wa_template']??''));
        flash('תבנית הוואטסאפ נשמרה.');
        header('Location: settings.php'); exit;
    }
    if ($act==='test') {
        $phone=trim($_POST['test_phone']??'');
        if($phone===''){ $notice='הזן מספר לבדיקה.'; }
        else { $r=greenapi_send_text($phone,'בדיקת חיבור bidernet ✅'); $notice=!empty($r['ok'])?'נשלחה הודעת בדיקה בהצלחה!':('הבדיקה נכשלה: '.($r['error']??'שגיאה')); }
    }
}
$cfg=greenapi_cfg(); $connected=greenapi_enabled();
$quote_start=(int)setting_get($pdo,'quote_start','1001');
$order_start=(int)setting_get($pdo,'order_start','5001');
$owner_phone=setting_get($pdo,'owner_phone','');
$wa_template=setting_get($pdo,'wa_template',''); if(trim($wa_template)==='') $wa_template=wa_template_default();
page_head('הגדרות','settings');
?>
<div class="pagebar"><h1>הגדרות</h1></div>
<?php if ($notice): ?><div class="alert <?= strpos($notice,'נכשל')!==false?'err':'ok' ?>"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
  <h2>תבנית הודעת וואטסאפ</h2>
  <div class="muted" style="font-size:13px;margin-bottom:12px">הטקסט שיישלח ללקוח עם ההצעה. השתמש במשתנים <code style="background:#eef;padding:1px 6px;border-radius:4px;direction:ltr;display:inline-block">{שם}</code> ו-<code style="background:#eef;padding:1px 6px;border-radius:4px;direction:ltr;display:inline-block">{קישור}</code> — הם יוחלפו אוטומטית בשם הלקוח ובקישור לחתימה.</div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="wa_template">
    <div class="field"><textarea name="wa_template" style="min-height:150px;line-height:1.7"><?= e($wa_template) ?></textarea></div>
    <div class="actions"><button class="btn" type="submit">שמור תבנית</button></div>
  </form>
</div>

<div class="card">
  <h2>מספור מסמכים</h2>
  <div class="muted" style="font-size:13px;margin-bottom:12px">הצעות מחיר והזמנות עבודה ממוספרות בסדרות נפרדות. כאן מגדירים מאיזה מספר להתחיל (חל על מסמכים חדשים בלבד).</div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="numbering">
    <div class="grid2">
      <div class="field"><label>הצעות מחיר — מספר התחלה</label><input type="number" name="quote_start" value="<?= $quote_start ?>"></div>
      <div class="field"><label>הזמנות עבודה — מספר התחלה</label><input type="number" name="order_start" value="<?= $order_start ?>"></div>
    </div>
    <div class="actions"><button class="btn" type="submit">שמור מספור</button></div>
  </form>
</div>

<div class="card">
  <h2>התראות</h2>
  <div class="muted" style="font-size:13px;margin-bottom:12px">קבל הודעת וואטסאפ כשלקוח צופה או חותם על מסמך. (דורש חיבור GREEN API פעיל)</div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="notify">
    <div class="grid2"><div class="field"><label>הטלפון שלך לקבלת התראות</label><input type="text" name="owner_phone" value="<?= e($owner_phone) ?>" placeholder="0541234567"></div></div>
    <div class="actions"><button class="btn" type="submit">שמור</button></div>
  </form>
</div>

<div class="card">
  <h2>חיבור וואטסאפ · GREEN API</h2>
  <div style="margin-bottom:12px"><?= $connected?'<span class="badge green">מחובר</span> <span class="muted">המערכת יכולה לשלוח בוואטסאפ.</span>':'<span class="badge gray">לא מחובר</span> <span class="muted">הזן פרטים מ-green-api.com.</span>' ?></div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <div class="grid2">
      <div class="field"><label>idInstance</label><input type="text" name="greenapi_id" value="<?= e($cfg['id']) ?>" placeholder="1101234567"></div>
      <div class="field"><label>apiTokenInstance</label><input type="text" name="greenapi_token" value="<?= e($cfg['token']) ?>" placeholder="הטוקן הארוך"></div>
    </div>
    <div class="muted" style="margin-top:8px;font-size:13px">משיגים בלוח הבקרה של GREEN API אחרי סריקת QR. ודא שהסטטוס "authorized".</div>
    <div class="actions"><button class="btn" type="submit">שמור וחבר</button></div>
  </form>
  <?php if ($connected): ?>
  <hr>
  <form method="post" class="rowflex" style="align-items:flex-end">
    <?= csrf_field() ?><input type="hidden" name="action" value="test">
    <div class="field" style="margin:0"><label>שלח בדיקה למספר</label><input type="text" name="test_phone" placeholder="0501234567" style="max-width:200px"></div>
    <button class="btn btn-ghost" type="submit">שלח בדיקה</button>
  </form>
  <?php endif; ?>
</div>
<?php page_foot(); ?>
