INIMAI MATRIMONY - Firebase version (shared online database, no Node.js)

A. FIREBASE (one time, ~10 minutes)
 1. console.firebase.google.com > Add project > name: inimai-matrimony
 2. Build > Authentication > Get started > Sign-in method > Email/Password > Enable
 3. Authentication > Users > Add user: your ADMIN email + a strong password
 4. Build > Firestore Database > Create database > Production mode
 5. Firestore > Rules tab: delete everything, paste the whole file firestore.rules,
    change 'admin@example.com' to your admin email (lowercase), click Publish
 6. Project settings (gear) > Your apps > Web (</>) > Register app > copy firebaseConfig
 7. Open firebase-config.js: paste the config and set ADMIN_EMAIL

B. EMAIL when someone shows interest (EmailJS, free 200 emails/month)
 1. emailjs.com > sign up > Email Services > Add service (Gmail) > copy Service ID
 2. Email Templates > Create template:
      To Email:  {{to_email}}
      Subject:   {{subject}}
      Content:   {{message}}
    Save, copy Template ID
 3. Account > General > copy Public Key
 4. Paste the 3 values into firebase-config.js (EMAILJS)

C. VERCEL
 Upload this folder (index.html at the top level). Website: your-site.vercel.app
 Admin portal: your-site.vercel.app/admin

Admin login: only the password (the email is ADMIN_EMAIL from firebase-config.js)
