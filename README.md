# Hotel Management System

A PHP and CodeIgniter-based hotel management system for managing rooms, guests,
reservations, payments, reports, and day-to-day hotel operations.

## Features

- Public hotel website with room browsing and booking
- Customer registration, login, and booking history
- Room, room facility, unit, tax, and addon management
- Reservation and booking status management
- Payment configuration and payment gateway integrations
- Customer, HR, purchase, accounts, and reporting modules
- PDF generation, email notifications, and QR code support
- Permission-based administration dashboard

## Requirements

- PHP version supported by this project's Composer configuration
- Apache with `mod_rewrite` enabled
- MySQL or MariaDB with the `mysqli` extension
- Composer
- PHP extensions required by CodeIgniter and the installed Composer packages

## Installation

1. Clone or extract the project into your web server document root. For WAMP,
	place it under `C:\wamp64\www\hotel-management`.
2. Create a MySQL database named `hotel_management` (or choose another name).
3. Install the PHP dependencies from the project directory:

	```bash
	composer install
	```

4. Update the database credentials in
	`application/config/database.php`.
5. Make sure the web server can write to `application/cache/` and
	`application/logs/`.
6. Open the project URL in a browser. If the application installer is enabled,
	complete the setup there; otherwise import the database schema supplied with
	your deployment and open the application normally.

For a default WAMP setup, the application is usually available at:

```text
http://localhost/hotel-management/
```

The base URL is detected from the current request in
`application/config/config.php`. Set it explicitly for production or unusual
proxy/server setups.

## Configuration

- Database: `application/config/database.php`
- Application URL and environment: `application/config/config.php`
- Routes: `application/config/routes.php`
- Payment provider settings: `application/config/paypallib_config.php` and
  the payment settings module
- Runtime logs: `application/logs/`

Set `ENVIRONMENT` to `production` for a live deployment and disable displaying
database errors. Never commit production passwords, API keys, or payment
credentials to the repository.

## Project Structure

```text
application/controllers/   Application controllers
application/models/        Database models
application/modules/       Feature modules and admin workflows
application/views/         Public and shared views
application/config/        CodeIgniter configuration
assets/                    CSS, JavaScript, images, and uploaded assets
system/                    CodeIgniter framework core
vendor/                    Composer dependencies
```

## Development

Run Composer after changing `composer.json`:

```bash
composer install
```

Keep debug mode enabled only during local development. Before deploying,
verify file permissions, HTTPS, database credentials, payment settings, mail
settings, and scheduled backups.

## License

This project includes CodeIgniter and third-party packages. See `LICENSE` and
the individual package licenses in `vendor/` for licensing details.
