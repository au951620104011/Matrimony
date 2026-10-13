INIMAI MATRIMONY - PHP + MySQL version (no Firebase, no Node)

A) RUN ON YOUR LAPTOP (XAMPP)
 1. Copy this folder to  C:\xampp\htdocs\inimai
 2. XAMPP Control Panel: Start Apache and MySQL
 3. Open http://localhost/phpmyadmin > New > database name: inimai (utf8mb4_general_ci) > Create
 4. Click the inimai database > Import > choose install.sql > Go
 5. Open config.php and set UPI_ID (your UPI id) and WHATSAPP number
 6. Website:  http://localhost/inimai/
    Admin:    http://localhost/inimai/admin/   (first time it asks you to CREATE your admin username + password)

B) PUT IT LIVE (Hostinger)
 1. hPanel > Databases > create a MySQL database + user. phpMyAdmin > Import install.sql
 2. File Manager > public_html : upload and extract this folder's files
 3. Edit config.php: DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_URL (https://yourdomain.com), UPI_ID, MAIL_FROM
 4. Turn on the free SSL, open https://yourdomain.com/admin/ and create the admin login
 5. Replace http://localhost/inimai in sitemap.xml and robots.txt with your domain

HOW IT WORKS
 - Visitors see photo + name of 5 profiles only.
 - Register: details > plan > scan UPI QR (amount filled in) > 12 digit UTR (+ screenshot).
 - Admin > Payments: check UTR and screenshot > Approve or Reject (with reason). Approve = plan starts, profile goes live.
 - Silver Rs1 = 3 months, 25 full profiles | Gold Rs2000 = 6 months, 50 | Platinum Rs3000 = 12 months, 100.
 - "Unlock" on a profile uses one view (same profile again is free). After the date ends, details lock until renewal.
 - Phone, about and star are sent by the server only after unlock or an accepted interest.
 - Admin: Dashboard, Payments, Members (reset password), Profiles (edit/approve/delete), Add profile, Activity, Interests.
 - Emails use PHP mail() (works on Hostinger, not on XAMPP). SMS: fill SMS_AUTHKEY + SMS_TEMPLATE_ID in config.php (MSG91, DLT template with one variable VAR1).
