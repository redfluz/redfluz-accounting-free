# Redfluz Accounting

A lightweight accounting and business management system built with Laravel.

## Free vs Premium

| Feature | Free | Premium |
|---|:---:|:---:|
| CRM (Contacts & Companies) | ✅ | ✅ |
| Project Management | - | ✅ |
| Inventory & Stock | Partial | ✅ |
| Finance (Journal & Ledger) | ✅ | ✅ |
| Bank & Payments | ✅ | ✅ |
| Bank Reconciliation | — | ✅ |
| Purchase Orders & Invoices | ✅ | ✅ |
| Purchase Requests & RFQ | — | ✅ |
| Sales Orders & Invoices | ✅ | ✅ |
| Quotations | ✅ | ✅ |
| Delivery Orders | ✅ | ✅ |
| Returns (RMA) | — | ✅ |
| HR & Payroll | — | ✅ |
| Claims & Advances | — | ✅ |
| Leave Management | — | ✅ |
| Fixed Assets & Depreciation | — | ✅ |
| Multi-Stock Location | 1 | Unlimited |
| Financial Posting Periods | — | ✅ |
| Activity Logs | — | ✅ |
| WebSocket Notifications | — | ✅ |

## Requirements

- PHP `^8.5` (8.5 or later, below 9.0)
- PHP extensions: BCMath, GD, PDO, SQLite (`pdo_sqlite` and `sqlite3`), ZIP, and Laravel's Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, Session, Tokenizer and XML extensions
- Composer
- Node.js `^20.19.0 || >=22.12.0` & NPM (Vite 8's engine requirement)

**Database:** SQLite is the currently supported migration path. See the compatibility caveat below before considering another database engine.

**Recommended local environments:**
- **Windows:** [Laragon](https://laragon.org) — configure PHP 8.5 and the extensions above
- **macOS:** [Laravel Herd](https://herd.laravel.com) — select PHP 8.5 and enable the extensions above
- **Linux:** Install PHP 8.5 CLI and matching extension packages from a repository that provides them; distribution defaults may be too old.

## Quick Install

### Easy Install (recommended, one-click from a clean checkout)

If you cloned the repo and have **no `vendor/` or `node_modules/`** yet, use the standalone installer (it does not require `php artisan`, which needs `vendor/` first).

**Windows, macOS, Linux (cross-platform, recommended):**

```bash
# 1. Clone and enter directory
git clone <repository-url> redfluz-accounting-free
cd redfluz-accounting-free

# 2. One-click install (with demo data)
php easy-install.php
```

**macOS / Linux (shell script alternative):**

```bash
chmod +x easy-install.sh   # if needed
./easy-install.sh
# or: bash easy-install.sh
```

Requires `php`, `composer`, and `npm` on your PATH. The installer installs PHP/JS dependencies, creates `.env` and the SQLite database if missing, runs migrations and seeds demo data, builds frontend assets, and creates a portable storage link.

Then start the app with `composer run dev` (or `php artisan serve` and `npm run dev`) and open http://localhost:8000.

**Default login:** `admin@example.com` / `password`

> **Note:** `php artisan app:install` and `php artisan app:easy-install` are Artisan commands — they require `vendor/` to exist. From a truly clean checkout, run `php easy-install.php` (works on Windows too) or `composer install` first.

### Standard Install (step-by-step)

```bash
# 1. Clone and enter directory
git clone <repository-url> redfluz-accounting-free
cd redfluz-accounting-free

# 2. Install dependencies
composer install
npm install && npm run build

# 3. Copy environment file (keep DB_CONNECTION=sqlite)
cp .env.example .env
```

**4. Choose ONE installer command on a fresh database — these are alternatives, not sequential steps.**

For a clean install, answer **no** to the demo-data prompt:

```bash
php artisan app:install
```

Or, for demo data (customers, products, sample transactions):

```bash
php artisan app:install --demo
```

The installer will:
- Generate your APP_KEY if missing
- Create the default SQLite database file if missing (`database/` must be writable)
- Run all migrations
- Seed essential data (roles, menus, chart of accounts), plus demo data when selected
- Create an absolute storage symlink if `public/storage` does not exist

**5. After either installer option, create a portable storage link:**

```bash
php artisan storage:link --relative --force
```

The installer still uses an absolute link. This follow-up replaces an existing symlink with a relative link to this copy's `storage/app/public`, resolving copied-folder 403 errors caused by a stale absolute link. `--force` replaces only existing symlinks, not real directories or files; it will refuse to overwrite those.

**Default login:** `admin@example.com` / `password`

### Artisan Easy Reinstall (when `vendor/` is already installed)

For a destructive wipe-and-reinstall with demo data (removes `vendor/`, `node_modules/`, database, uploads, caches, and build files, then reinstalls everything):

```bash
php artisan app:easy-install --force
```

## Database Compatibility

SQLite is the currently supported migration path, including for the production steps below. The `create_users_table` migration uses `CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL`. MySQL/MariaDB do not support this partial-index syntax, so changing `.env` to either engine is not a supported installation recipe. Other database engines have not been verified; no compatibility is claimed.

## Reset (Factory Reset)

To completely wipe everything — database, uploaded files, logs, sessions, caches:

```bash
php artisan app:reset

# Skip the confirmation prompt (for scripts/CI)
php artisan app:reset --force
```

Then run `php artisan app:install` to set up fresh.

## Manual Install (alternative)

For a fresh clean database, after cloning and installing dependencies as above. If you want demo data, choose `php artisan app:install --demo` on a fresh database instead of this manual sequence; do not add it after a populated manual install. `db:seed --demo` is not a valid command option.

```bash
cp .env.example .env
php artisan key:generate

# Keep DB_CONNECTION=sqlite and use the default database path
# Ensure database/ is writable; create the SQLite file if absent
touch database/database.sqlite
php artisan migrate
php artisan db:seed          # essential data only

php artisan storage:link --relative --force
php artisan serve
```

## Development

The Composer `dev` script starts the web server, queue listener, Reverb and Vite:

```bash
composer run dev
```

There is no application `php artisan dev` command. Or run just the web server and Vite in separate terminals:

```bash
php artisan serve
npm run dev
```

## Production

After installation, set `APP_ENV=production`, `APP_DEBUG=false` and `APP_URL` to the actual public URL (including the correct scheme) in the deployment's `.env` before caching configuration. Change the default admin password before exposing the application.

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Clean source packages:** Explicitly omit `public/hot` (Vite's local dev-server marker), local `.env` files and secrets, database data/backups, runtime uploads, caches, sessions and logs. Keep `.env.example` and the supplied empty directory structure/`.gitignore` placeholders. Review the actual archive contents: `.gitignore` does not automatically filter ZIP files. Deployment-specific data stays on the deployment, not in the clean source package.
