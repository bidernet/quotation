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
        setting_set($pdo,'owner_phone',trim($_POST['owner_phone']??''));
        flash('ההגדרות נשמרו.'); header('Location: settings.php'); exit;
    }
    if ($act==='test') {
        $phone=trim($_POST['test_phone']??'');
        if($phone===''){ $notice='הזן מספר לבדיקה.'; }
        else { $r=greenapi_send_text($phone,'בדיקת חיבור bidernet ✅'); $notice=!empty($r['ok'])?'נשלחה הודעת בדיקה בהצלחה!':('הבדיקה נכשלה: '.($r['error']??'שגיאה')); }
    }
}
$cfg=greenapi_cfg(); $connected=greenapi_enabled();
$owner=setting_get($pdo,'owner_phone','');
page_head('הגדרות','settings');
?>
<div class="pagebar"><h1>הגדרות</h1></div>
<?php if ($notice): ?><div class="alert <?= strpos($notice,'נכשל')!==false?'err':'ok' ?>"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
  <h2>חיבור וואטסאפ · GREEN API</h2>
  <div style="margin-bottom:14px"><?= $connected?'<span class="badge green">מחובר</span> <span class="muted">המערכת יכולה לשלוח בוואטסאפ.</span>':'<span class="badge gray">לא מחובר</span> <span class="muted">הזן פרטים מ-green-api.com.</span>' ?></div>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <div class="grid2">
      <div class="field"><label>idInstance</label><input type="text" name="greenapi_id" value="<?= e($cfg['id']) ?>" placeholder="1101234567"></div>
      <div class="field"><label>apiTokenInstance</label><input type="text" name="greenapi_token" value="<?= e($cfg['token']) ?>" placeholder="הטוקן הארוך"></div>
    </div>
    <hr>
    <div class="field"><label>📱 מספר בעל העסק לקבלת התראות</label>
      <input type="text" name="owner_phone" value="<?= e($owner) ?>" placeholder="0501234567" style="max-width:280px">
      <div class="hint">לכאן יישלחו התראות וואטסאפ כשהלקוח צופה בהצעה או חותם עליה.</div>
    </div>
    <div class="actions"><button class="btn" type="submit">שמור וחבר</button></div>
  </form>
  <?php if ($connected): ?>
  <hr>
  <form method="post" class="rowflex" style="align-items:flex-end;justify-content:flex-start;gap:12px">
    <?= csrf_field() ?><input type="hidden" name="action" value="test">
    <div class="field" style="margin:0"><label>שלח בדיקה למספר</label><input type="text" name="test_phone" placeholder="0501234567" style="max-width:200px"></div>
    <button class="btn btn-ghost" type="submit">שלח בדיקה</button>
  </form>
  <?php endif; ?>
</div>
<?php page_foot(); ?>
