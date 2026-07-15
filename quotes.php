<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

$tab   = (($_GET['tab'] ?? '') === 'signed') ? 'signed' : 'open';
$where = $tab === 'signed' ? "status='signed'" : "status<>'signed'";
$order = $tab === 'signed' ? "signed_at DESC" : "created_at DESC";
$rows  = $pdo->query("SELECT q.*, (SELECT COALESCE(SUM(price*qty),0) FROM quote_items qi WHERE qi.quote_id=q.id) AS sub
                      FROM quotes q WHERE $where ORDER BY $order")->fetchAll();

page_head($tab==='signed' ? 'הזמנות חתומות' : 'הצעות מחיר', $tab==='signed' ? 'signed' : 'quotes');
?>
<div class="pagebar">
  <h1><?= $tab==='signed' ? 'הזמנות חתומות' : 'הצעות מחיר' ?></h1>
  <div class="spacer"></div>
  <a class="btn" href="quote_form.php">+ הצעה חדשה</a>
</div>

<div class="subtabs">
  <a class="tab <?= $tab==='open'?'on':'' ?>" href="quotes.php?tab=open">📝 הצעות מחיר</a>
  <a class="tab <?= $tab==='signed'?'on':'' ?>" href="quotes.php?tab=signed">✅ הזמנות חתומות</a>
</div>

<div class="card" style="padding:0">
<?php if (!$rows): ?>
  <div class="empty"><p><?= $tab==='signed'?'אין עדיין הזמנות חתומות.':'אין עדיין הצעות מחיר.' ?></p>
  <?php if ($tab!=='signed'): ?><a class="btn" href="quote_form.php">צור הצעה ראשונה</a><?php endif; ?></div>
<?php else: ?>
  <table class="data">
    <thead><tr>
      <th>מסמך</th><th>סטטוס</th><th>תאריך</th>
      <?php if ($tab==='signed'): ?><th>נחתם</th><?php else: ?><th>תוקף</th><?php endif; ?>
      <th>סה״כ כולל מע״מ</th><th class="tl">פעולות</th>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $q):
      $total  = (float)$q['sub'] * (1 + VAT_RATE);
      $expired = quote_is_expired($q);
      $days    = quote_days_left($q);
    ?>
      <tr class="<?= $expired?'row-expired':'' ?>">
        <td>
          <a class="link" href="quote_form.php?id=<?= (int)$q['id'] ?>">#<?= (int)$q['doc_no'] ?> · <?= e($q['client_name'] ?: '—') ?></a>
          <?php if ($q['mode']==='order' && $q['status']!=='signed'): ?><span class="badge amber" style="margin-inline-start:6px">הזמנת עבודה</span><?php endif; ?>
          <?php if (trim((string)($q['internal_notes']??''))!==''): ?><span title="יש הערה פנימית" style="margin-inline-start:4px">📌</span><?php endif; ?>
        </td>
        <td><?= quote_status_badge($q) ?></td>
        <td class="nowrap"><?= fmt_date($q['quote_date']) ?></td>
        <?php if ($tab==='signed'): ?>
          <td class="nowrap muted"><?= $q['signed_at']?fmt_date($q['signed_at']):'—' ?></td>
        <?php else: ?>
          <td class="nowrap muted">
            <?php if ($q['status']==='signed'): ?>—
            <?php elseif ($expired): ?><span style="color:var(--red)">פג</span>
            <?php elseif ($days!==null): ?><?= $days ?> ימים<?php else: ?>—<?php endif; ?>
          </td>
        <?php endif; ?>
        <td class="money"><?= money($total) ?></td>
        <td class="tl nowrap">
          <a class="btn btn-ghost btn-sm" href="quote_view.php?id=<?= (int)$q['id'] ?>" target="_blank">צפייה</a>
          <a class="btn btn-ghost btn-sm" href="quote_form.php?id=<?= (int)$q['id'] ?>">עריכה</a>
          <a class="btn btn-ghost btn-sm" href="pdf.php?id=<?= (int)$q['id'] ?>" target="_blank">PDF</a>
          <form method="post" action="quote_dup.php" style="display:inline">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
            <button class="btn btn-ghost btn-sm" type="submit" title="שכפל">⧉ שכפול</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php page_foot(); ?>
