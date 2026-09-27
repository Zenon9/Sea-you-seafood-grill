STEP 4 - TABLE CHECKER
- Added tables.php for cashier/admin.
- Available and occupied tables are shown visually.
- Occupied tables show active order number, Kitchen status, total, and minutes open.
- A table cannot be cleared while Pending, Preparing, or Ready.
- After Kitchen marks Served, cashier can Clear Table.
- Clear Table atomically changes the order from Served to Completed and the table to available.
- Added a Table Checker link to the Cashier POS header.
No database.sql changes are required for this step.
