# Install WatchEarn on Windows 11 + XAMPP (localhost)

> Run commands **one at a time** and wait for each to finish. Do not paste
> the whole list at once — prompts can swallow the next command (this bit
> us during the Tryvora install).

## 0. Requirements

- XAMPP with **PHP 8.3 or higher** (Laravel 13 will not install on PHP 8.2).
  Ali's terminal PHP is the WinGet PHP 8.4 — that works fine.
- Composer installed.
- In your terminal's `php.ini`, make sure these extensions are enabled
  (remove the `;` at the line start if present): `fileinfo`, `mbstring`,
  `openssl`, `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `curl`, `zip`.

Check:

```
php -v
composer --version
```

## 1. Extract and enter the project

Extract `watch-earn.zip` to `C:\xampp\htdocs\watchearn`, then:

```
cd C:\xampp\htdocs\watchearn
```

## 2. Install dependencies

```
composer install
```

## 3. Create the database

Open `http://localhost/phpmyadmin`, click **New**, create a database named
`watchearn` (collation `utf8mb4_general_ci`).

## 4. Environment file

```
copy .env.example .env
```

Then generate the app key:

```
php artisan key:generate
```

`.env` already points at MySQL database `watchearn`, user `root`, no
password (XAMPP default). Mail is set to `log`, so verification codes are
written to `storage\logs\laravel.log` — perfect for local testing. For a
live server, set real SMTP values in `.env`.

## 5. Migrate and seed

```
php artisan migrate --seed
```

This creates all tables, default settings, the admin and demo accounts,
and 3 sample tasks.

## 6. Run

```
php artisan serve
```

Open `http://localhost:8000`.

- **Admin:** `admin@watchearn.local` / `password`
  → go to **Admin → Global Settings** and set **your real WhatsApp
  number** (the seeded `923000000000` is a placeholder). Also review the
  minimum withdrawal and commission.
- **Worker demo:** `demo@watchearn.local` / `password`
- Anyone registering needs a referral code — the admin's own code is
  `ADMIN001`. Try registering with it, read the 6-digit code from
  `storage\logs\laravel.log`, and verify.

## 7. Run the tests (optional but recommended)

```
php artisan test
```

Expected: **24 passed**. Tests use an in-memory SQLite database, so they
never touch your real data.

## Going live later

1. Point your domain at the server, set `APP_URL` and `APP_ENV=production`,
   `APP_DEBUG=false` in `.env`.
2. Configure real SMTP so verification codes reach users.
3. Change the seeded admin/demo passwords, or delete the demo account.
4. Follow `docs/CPAGRIP_SETUP.md` to connect your real CPAGrip account.
