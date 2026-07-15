<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_access();
$pdo = db();
ensure_schema($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: quotes.php'); exit; }
csrf_check();
$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM quotes WHERE id=?"); $st->execute([$id]); $q = $st->fetch();
if (!$q) { flash('ההצעה לא נמצאה.'); header('Location: quotes.php'); exit; }

$doc_no = (int)$pdo->query("SELECT COALESCE(MAX(doc_no),0)+1 FROM quotes")->fetchColumn();
$tok    = quote_make_token();

// שכפול כטיוטה חדשה (הצעת מחיר), ללא חתימה/שליחה/צפייה
$pdo->prepare("INSERT INTO quotes
    (doc_no,client_name,phone,heading,quote_date,validity,mode,status,public_token,internal_notes,notify_view,notify_sign)
    VALUES (?,?,?,?,?,?, 'quote','draft', ?, ?, ?, ?)")
    ->execute([
        $doc_no, $q['client_name'], $q['phone'], $q['heading'], date('Y-m-d'),
        (int)$q['validity'], $tok, $q['internal_notes'] ?? null,
        (int)($q['notify_view'] ?? 1), (int)($q['notify_sign'] ?? 1),
    ]);
$newId = (int)$pdo->lastInsertId();

// שכפול הפריטים
$its = $pdo->prepare("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order"); $its->execute([$id]);
$ins = $pdo->prepare("INSERT INTO quote_items (quote_id,name,descr,fmt,price,qty,sort_order) VALUES (?,?,?,?,?,?,?)");
foreach ($its->fetchAll() as $r) {
    $ins->execute([$newId, $r['name'], $r['descr'], $r['fmt'], $r['price'], $r['qty'], $r['sort_order']]);
}

flash('ההצעה שוכפלה (#' . $doc_no . '). ניתן לערוך אותה כעת.');
header('Location: quote_form.php?id=' . $newId); exit;
