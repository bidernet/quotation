<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

$tab = (($_GET['tab'] ?? '') === 'signed') ? 'signed' : 'open';
$where = $tab === 'signed' ? "status='signed'" : "status<>'signed'";
$order = $tab === 'signed' ? "signed_at DESC" : "created_at DESC";
$rows = $pdo->query("SELECT q.*, (SELECT COALESCE(SUM(price*qty),0) FROM quote_items qi WHERE qi.quote_id=q.id) AS sub
                     FROM quotes q WHERE $where ORDER BY $order")->fetchAll();
$open_cnt   = (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status<>'signed'")->fetchColumn();
$signed_cnt = (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='signed'")->fetchColumn();

page_head('הצעות מחיר', 'index');
?>
<div class="pagebar">
  <h1>הצעות מחיר</h1>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="catalog.php">📚 קטלוג שירותים</a>
  <a class="btn" href="quote_form.php">+ הצעה חדשה</a>
</div>

<div class="subtabs">
  <a class="tab <?= $tab==='open'?'on':'' ?>" href="index.php?tab=open">📝 הצעות מחיר (<?= $open_cnt ?>)</a>
  <a class="tab <?= $tab==='signed'?'on':'' ?>" href="index.php?tab=signed">✅ הזמנות חתומות (<?= $signed_cnt ?>)</a>
</div>

<div class="card">
<?php if (!$rows): ?>
  <div class="empty"><p><?= $tab==='signed'?'אין עדיין הזמנות חתומות.':'אין עדיין הצעות מחיר.' ?></p>
  <?php if ($tab!=='signed'): ?><a class="btn" href="quote_form.php">צור הצעה ראשונה</a><?php endif; ?></div>
<?php else: ?>
  <table class="data">
    <thead><tr>
      <th>מסמך</th><th>סטטוס</th><th>תאריך</th>
      <?php if ($tab==='signed'): ?><th>תאריך חתימה</th><?php endif; ?>
      <th>סה״כ כולל מע״מ</th><th class="tl">פעולה</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $q):
      $total = (float)$q['sub'] * (1 + VAT_RATE);
      $badge = $q['status']==='signed' ? '<span class="badge green">חתום</span>'
             : ($q['mode']==='order' ? '<span class="badge amber">הזמנת עבודה</span>' : '<span class="badge gray">הצעה</span>');
    ?>
      <tr>
        <td><a class="link" href="quote_form.php?id=<?= (int)$q['id'] ?>">#<?= (int)$q['doc_no'] ?> · <?= e($q['client_name'] ?: '—') ?></a></td>
        <td><?= $badge ?></td>
        <td class="nowrap"><?= fmt_date($q['quote_date']) ?></td>
        <?php if ($tab==='signed'): ?><td class="nowrap muted"><?= $q['signed_at']?fmt_date($q['signed_at']):'—' ?></td><?php endif; ?>
        <td class="money"><?= money($total) ?></td>
        <td class="tl nowrap">
          <a class="btn btn-ghost btn-sm" href="quote_view.php?id=<?= (int)$q['id'] ?>" target="_blank">צפייה</a>
          <a class="btn btn-ghost btn-sm" href="quote_form.php?id=<?= (int)$q['id'] ?>">עריכה</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php page_foot(); ?>
