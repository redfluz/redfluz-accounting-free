#!/usr/bin/env bash
# Easy one-click install for first-time users (works from a clean checkout: no vendor/node_modules).
# Usage: ./easy-install.sh
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║        Redfluz Accounting — Easy Install (Fresh Start)     ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

need() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "  ✗ Missing required command: $1"
    echo "    Please install it and run this script again."
    exit 1
  fi
}

need php
need composer
need npm

# Ensure .env exists
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
  echo "▸ Created .env from .env.example"
fi

# Ensure SQLite database file exists (default setup)
mkdir -p database
if [ ! -f database/database.sqlite ]; then
  : > database/database.sqlite
  echo "▸ Created database/database.sqlite"
fi

echo ""
echo "▸ Installing PHP dependencies (composer install)..."
composer install --no-interaction --prefer-dist --optimize-autoloader
echo "  ✓ PHP dependencies installed"

echo ""
echo "▸ Setting up the application with demo data..."
php artisan app:install --demo --no-interaction

echo ""
echo "▸ Installing frontend dependencies (npm install)..."
npm install
echo "  ✓ Frontend dependencies installed"

echo ""
echo "▸ Building frontend assets (npm run build)..."
npm run build
echo "  ✓ Frontend assets built"

echo ""
echo "▸ Creating storage link..."
# Relative links require symfony/filesystem; fall back to a standard (absolute) link if unavailable.
if ! php artisan storage:link --relative --force 2>/dev/null; then
  echo "  (Relative link unavailable, creating standard storage link...)"
  php artisan storage:link --force || true
fi

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                  Easy Install Complete!                   ║"
echo "╠════════════════════════════════════════════════════════════╣"
echo "║  Demo data is ready.                                      ║"
echo "║                                                           ║"
echo "║  Login:    admin@example.com                              ║"
echo "║  Password: password                                       ║"
echo "║                                                           ║"
echo "║  Start:    php artisan dev                                ║"
echo "║  Open:     http://localhost:8000                          ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
