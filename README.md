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

- PHP >= 8.4
- Composer
- Node.js >= 18 & NPM

**Database:** SQLite (default, zero config) or MySQL/MariaDB

**Recommended local environments:**
- **Windows:** [Laragon](https://laragon.org) — lightweight, one-click, includes MySQL
- **macOS:** [Laravel Herd](https://herd.laravel.com) — lightweight, no Docker
- **Linux:** `sudo apt install php php-cli php-mbstring php-xml php-curl php-zip unzip`

## Quick Install

```bash
# 1. Clone and enter directory
git clone <repository-url>
cd redfluz-accounting

# 2. Install dependencies
composer install
npm install && npm run build

# 3. Copy environment file (SQLite is default — no database setup needed!)
cp .env.example .env

# 4. Run the installer (handles key, migrations, seeding)
php artisan app:install

# For demo data (customers, products, sample transactions):
php artisan app:install --demo
```

The installer will:
- Generate your APP_KEY
- Create SQLite database (or connect to MySQL if configured)
- Run all migrations
- Seed essential data (roles, menus, chart of accounts)
- Create the storage symlink

**Default login:** `admin@example.com` / `password`

## Switching to MySQL (optional)

SQLite is perfect for trying out. For production or multi-user, edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=redfluz_accounting
DB_USERNAME=root
DB_PASSWORD=your_password
```

Then re-run: `php artisan app:install`

## Reset (Factory Reset)

To completely wipe everything — database, uploaded files, logs, sessions, caches:

```bash
php artisan app:reset

# Skip the confirmation prompt (for scripts/CI)
php artisan app:reset --force
```

Then run `php artisan app:install` to set up fresh.

## Manual Install (alternative)

If you prefer step-by-step control:

```bash
cp .env.example .env
php artisan key:generate

# Edit .env with your database credentials, then:
php artisan migrate
php artisan db:seed          # clean install
php artisan db:seed --demo   # OR with demo data

php artisan storage:link
php artisan serve
```

## Development

```bash
php artisan dev
```

Or run services separately:

```bash
php artisan serve
npm run dev
```

## Production

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
