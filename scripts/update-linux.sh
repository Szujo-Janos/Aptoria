#!/usr/bin/env bash
set -euo pipefail

[ -f .env ] || cp .env.example .env
mkdir -p database storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
[ -f database/database.sqlite ] || touch database/database.sqlite
composer install
php artisan key:generate --force
php artisan optimize:clear
php artisan migrate --force
php artisan aptoria:health
