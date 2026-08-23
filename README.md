<div align="center">

# Hotel Management System

### Complete hotel operations, reservations, and guest management platform

<p>
  <img src="https://img.shields.io/badge/PHP%20%7C%20CodeIgniter-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP and CodeIgniter">
  <img src="https://img.shields.io/badge/Database-MySQL%20%7C%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL or MariaDB">
  <img src="https://img.shields.io/badge/Dependency-Composer-885630?style=for-the-badge&logo=composer&logoColor=white" alt="Composer">
</p>

<p>
  Public booking website&nbsp;&bull;&nbsp;Operations dashboard&nbsp;&bull;&nbsp;Role-based access
</p>

</div>

## About This Project

Hotel Management System is a complete web-based solution for hotels and
hospitality businesses. It combines a customer-facing booking website with an
administration dashboard for reservations, rooms, payments, customers,
accounts, inventory, HR, reports, and daily hotel operations.

The project is designed to help hotel teams reduce manual work, centralize
operational data, and provide guests with a straightforward online booking
experience. Its modular CodeIgniter structure makes it possible to configure
and extend individual business areas as the property grows.

## Project Description

This repository contains the source code for a PHP hotel management platform
developed and maintained by **Shawon Norban Group**. The application supports
public room discovery and reservations, while authorized staff can manage the
complete reservation lifecycle from booking and payment through check-in,
check-out, reporting, and customer follow-up.

The system also includes supporting business tools for accounting, purchasing,
stock, employee management, payroll, website content, payment gateways, email,
SMS, PDF documents, and QR-code workflows.

## Git Repository

