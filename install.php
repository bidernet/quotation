<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';

$msg = ''; $err = '';
try {
    $pdo = db();
    ensure_schema($pdo);
    $msg = 'הטבלאות נוצרו בהצלחה! המערכת מוכנה.';
} catch (Exception $e) {
    $err = 'שגיאה: ' . $e->getMessage();
}
?><!DOCTYPE html><html lang="he" dir="rtl"><head><meta charset="utf-8">
<title>התקנה</title><link rel="stylesheet" href="assets/style.css"></head>
<body class="login-bg"><div class="login-card">
<h1>התקנת מערכת הצעות מחיר</h1>
<?php if ($err): ?><div class="alert err"><?= e($err) ?></div>
<p class="muted">ודא שפרטי מסד הנתונים ב-<code>config.php</code> נכונים ושהמסד קיים.</p>
<?php else: ?><div class="alert ok"><?= e($msg) ?></div>
<a class="btn" href="index.php">כניסה למערכת →</a>
<p class="muted" style="margin-top:12px">מומלץ למחוק את <code>install.php</code> מהשרת לאחר ההתקנה.</p>
<?php endif; ?>
</div></body></html>
