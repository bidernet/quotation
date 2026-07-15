# מערכת הצעות מחיר · bidernet (עצמאית)

מערכת עצמאית ל-quotation.bidernet.co.il — נפרדת מה-CRM, עם מסד נתונים משלה.

## התקנה
1. cPanel → MySQL Databases → צור מסד + משתמש חדשים (למשל biderne1_quotes).
2. העלה את כל הקבצים לתיקיית הסאב-דומיין.
3. העתק config.sample.php ל-config.php ומלא DB + APP_URL.
4. גלוש ל-install.php (יוצר טבלאות) → מחק אותו אחרי.
5. חיבור וואטסאפ: הגדרות → הזן idInstance + apiTokenInstance (GREEN API).

## גישה
- ACCESS_CODE ריק ב-config = בלי מסך כניסה. מלא קוד = כניסה עם קוד.
- sign.php (חתימת לקוח) תמיד ציבורי.
