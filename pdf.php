<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/lib/quote_pdf.php';
require_access();
$pdo = db();
ensure_schema($pdo);

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM quotes WHERE id=?"); $st->execute([$id]); $q = $st->fetch();
if (!$q) { http_response_code(404); die('המסמך לא נמצא.'); }
$its = $pdo->prepare("SELECT * FROM quote_items WHERE quote_id=? ORDER BY sort_order"); $its->execute([$id]); $items = $its->fetchAll();

$action = $_GET['action'] ?? '';

/* שליחת ה-PDF ללקוח בוואטסאפ */
if ($action === 'wa') {
    if (empty($q['phone'])) { flash('לא הוזן טלפון ללקוח.'); header('Location: quote_form.php?id='.$id); exit; }
    $tmp = sys_get_temp_dir() . '/q' . $id . '_' . time() . '.pdf';
    build_quote_pdf($q, $items, 'F', $tmp);
    $kind = ($q['mode']==='order') ? 'הזמנת עבודה' : 'הצעת מחיר';
    $r = greenapi_send_file($q['phone'], $tmp, basename($tmp), $kind . ' מבידרנט');
    @unlink($tmp);
    flash(!empty($r['ok']) ? 'ה-PDF נשלח בוואטסאפ.' : ('שליחה נכשלה: '.($r['error']??'שגיאה')));
    header('Location: quote_form.php?id='.$id); exit;
}

/* תצוגה / הורדה */
$dest = (($_GET['dl'] ?? '') === '1') ? 'D' : 'I';
build_quote_pdf($q, $items, $dest);
exit;
