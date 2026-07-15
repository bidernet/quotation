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
$st = $pdo->prepare("SELECT * FROM quotes WHERE id=?"); $st->execute([$id]); $q = $st->fetch();
if (!$q) { flash('המסמך לא נמצא.'); header('Location: index.php'); exit; }

$doc_no = next_doc_no($pdo, $q['mode']);
$token  = quote_make_token();
$pdo->prepare("INSERT INTO quotes (doc_no,client_name,phone,heading,quote_date,validity,mode,status,notes,public_token)
               VALUES (?,?,?,?,?,?,?, 'draft', ?, ?)")
    ->execute([$doc_no, $q['client_name'], $q['phone'], $q['heading'], date('Y-m-d'),
               $q['validity'], $q['mode'], $q['notes'], $token]);
$newId = (int)$pdo->lastInsertId();

$items = $pdo->prepare("SELECT name,descr,fmt,price,qty,sort_order FROM quote_items WHERE quote_id=? ORDER BY sort_order");
$items->execute([$id]);
$ins = $pdo->prepare("INSERT INTO quote_items (quote_id,name,descr,fmt,price,qty,sort_order) VALUES (?,?,?,?,?,?,?)");
foreach ($items->fetchAll() as $it) {
    $ins->execute([$newId, $it['name'], $it['descr'], $it['fmt'], $it['price'], $it['qty'], $it['sort_order']]);
}
flash('המסמך שוכפל. אפשר לערוך את העותק.');
header('Location: quote_form.php?id=' . $newId);
exit;
