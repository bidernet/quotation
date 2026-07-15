<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

/* ---- נתונים ללוח בקרה ---- */
$all = $pdo->query("SELECT q.*, (SELECT COALESCE(SUM(price*qty),0) FROM quote_items qi WHERE qi.quote_id=q.id) AS sub
                    FROM quotes q ORDER BY q.created_at DESC")->fetchAll();

$cnt = ['total'=>0,'open'=>0,'sent'=>0,'viewed'=>0,'signed'=>0,'expired'=>0];
$val_open = 0.0; $val_signed = 0.0;
$recent = []; $need_attention = [];
foreach ($all as $q) {
    $cnt['total']++;
    $info = quote_status_info($q);
    $cnt[$info['key']] = ($cnt[$info['key']] ?? 0) + 1;
    $total = (float)$q['sub'] * (1 + VAT_RATE);
    if ($info['key']==='signed') $val_signed += $total;
    else $val_open += $total;
    // דורש טיפול: נצפה אך לא נחתם, או פג תוקף לאחרונה
    if (in_array($info['key'], ['viewed'])) $need_attention[] = [$q,$info,$total];
}
$recent = array_slice($all, 0, 6);

$conv = $cnt['total'] ? round($cnt['signed'] / $cnt['total'] * 100) : 0;

page_head('ראשי', 'dashboard');
?>
<div class="pagebar"><h1>לוח בקרה</h1><div class="spacer"></div>
  <a class="btn" href="quote_form.php">+ הצעה חדשה</a>
</div>

<div class="kpi-grid">
  <div class="kpi hero">
    <div class="label">שווי הזמנות חתומות</div>
    <div class="value"><?= money($val_signed) ?></div>
    <div class="sub"><?= (int)$cnt['signed'] ?> הזמנות · אחוז סגירה <?= $conv ?>%</div>
  </div>
  <div class="kpi">
    <div class="label">הצעות פתוחות</div>
    <div class="value"><?= (int)($cnt['total']-$cnt['signed']) ?></div>
    <div class="sub">שווי פוטנציאלי <?= money($val_open) ?></div>
  </div>
  <div class="kpi">
    <div class="label">ממתינות לתגובה</div>
    <div class="value"><?= (int)($cnt['sent']+$cnt['viewed']) ?></div>
    <div class="sub"><?= (int)$cnt['viewed'] ?> נצפו · <?= (int)$cnt['sent'] ?> נשלחו</div>
  </div>
  <div class="kpi">
    <div class="label">פג תוקף</div>
    <div class="value <?= $cnt['expired']>0?'danger':'' ?>"><?= (int)$cnt['expired'] ?></div>
    <div class="sub">דורשות חידוש או סגירה</div>
  </div>
</div>

<?php if ($need_attention): ?>
<div class="card">
  <h2>🔔 דורשות טיפול — נצפו ולא נחתמו</h2>
  <table class="data">
    <thead><tr><th>מסמך</th><th>נצפה</th><th>סה״כ</th><th class="tl">פעולה</th></tr></thead>
    <tbody>
    <?php foreach ($need_attention as [$q,$info,$total]): ?>
      <tr>
        <td><a class="link" href="quote_form.php?id=<?= (int)$q['id'] ?>">#<?= (int)$q['doc_no'] ?> · <?= e($q['client_name']?:'—') ?></a></td>
        <td class="nowrap muted"><?= $q['viewed_at']?fmt_date($q['viewed_at']):'—' ?></td>
        <td class="money"><?= money($total) ?></td>
        <td class="tl nowrap">
          <?php if (greenapi_enabled() && $q['phone']): ?>
          <form method="post" action="send.php" style="display:inline" onsubmit="return confirm('לשלוח תזכורת ללקוח?')"><?= csrf_field() ?><input type="hidden" name="quote_id" value="<?= (int)$q['id'] ?>"><button class="btn btn-green btn-sm" type="submit">💬 תזכורת</button></form>
          <?php endif; ?>
          <a class="btn btn-ghost btn-sm" href="quote_view.php?id=<?= (int)$q['id'] ?>" target="_blank">צפייה</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<div class="card" style="padding:0">
  <div style="display:flex;align-items:center;padding:16px 20px;border-bottom:1px solid var(--line-2)">
    <h2 style="margin:0">מסמכים אחרונים</h2><div class="spacer"></div>
    <a class="btn btn-ghost btn-sm" href="quotes.php">לכל ההצעות ←</a>
  </div>
  <?php if (!$recent): ?>
    <div class="empty"><p>אין עדיין מסמכים.</p><a class="btn" href="quote_form.php">צור הצעה ראשונה</a></div>
  <?php else: ?>
    <table class="data">
      <thead><tr><th>מסמך</th><th>סטטוס</th><th>תאריך</th><th>סה״כ</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $q): $total=(float)$q['sub']*(1+VAT_RATE); ?>
        <tr>
          <td><a class="link" href="quote_form.php?id=<?= (int)$q['id'] ?>">#<?= (int)$q['doc_no'] ?> · <?= e($q['client_name']?:'—') ?></a></td>
          <td><?= quote_status_badge($q) ?></td>
          <td class="nowrap muted"><?= fmt_date($q['quote_date']) ?></td>
          <td class="money"><?= money($total) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php page_foot(); ?>
