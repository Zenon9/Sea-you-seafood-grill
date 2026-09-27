STEP 2 - CHECKOUT + DATABASE + RECEIPT
- Checkout validates table availability inside a database transaction.
- Product prices and stock are re-read from MySQL and locked during checkout.
- Cash is validated server-side.
- Order, order items, payment, stock deduction, table occupancy, and activity log commit together.
- Order remains Pending so the Kitchen can receive it.
- Successful checkout redirects to receipt.php.
- Receipt has Print and New Order actions.
Test: Dine-in cash, Takeout cash, insufficient cash, occupied table, low/out-of-stock.
