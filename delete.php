<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();
$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $pdo->prepare("DELETE FROM quote_items WHERE quote_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM quotes WHERE id=?")->execute([$id]);
    flash('המסמך נמחק.');
}
header('Location: index.php');
exit;
