# CodeIgniter → Laravel migration

This folder is a Laravel 13 application that is being built **next to** the CodeIgniter 3 site in the repository root
(strangler-fig approach). Both apps use the same MySQL/MariaDB database, so modules can move over one at a time and
the old site keeps working until everything has been ported. Nothing outside `laravel/` was changed.

## Phase 1 (done)

| Area | Status |
| --- | --- |
| Schema | 116 legacy tables reverse-engineered into Laravel migrations (`database/migrations`). A fresh `php artisan migrate` on an empty database builds the whole schema. |
| Models | One Eloquent model per legacy table in `app/Models/Legacy` (`$timestamps = false`, legacy primary keys). `User`, `Customerinfo`, `BookedInfo` are hand-written. |
| Authentication | Two guards: `customer` (guests, `customerinfo`) and `admin` (staff, `user`). Passwords stay compatible with the old site (see below). Login is rate-limited instead of using the old image captcha. |
| Guest site | Home, room list with availability search, room details with a server-side price quote, register / sign in, booking, checkout (offline payment methods), "my bookings". |
| Back office | Staff sign-in, dashboard, reservation list (search + status filter), reservation detail with status lifecycle (pending → confirmed → checked in → checked out, cancel from pending/confirmed), room list. |
| Tests | 23 feature tests (`php artisan test`) covering the booking flow, double-booking, pricing, ownership checks, legacy password login and the admin lifecycle. |

### Differences from the old behaviour (deliberate)

* **Prices are calculated on the server.** The old `bookedroom` action trusted `amount`, `roomrate` and `discount` posted by the browser.
* **No double booking.** Room numbers are allocated inside a transaction after locking the bookings table; the old code only checked and then inserted.
* **Capacity and date checks** (no past dates, check-out after check-in, party fits the rooms) are enforced.
* Guests can only open their own bookings.
* Staff accounts need `usertype = 1` and `status = 1` to sign in.

### Passwords

The old site stores unsalted MD5 hashes (`user.password` is `varchar(32)`, `customerinfo.pass`). `App\Auth\LegacyUserProvider`
accepts those, and also bcrypt hashes, but **never rewrites a stored hash**, because the CodeIgniter site still reads the same rows.
Once the old site is switched off: widen `user.password` to 255 characters, then re-enable rehash-on-login
(remove `rehashPasswordIfRequired()` from the provider). Every account should be asked to reset its password at that point.

## Running it

```bash
cd laravel
composer install
cp .env.example .env           # then set DB_* to the database the CodeIgniter site uses
php artisan key:generate
php artisan serve              # http://127.0.0.1:8000  (staff: /admin/login)
```

* `public/assets` and `public/website_assets` are symlinks to the legacy asset folders (room images, logos).
* **Existing database:** do *not* run `php artisan migrate` on it – the tables already exist. Only the framework tables are new,
  and none are required (`SESSION_DRIVER`, `CACHE_STORE` and `QUEUE_CONNECTION` default to `file`/`sync`).
* **Empty database:** `php artisan migrate` creates the legacy schema. Reference data (settings, menus, roles, payment methods)
  must still be imported from a legacy dump.
* **Tests** use their own database: create `hotel_test`, grant your user access, adjust `phpunit.xml` if the credentials differ.

## Not ported yet

* Online payments: PayPal (+IPN), SSLCommerz, Stripe. The checkout lists them as "coming soon".
* Booking confirmation e-mail, SMS, PDF invoices (Dompdf), QR codes, promo codes, extra bed/person charges, wake-up calls.
* Guest profile editing, password reset, contact form, newsletter, gallery/about/terms pages and the CMS-driven home page (sliders, banners, menus).
* Staff roles and fine-grained permissions (`sec_*` tables). Today every active staff account can use the whole back office.
* Admin CRUD: rooms, floors, facilities, bed types, offers, taxes, currencies, customers, check-in/out with payments and bills.
* Accounts (chart of accounts, vouchers, ledgers, balance sheet), purchases/inventory, HRM/payroll, reports, language manager, backups, add-on modules.
* The legacy bdtask licence module (`system/core/compat/lic.php`) is intentionally **not** ported.

Suggested order: payments + e-mail → admin room/price CRUD → check-in/out and billing → accounts → purchase → HRM → reports.
