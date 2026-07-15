<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

$tab = (($_GET['tab'] ?? '') === 'signed') ? 'signed' : 'open';
$search = trim($_GET['q'] ?? '');
$active = ($tab === 'signed' && $search === '') ? 'signed' : 'index';

$params = [];
if ($search !== '') {
    // חיפוש גלובלי לפי מספר / שם / טלפון (מתעלם מהלשונית)
    $cond = "(client_name LIKE ? OR phone LIKE ? OR doc_no LIKE ?)";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like];
    $order = "created_at DESC";
    $rows = $pdo->prepare("SELECT q.*, (SELECT COALESCE(SUM(price*qty),0) FROM quote_items qi WHERE qi.quote_id=q.id) AS sub
                           FROM quotes q WHERE $cond ORDER BY $order");
    $rows->execute($params);
    $rows = $rows->fetchAll();
} else {
    $where = $tab === 'signed' ? "status='signed'" : "status<>'signed'";
    $order = $tab === 'signed' ? "signed_at DESC" : "created_at DESC";
    $rows = $pdo->query("SELECT q.*, (SELECT COALESCE(SUM(price*qty),0) FROM quote_items qi WHERE qi.quote_id=q.id) AS sub
                         FROM quotes q WHERE $where ORDER BY $order")->fetchAll();
}
$open_cnt   = (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status<>'signed'")->fetchColumn();
$signed_cnt = (int)$pdo->query("SELECT COUNT(*) FROM quotes WHERE status='signed'")->fetchColumn();

page_head($tab==='signed' && $search===''?'הזמנות חתומות':'הצעות מחיר', $active);
?>
<div class="pagebar">
  <h1><?= $tab==='signed' && $search===''?'הזמנות חתומות':'הצעות מחיר' ?></h1>
  <div class="spacer"></div>
  <form method="get" class="searchbox">
    <span class="s-ico"><?= icon('search', 18) ?></span>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="חיפוש לפי מספר / שם / טלפון">
    <?php if ($search!==''): ?><a class="btn btn-ghost btn-sm" href="index.php">✕</a><?php endif; ?>
  </form>
  <a class="btn" href="quote_form.php"><?= icon('plus') ?> הצעה חדשה</a>
</div>

<?php if ($search!==''): ?>
<div class="subtabs"><span class="muted" style="padding:9px 4px">תוצאות חיפוש עבור "<?= e($search) ?>" (<?= count($rows) ?>)</span></div>
<?php endif; ?>

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
      $badge = quote_status_badge($q);
    ?>
      <tr>
        <td><a class="link" href="quote_form.php?id=<?= (int)$q['id'] ?>"><?= e(doc_label($q)) ?> · <?= e($q['client_name'] ?: '—') ?></a></td>
        <td><?= $badge ?></td>
        <td class="nowrap" data-label="תאריך"><?= fmt_date($q['quote_date']) ?></td>
        <?php if ($tab==='signed'): ?><td class="nowrap muted" data-label="נחתם"><?= $q['signed_at']?fmt_date($q['signed_at']):'—' ?></td><?php endif; ?>
        <td class="money" data-label="סה״כ כולל מע״מ"><?= money($total) ?></td>
        <td class="tl nowrap">
          <a class="btn btn-ghost btn-sm" href="quote_view.php?id=<?= (int)$q['id'] ?>" target="_blank" title="צפייה"><?= icon('eye') ?></a>
          <?php if (greenapi_enabled() && $q['phone']): ?>
          <form method="post" action="send.php" style="display:inline" onsubmit="return confirm('לשלוח בוואטסאפ אל <?= e($q['phone']) ?>?')"><?= csrf_field() ?><input type="hidden" name="quote_id" value="<?= (int)$q['id'] ?>"><button class="btn btn-green btn-sm" type="submit" title="שלח בוואטסאפ"><?= icon('send') ?></button></form>
          <?php endif; ?>
          <a class="btn btn-ghost btn-sm" href="quote_form.php?id=<?= (int)$q['id'] ?>">ערוך</a>
          <form method="post" action="duplicate.php" style="display:inline" onsubmit="return confirm('לשכפל את המסמך?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$q['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit" title="שכפל"><?= icon('copy') ?></button></form>
          <form method="post" action="delete.php" style="display:inline" onsubmit="return confirm('למחוק לצמיתות את <?= e(doc_label($q)) ?>?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$q['id'] ?>"><button class="btn btn-red btn-sm" type="submit" title="מחק"><?= icon('trash') ?></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>
<?php page_foot(); ?>
