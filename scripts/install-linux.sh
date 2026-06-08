#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-}"
NO_DEV="${NO_DEV:-1}"
NO_SEED="${NO_SEED:-0}"

cd "$PROJECT_ROOT"

echo "Aptoria Linux / online hosting installer"
echo "Project root: $PROJECT_ROOT"

require_command() {
  if ! command -v "$1" >/dev/null 2>&1; then
    echo "Missing command: $1" >&2
    exit 1
  fi
}

require_php_extension() {
  if ! "$PHP_BIN" -r "exit(extension_loaded('$1') ? 0 : 1);"; then
    echo "Missing PHP extension: $1" >&2
    exit 1
  fi
}

composer_cmd() {
  if [ -n "$COMPOSER_BIN" ]; then
    # Supports values such as "composer", "/usr/local/bin/composer" or "php composer.phar".
    # shellcheck disable=SC2086
    $COMPOSER_BIN "$@"
    return
  fi

  if command -v composer >/dev/null 2>&1; then
    composer "$@"
    return
  fi

  if [ -f "$PROJECT_ROOT/composer.phar" ]; then
    "$PHP_BIN" "$PROJECT_ROOT/composer.phar" "$@"
    return
  fi

  echo "Composer is not available." >&2
  echo "Install Composer or run:" >&2
  echo "php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\"" >&2
  echo "php composer-setup.php --install-dir=. --filename=composer.phar" >&2
  echo "rm -f composer-setup.php" >&2
  echo "COMPOSER_BIN=\"php composer.phar\" bash scripts/install-linux.sh" >&2
  exit 1
}

require_command "$PHP_BIN"

PHP_VERSION="$($PHP_BIN -r 'echo PHP_VERSION;')"
if ! "$PHP_BIN" -r "exit(version_compare(PHP_VERSION, '8.2.0', '>=') ? 0 : 1);"; then
  echo "PHP >= 8.2 is required. Current: $PHP_VERSION" >&2
  exit 1
fi

echo "PHP version: $PHP_VERSION"

for ext in ctype curl fileinfo json mbstring openssl pdo tokenizer xml; do
  require_php_extension "$ext"
done

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
  require_php_extension pdo_sqlite
fi

mkdir -p bootstrap/cache \
  database/backups \
  storage/app/private \
  storage/app/public \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Created .env from .env.example"
else
  echo "Keeping existing .env"
fi

if grep -q '^APTORIA_VERSION=' .env; then
  sed -i.bak '/^APTORIA_VERSION=/d' .env
  rm -f .env.bak
  echo "Removed obsolete APTORIA_VERSION override"
fi

if grep -q '^DB_CONNECTION=sqlite' .env || ! grep -q '^DB_CONNECTION=' .env; then
  mkdir -p database
  touch database/database.sqlite
  echo "SQLite database file is ready"
fi

if [ "$NO_DEV" = "1" ]; then
  composer_cmd install --no-dev --no-interaction --prefer-dist --optimize-autoloader
else
  composer_cmd install --no-interaction --prefer-dist
fi

if ! grep -qE '^APP_KEY=.+$' .env; then
  "$PHP_BIN" artisan key:generate --force
fi

"$PHP_BIN" artisan optimize:clear

if [ "$NO_SEED" = "1" ]; then
  "$PHP_BIN" artisan migrate --force
else
  "$PHP_BIN" artisan migrate --seed --force
fi

VERSION="$(cat VERSION | tr -d '[:space:]')"
cat > storage/app/installed.lock <<EOF
{
  "installed_at": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "created_by": "install-linux.sh",
  "version": "$VERSION",
  "app_url": "$(grep '^APP_URL=' .env | head -n1 | cut -d= -f2-)"
}
EOF

if grep -q '^APP_ENV=production' .env; then
  "$PHP_BIN" artisan config:cache
  "$PHP_BIN" artisan route:cache
  "$PHP_BIN" artisan view:cache
else
  "$PHP_BIN" artisan optimize:clear
fi

echo "Installation complete."
echo "For production, point Apache/Nginx document root to: $PROJECT_ROOT/public when possible."
echo "If you installed into a subfolder, the root index.php/.htaccess fallback is active."
echo "Scheduler example: * * * * * cd $PROJECT_ROOT && $PHP_BIN artisan schedule:run >> /dev/null 2>&1"
