SEA YOU SEAFOOD & GRILL POS v2

INSTALL
1. Put the seafood_pos folder in F:\xampp\htdocs\ (rename it to sea_you_pos if desired).
2. IMPORTANT: if you rename the folder, update redirect paths if needed. Easiest: keep folder name seafood_pos.
3. Start Apache + MySQL.
4. Import database.sql in phpMyAdmin. It creates database seafood_pos.
5. Open http://localhost/seafood_pos/

ACCOUNTS (password for all: password)
admin / cashier / kitchen

WORKING NOW
- Role login
- Tablet-style cashier POS
- Real Sea You menu categories and menu prices transcribed from supplied photos
- Search/category filtering, cart and quantity changes
- Dine-in/takeout and table selection/status
- Cash, GCash and Card payment recording
- Stock deduction
- Send unpaid orders to kitchen
- Kitchen queue/status updates
- Admin live sales/order/low-stock summary

PAYMENT NOTE
The GCash/Card option records a cashless transaction in the POS; it is not a live bank/InstaPay gateway. A real merchant QR/API requires the restaurant's authorized merchant/payment-provider credentials. Do not hard-code personal banking credentials.

MENU NOTE
Items/prices that were visibly N/A or too unclear in the photos were not guessed. Admin can update products later.
