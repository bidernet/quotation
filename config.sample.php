<?php
/**
 * הגדרות מערכת הצעות מחיר (עצמאית) — quotation.bidernet.co.il
 * העתק קובץ זה ל-config.php ומלא את הפרטים.
 */

// ===== מסד נתונים (צור חדש ב-cPanel → MySQL Databases) =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'biderne1_quotes');      // שם מסד הנתונים החדש
define('DB_USER', 'biderne1_quotes');      // המשתמש שיצרת
define('DB_PASS', '');                      // הסיסמה של המשתמש
define('DB_CHARSET', 'utf8mb4');

// ===== כתובת המערכת =====
define('APP_URL', 'https://quotation.bidernet.co.il');
define('APP_NAME', 'bidernet · הצעות מחיר');

// ===== קוד גישה (אופציונלי) =====
// אם תשאיר ריק — אין מסך כניסה (כל מי שיודע את הכתובת נכנס).
// אם תמלא קוד — יידרש להזין אותו פעם אחת כדי להיכנס לניהול.
define('ACCESS_CODE', '');

// ===== מע"מ =====
define('VAT_RATE', 0.17);

// ===== WhatsApp דרך GREEN API =====
define('GREENAPI_API_URL',   'https://api.green-api.com');
define('GREENAPI_MEDIA_URL', 'https://media.green-api.com');
define('GREENAPI_ID',    '');   // idInstance
define('GREENAPI_TOKEN', '');   // apiTokenInstance
