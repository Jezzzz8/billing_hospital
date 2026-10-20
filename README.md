# Billing Hospital

## Local setup

1. Create the `billing_hospital` MySQL database and import the schema-only `billing_hospital.sql`. It does not include patient, billing, or account seed records; provision required master data and an initial administrator securely for your environment.
2. Configure the database connection in `config/connection.php` for your local MySQL account.
3. Install PHP dependencies with `composer install`.
4. Install JavaScript dependencies with `npm install`. The post-install script generates the local browser vendor copies.
5. Build the Tailwind stylesheet with `npm run tailwind:build`.
6. Apply the SQL files in `database/migrations/` once, in filename order.
7. Start Apache and MySQL in XAMPP, then run `start-websocket.bat` in a separate console.

The application expects to be served from `/billing_hospital` by default; update `BASE_URL` in `config/config.php` if your local URL differs.

## Live updates

The realtime notification and update service runs separately from Apache. Start it with `start-websocket.bat` and leave its console window running while using the application. WebSocket clients connect on port `8080`; the same process binds the patient-portal in-memory sign-in rate limiter to loopback port `8081`. Allow port `8080` through the local firewall when accessing the application from another device; do not expose port `8081`.

For HTTPS deployments, configure the web server to proxy secure WebSocket (`wss`) connections to this service.

## Patient portal

Patients can sign in at `/billing_hospital/patient-portal.php` with their first name, last name, date of birth, and the on-page math CAPTCHA. Sign-in is rejected if more than one active patient matches the same name and date of birth. The portal does not store a password or date of birth in the session. Sign-ins are limited to ten attempts per IP address in a 15-minute window by the in-memory limiter, so the local WebSocket/limiter process must be running. Patient sessions expire after five minutes of inactivity. From the portal, patients can view, print, or download their own cashier-format statements of account.

Medicine and other stock-tracked Pharmacy items remain charged after they are dispensed and received. Nursing records each administered quantity in the Medicine Intake Monitor; the system reduces the remaining quantity and derives the intake status automatically. A separate intake log records doses, starting balances, and legacy quantity reconciliations. Items received before dose logging was enabled must be reconciled to the actual quantity remaining before discharge. When the attending doctor marks a patient ready for discharge, they choose whether each remaining stock-tracked item goes home with the patient or is returned to Pharmacy. Take-home items keep their charges. Pharmacy must confirm the quantity physically received before a negative `MEDICINE_RETURN` charge credits the bill at the original unit price; only the quantity Pharmacy marks safe to restock is added back to inventory. The original fulfillment and charge remain in the audit trail.

Apply `database/migrations/20261019_charge_item_stockable.sql`, `database/migrations/20261019_charge_item_stock_quantity.sql`, `database/migrations/20261019_service_fulfillment_intake_tracking.sql`, and `database/migrations/20261020_service_fulfillment_intake_log.sql` once before using stock and dose intake tracking. Stock is a simple on-hand count on each stockable charge item (no lot, expiry, or movement history). The Pharmacy Stock page is restricted to Pharmacy department accounts and supports signed stock adjustments; an administrator enables tracking for each medicine or other Pharmacy-managed item, such as dextrose, in Charge Items. Pharmacy fulfillment and POS sales decrement stock transactionally. Returns credit the amount physically accepted at the original price, subtract it from the nurse-confirmed remaining quantity, and restock only the Pharmacy-approved safe quantity. Stock-tracked items cannot be fulfilled by other departments or added through manual billing charges.
