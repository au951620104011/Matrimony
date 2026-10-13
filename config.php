<?php
// ===== CHANGE THESE =====
const DB_HOST='localhost', DB_NAME='install', DB_USER='root', DB_PASS='';   // XAMPP default. Hostinger: use the values from hPanel
const SITE_URL='http://localhost/inimai';        // your website address, no / at the end
const UPI_ID='mahalakshmi132004@okhdfcbank';                    // your UPI id, e.g. 9876543210@ybl
const UPI_NAME='Inimai Matrimony';
const WHATSAPP='919999999999';                    // country code + number
const MAIL_FROM='no-reply@yourdomain.com';
const SMS_AUTHKEY='', SMS_TEMPLATE_ID='';         // optional: MSG91 (needs a DLT approved template with one variable VAR1)
// ===== plans =====
const PLANS=['Silver'=>['amount'=>1,'months'=>3,'quota'=>25],'Gold'=>['amount'=>2000,'months'=>6,'quota'=>50],'Platinum'=>['amount'=>3000,'months'=>12,'quota'=>100]];
const CASTES=['Nadar','Pillai','Mudaliar','Gounder','Naidu','Yadavar','Thevar','Chettiar','Vanniyar','Christian','Muslim','Other'];
const EDU=['Below 10th','10th / 12th','Diploma','Bachelor degree','Master degree','Doctorate','Other'];
const INC=['Below ₹2 lakh','₹2 – 5 lakh','₹5 – 10 lakh','₹10 lakh and above'];
const RELIGIONS=['Hindu','Christian','Muslim'];
