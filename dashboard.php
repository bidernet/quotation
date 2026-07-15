<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

$all = $pdo->query("SELECT q.*, (SELECT COALESCE(SUM(price*qty),0) FROM quote_items qi WHERE qi.quote_id=q.id) AS sub FROM quotes q")->fetchAll();

$open_cnt = 0; $pending_value = 0; $signed_month = 0; $sent_total = 0; $signed_total_all = 0;
$month = date('Y-m');
foreach ($all as $q) {
    $total = (float)$q['sub'] * (1 + VAT_RATE);
    $eff = quote_effective_status($q);
    if ($q['status'] === 'signed') {
        $signed_total_all++;
        if (substr((string)$q['signed_at'], 0, 7) === $month) $signed_month++;
    } else {
        if ($eff !== 'expired' && $eff !== 'rejected') { $open_cnt++; $pending_value += $total; }
    }
    if (in_array($q['status'], ['sent','viewed','signed'], true)) $sent_total++;
}
$conversion = $sent_total > 0 ? round($signed_total_all / $sent_total * 100) : 0;

// activity feed
$recent = $pdo->query("SELECT * FROM quotes ORDER BY GREATEST(COALESCE(signed_at,'1970-01-01'), COALESCE(viewed_at,'1970-01-01'), created_at) DESC LIMIT 8")->fetchAll();

$active = 'dashboard';
page_head('דשבורד', 'dashboard');
?>
<div class="pagebar"><h1>דשבורד</h1><div class="spacer"></div><a class="btn" href="quote_form.php"><?= icon('plus') ?> הצעה חדשה</a></div>

<div class="kpis">
  <div class="kpi"><div class="lbl">הצעות פתוחות</div><div class="val"><?= $open_cnt ?></div><div class="sub">ממתינות לתגובה</div></div>
  <div class="kpi"><div class="lbl">נחתמו החודש</div><div class="val green"><?= $signed_month ?></div><div class="sub"><?= e(date('m/Y')) ?></div></div>
  <div class="kpi"><div class="lbl">שווי ממתין לחתימה</div><div class="val lime"><?= money($pending_value) ?></div><div class="sub">כולל מע״מ</div></div>
  <div class="kpi"><div class="lbl">אחוז המרה</div><div class="val"><?= $conversion ?>%</div><div class="sub">חתומות מתוך שנשלחו</div></div>
</div>

<div class="card">
  <h2>פעילות אחרונה</h2>
  <?php if (!$recent): ?>
    <div class="muted">אין עדיין פעילות. <a href="quote_form.php">צור הצעה ראשונה</a>.</div>
  <?php else: foreach ($recent as $q):
    if ($q['status'] === 'signed' && $q['signed_at']) { $ic=icon('check'); $txt='<b>'.e($q['client_name']).'</b> חתם על '.e(doc_label($q)); $when=$q['signed_at']; }
    elseif ($q['status'] === 'viewed' && $q['viewed_at']) { $ic=icon('eye'); $txt='<b>'.e($q['client_name']).'</b> צפה ב'.e(doc_label($q)); $when=$q['viewed_at']; }
    elseif (quote_is_expired($q)) { $ic=icon('clock'); $txt=e(doc_label($q)).' · <b>'.e($q['client_name']).'</b> פג תוקף'; $when=quote_expiry_date($q); }
    elseif ($q['status'] === 'sent') { $ic=icon('send'); $txt='נשלחה '.e(doc_label($q)).' ל<b>'.e($q['client_name']).'</b>'; $when=$q['created_at']; }
    else { $ic=icon('doc'); $txt='נוצרה '.e(doc_label($q)).' · <b>'.e($q['client_name']).'</b>'; $when=$q['created_at']; }
  ?>
    <div class="actrow"><span><?= $ic ?> <?= $txt ?></span><span class="muted"><?= fmt_date($when) ?></span></div>
  <?php endforeach; endif; ?>
</div>
<?php page_foot(); ?>
