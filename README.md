# Hotel Management System

A complete hotel platform built with **Laravel 13**: a public booking website, a front-desk and back-office
application, accounting, purchasing, HR and payroll.

* **Guests** search rooms, book, pay online (Stripe, PayPal, SSLCommerz) or at the hotel, get e-mail confirmations
  and PDF invoices, and manage their bookings.
* **Front desk** takes phone and walk-in reservations, checks guests in and out, takes payments and refunds,
  adds charges to the bill and prints invoices.
* **Back office** covers room setup, offers and promo codes, a double-entry ledger with financial statements,
  purchasing and stock, employees, attendance, leave, loans and payroll, reports, roles and permissions, backups.

## Requirements

PHP 8.3+ (`pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`, `curl`, `intl`), MySQL 8 / MariaDB 10.6+, Composer.
No Node.js is needed: front-end libraries are bundled in `public/vendor`.

## Install

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env            # set APP_URL, DB_*, MAIL_* and (optionally) ADMIN_EMAIL / ADMIN_PASSWORD
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force     # roles, chart of accounts, payment methods, starter pages, first administrator
php artisan storage:link
```

Point the web server's document root at `public/`. Sign in at `/admin/login` with `ADMIN_EMAIL` /
`ADMIN_PASSWORD` (default `admin@example.com` / `ChangeMe!2026`) and **change the password straight away**.
Then fill in *Hotel settings*, add room types, rooms and rates, and switch on the payment methods you accept.

Scheduled jobs (nightly database backup) need one cron entry:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Upgrading a database created by the old CodeIgniter system

The schema is compatible. Passwords stored as MD5 keep working once and are re-hashed on each person's next login.

```bash
php artisan hotel:adopt-existing-database   # records the baseline migrations as already applied
php artisan migrate --force                 # adds the new tables and columns
php artisan db:seed --force                 # roles, chart of accounts, pages (existing rows are never overwritten)
```

Existing staff flagged as administrators become *Super Admin*. Old accounting, purchasing and HR tables are left
untouched but are no longer used: the new modules keep their data in new tables (`ledger_*`, `journal_*`,
`inventory_*`, `purchase*`, `hr_*`). Back up first, and do not run both systems against the same database.

## Try it with sample data

On a fresh, empty installation (after `php artisan migrate --seed`) run:

```
php artisan hotel:demo-data
```

It fills every module through the normal services — four room types with room numbers, guests, twelve reservations
(checked out, in house, upcoming, cancelled), purchases with returns and wastage, ten employees with a paid payroll run,
rosters, housekeeping and laundry, transport and hall bookings — so the ledger, stock and reports show real figures.
It refuses to run when reservations already exist (use `--force` to add anyway). Never run it on your live data.

## Advance bookings

*Front desk → Advance bookings* lists upcoming reservations with the advance received. Under the list, set a
**required advance percentage**: new reservations then stay *pending* until that share is paid (the booking is
confirmed automatically when the payment arrives), and **release after N days** cancels pending bookings that
received nothing (`hotel:release-unpaid-bookings`, run hourly by the scheduler). Both default to 0 (off).

## Money flow in one paragraph

A guest payment is recorded as a deposit (cash / bank / gateway account debited, *Guest deposits* credited). At
check-out the stay is recognised as revenue: deposits and any unpaid balance are cleared and *Room revenue*, *Taxes
payable*, *Service charge income* and *Extra services revenue* are credited. Purchases debit *Inventory* and credit
*Accounts payable*; payroll debits *Salaries & wages* and credits *Salaries payable*, *Payroll tax payable* and
*Staff loans*. Everything is a balanced journal entry; the trial balance and balance sheet always agree.

## Modules (admin sidebar)

Groups collapse; the open/closed state is remembered per browser.

* **Customer** – guest records with ID (front/back) and photo, VIP flag; companions registered on each booking.
* **Purchase manage / Units & products** – purchases, supplier returns (with return-invoice PDF), stock levels,
  destroyed (written-off) list, units, categories, suppliers.
* **Human resources** – full employee profile (photo, NID, documents, education, experience, emergency contact),
  attendance, leave, loans, payroll. **Duty roster** – shifts, roster assign/list, attendance dashboard.
* **House keeping** – cleaning tasks with checklists, printable room QR codes (guests can request cleaning),
  laundry orders/prices/payments, reports.
* **Transport** – flight details, vehicles, vehicle bookings (double-booking is blocked).
* **Hall room** – halls, types, facilities, seat plans, bookings with clash check, payments posted to the ledger,
  status board, report.
* **WhatsApp** – click-to-chat links on reservations; optional sending through the WhatsApp Cloud API
  (phone-number ID and access token under *WhatsApp → WhatsApp setting*; the token is stored encrypted).

## Roles

*Super Admin* (everything), *Manager*, *Front Desk*, *Accountant*, *HR Manager*, *Store Keeper*, *Housekeeping* are created by the
seeder. Edit them or create your own under *Administration → Roles & permissions*.

## Online payments

Configure Stripe, PayPal or SSLCommerz under *Administration → Payment gateways* (credentials are stored encrypted).
A payment is only recorded after the provider's API confirms it; browsers returning from the provider are never trusted.
Stripe webhook: `POST /payments/stripe/notify` (event `checkout.session.completed`); SSLCommerz IPN:
`POST /payments/sslcommerz/notify`.

## Development

```bash
php artisan serve
php artisan test            # needs a MySQL database named hotel_test (see phpunit.xml)
vendor/bin/pint             # code style
```

## Security notes

Login and contact forms are rate-limited, uploads are validated, CSV exports neutralise spreadsheet formulas,
staff access is permission-checked on every route, and payment callbacks are verified server-side. Keep `.env`
and the `storage/app/backups` folder private.