| Item | Information |
| --- | --- |
| Repository | [shawonnorban/hotel-management](https://github.com/shawonnorban/hotel-management) |
| Owner | [Shawon Norban](https://github.com/shawonnorban) |
| Branch | `hotel-managements` |
| Organization | Norban Group of Companies |
| Website | [www.norbangroup.com](https://www.norbangroup.com) |

## Author And Maintainer

**Shawon Norban Group** is a software development team focused on building
efficient, data-driven web solutions and open collaboration. The project is
associated with **Norban Group of Companies**, based in Gulshan 1, Dhaka,
Bangladesh.

- GitHub: [@shawonnorban](https://github.com/shawonnorban)
- Email: [shawonnorban@gmail.com](mailto:shawonnorban@gmail.com)
- Website: [norbangroup.com](https://www.norbangroup.com)
- Facebook: [shawon2543](https://fb.com/shawon2543)

## Quick Navigation

[Features](#what-the-system-does) &nbsp;|&nbsp;
[Installation](#installation) &nbsp;|&nbsp;
[Modules](#main-modules) &nbsp;|&nbsp;
[URLs](#important-urls) &nbsp;|&nbsp;
[API](#api) &nbsp;|&nbsp;
[Security](#security-checklist)

## Quick Start

```bash
composer install
```

Then configure `application/config/database.php`, import the required module
schemas, start Apache and MySQL, and visit:

```text
http://localhost/hotel-management/
```

> **Deployment note:** Configure payment, email, SMS, and production credentials
> before enabling those integrations. Never commit secrets to the repository.

## What The System Does

### Guest Website

- Home, about, contact, gallery, privacy, and terms pages
- Room listing, room details, availability search, and checkout
- Customer registration, sign-in, sign-out, and password recovery
- Booking confirmation, success/failure pages, cancellation, and booking history
- Customer booking report and booking details
- Email notification flow after a successful booking

### Hotel Operations

- Room reservation creation, editing, details, payments, check-in, and check-out
- Room status and reservation status management
- Room types, bed types, floors, floor plans, images, facilities, sizes, and offers
- Booking types, complementary services, promotion codes, taxes, and currencies
- Customer and guest records, customer transactions, and wake-up calls
- Purchase records, purchase returns, products, suppliers, categories, units,
  ingredients, destroyed stock, and inventory reports
- Booking, customer receipt, purchase, and stock reports

### Administration And Workforce

- Admin dashboard, user management, profiles, roles, and granular permissions
- Financial years, chart of accounts, opening balance, vouchers, approvals,
  cash book, bank book, ledger, trial balance, profit and loss, and balance sheet
- HR attendance, attendance reports, awards, candidates, shortlisting,
  interviews, selection, departments, divisions, positions, and employees
- Employee performance, employee payments, holidays, leave types, leave
  applications, loans, loan installments, salary setup, and payroll generation
- Application, website, menu, banner, slider, footer, social, gallery, team,
  company, visitor, checkout, and page-title settings
- Language phrase management and module/theme addon management

## Integrations And Libraries

- PayPal payment processing and IPN support
- SSLCommerz payment gateway support with sandbox/live mode configuration
- Stripe checkout view and payment support where enabled by the deployment
- SMTP/email notifications through CodeIgniter's email library
- SMS configuration and Nexmo/Vonage client support
- PDF invoices and reports through Dompdf
- QR code generation for supported hotel workflows
- Composer autoloading for PHP dependencies

Integration credentials and production endpoints must be configured before the
related feature is used. A library being present in the repository does not by
itself mean that its provider account or credentials are active.

## Technology Stack

| Area | Technology |
| --- | --- |
| Backend | PHP and CodeIgniter |
| Database | MySQL/MariaDB through `mysqli` |
| Frontend | HTML, CSS, JavaScript, Bootstrap, jQuery plugins |
| Dependency manager | Composer |
| PDF | `dompdf/dompdf` |
| Payments | PayPal, SSLCommerz, and optional Stripe flow |
| Messaging | Email and optional Nexmo/Vonage SMS |

## Requirements

- Apache web server with URL rewriting enabled
- PHP version accepted by `composer.json` and the installed dependencies
- MySQL or MariaDB with the PHP `mysqli` extension
- Composer
- PHP extensions required by CodeIgniter, Dompdf, and configured integrations
- Write access for `application/cache/`, `application/logs/`, and upload folders

## Installation

### WAMP Local Setup

1. Copy or clone the repository into the WAMP document root, for example:
	`C:\wamp64\www\hotel-management`.
2. Start Apache and MySQL from the WAMP control panel.
3. Create a database named `hotel_management`, or select another database name.
4. From the project root, install Composer dependencies:

	```bash
	composer install
	```

5. Set the database host, username, password, and database name in
	`application/config/database.php`.
6. Import the database schema required by the deployment. Module schemas are
	stored in each module's `assets/data/database.sql`; there is no single root
	SQL file in this repository. Use the supplied installer or your deployment's
	database package when available.
7. Confirm that PHP/Apache can write to the runtime cache, log, upload, and
	asset-data directories.
8. Open the application:

	```text
	http://localhost/hotel-management/
	```

9. Remove or disable installer files after a successful installation. The
	installer controller exposes the cleanup flow when an `install/flag/env`
	file or the installer directory is present.

### Production Setup

Use HTTPS, point the virtual host document root at the project, configure a
production database user with only the required permissions, and set the
application environment to production. Do not expose configuration, cache,
logs, database dumps, or uploaded private files through the web server.

## Configuration Reference

| File or directory | Purpose |
| --- | --- |
| `application/config/config.php` | Base URL, environment, sessions, and framework settings |
| `application/config/constants.php` | Application URL constants and shared constants |
| `application/config/database.php` | Database connection and database name |
| `application/config/routes.php` | Public, admin, module, and API route mapping |
| `application/config/autoload.php` | Autoloaded libraries, helpers, and services |
| `application/config/paypallib_config.php` | PayPal library configuration |
| `application/config/migration.php` | Migration settings |
| `application/config/mimes.php` | Accepted upload MIME types |
| `application/libraries/` | PDF, payment, QR, upload, SMS, and shared libraries |
| `application/logs/` | Runtime log output |
| `application/cache/` | CodeIgniter cache and session data |

The base URL is currently calculated from the incoming request in
`application/config/config.php`. For reverse proxies, custom domains, and
production deployments, verify that the detected URL and HTTPS scheme are
correct before enabling payments or email links.

## Main Modules

These modules are present under `application/modules/`:

| Module | Responsibility |
| --- | --- |
| `accounts` | Accounting, vouchers, ledgers, and financial reports |
| `addon` | Module and theme management |
| `customer` | Customer, guest, transaction, and wake-up-call records |
| `dashboard` | Authentication, users, roles, permissions, and settings |
| `hrm` | HR, attendance, recruitment, leave, loans, and payroll |
| `payment_setting` | Payment methods, payment setup, and currencies |
| `purchase` | Purchases, returns, suppliers, products, and stock |
| `reports` | Booking, customer, purchase, and stock reports |
| `room_facilities` | Room facilities, facility details, and room sizes |
| `room_reservation` | Reservations, payments, check-in, check-out, and room status |
| `room_setting` | Rooms, beds, floors, booking types, offers, and promo codes |
| `tax_management` | Tax configuration |
| `template` | Shared dashboard layout and authentication views |
| `units` | Units, categories, products, ingredients, suppliers, and currencies |

Each module generally contains its own controllers, models, views, assets,
configuration, and (where supplied) `assets/data/database.sql` schema.

Database schema files currently included in the repository are:

- `application/modules/hrm/assets/data/database.sql`
- `application/modules/payment_setting/assets/data/database.sql`
- `application/modules/purchase/assets/data/database.sql`
- `application/modules/reports/assets/data/database.sql`
- `application/modules/room_facilities/assets/data/database.sql`
- `application/modules/room_reservation/assets/data/database.sql`
- `application/modules/room_setting/assets/data/database.sql`
- `application/modules/tax_management/assets/data/database.sql`
- `application/modules/units/assets/data/database.sql`

Check each SQL file before importing it into an existing database. Back up the
database first and import only the schemas required by the enabled modules.

## Important URLs

### Public URLs

| URL | Function |
| --- | --- |
| `/` | Hotel home page |
| `/user/roomlist` | Room listing |
| `/roomdetails` | Room details |
| `/checkout` | Booking checkout |
| `/user/login` | Customer login |
| `/register` | Customer registration |
| `/my-profile` | Customer booking report/profile |
| `/user/logout` | Customer logout |
| `/successful` | Successful booking result |
| `/fail` | Failed booking result |

### Administration URLs

| URL | Function |
| --- | --- |
| `/login` | Admin login |
| `/dashboard/home` | Admin dashboard |
| `/room_reservation/booking-list` | Reservation list |
| `/room_reservation/room-booking` | Create a reservation |
| `/room_reservation/checkin-list` | Check-in workflow |
| `/room_reservation/checkout-list` | Check-out workflow |
| `/customer/customer-list` | Customer management |
| `/reports/booking-report` | Booking report |
| `/payment_setting/payment-method-list` | Payment method management |

All routes are defined in `application/config/routes.php`; module route files
may also be loaded dynamically. Access to administration routes requires a
valid authenticated user and the appropriate role permission.

## API

`application/controllers/Api_handler_v2.php` provides JSON endpoints for the
mobile/app integration layer, including authentication, profile operations,
employee/customer data, and application status/purchase verification flows.
The controller currently enables cross-origin requests, so deployments should
review and restrict `Access-Control-Allow-Origin` and API authentication before
exposing it publicly. Send JSON request bodies with the `Content-Type:
application/json` header and consult the controller methods for the current
request fields and response format.

## Development

Install or refresh dependencies after changing `composer.json`:

```bash
composer install
```

Validate the Composer manifest:

```bash
composer validate --no-check-publish
```

For local debugging, inspect `application/logs/` and the Apache/PHP logs. Keep
debug output disabled on production systems. Before release, test booking,
payment callbacks, email/SMS delivery, PDF generation, uploads, permissions,
check-in/check-out, and database backup/restore.

## Security Checklist

- Change all default database and administrator credentials.
- Keep payment keys, SMTP passwords, SMS tokens, and purchase keys outside
  version control.
- Use HTTPS and verify payment gateway callback URLs.
- Restrict API origins and authenticate every sensitive API operation.
- Disable production error display and protect log/cache directories.
- Remove installer files after setup.
- Validate upload types and permissions, and back up the database regularly.
- Review the bundled CodeIgniter and third-party dependency versions before
  production deployment.

## Project Structure

```text
application/
  config/                  CodeIgniter and application configuration
  controllers/             Frontend, API, and installer controllers
  core/                    Application-specific CodeIgniter extensions
  helpers/                 Shared helper functions
  libraries/               Payments, PDF, QR, uploads, SMS, and utilities
  models/                  Shared database models
  modules/                 Feature modules and module assets/data
  views/                   Public, installer, and shared views
assets/                    Global CSS, JavaScript, images, fonts, and uploads
system/                    CodeIgniter framework files
vendor/                    Composer dependencies
index.php                  Application entry point
composer.json              PHP dependency manifest
LICENSE                    Project license
```

## License And Third-Party Notices

See `LICENSE` for the project license. CodeIgniter, Dompdf, PayPal, QR code,
Nexmo/Vonage, and other bundled libraries retain their own licenses and notices.
Review the package metadata under `vendor/` and the relevant library directories
before redistributing a modified deployment.
