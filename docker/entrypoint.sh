#!/bin/sh
set -e

# 1) Bootstrap du projet si nécessaire
if [ ! -f "/app/composer.json" ]; then
  echo "➡️  Pas de composer.json, création du squelette Symfony…"
  composer create-project symfony/skeleton . --no-interaction
  composer require symfony/orm-pack symfony/twig-pack symfony/security-bundle symfony/validator symfony/runtime doctrine/doctrine-migrations-bundle --no-interaction
  composer require --dev symfony/maker-bundle
fi

# 2) Install deps
echo "➡️  composer install…"
composer install --no-interaction

# 3) DATABASE_URL (si vide, on écrit celui par défaut fourni via compose)
if ! grep -q '^DATABASE_URL=' .env 2>/dev/null; then
  if [ -n "${DATABASE_URL:-}" ]; then
    echo "DATABASE_URL=\"$DATABASE_URL\"" >> .env
  fi
fi

# 4) DB + migrations (best effort)
php bin/console doctrine:database:create --if-not-exists || true
php bin/console doctrine:migrations:migrate --no-interaction || true

# 5) Démarrer le serveur PHP intégré (suffisant en local)
echo "✅  Serveur prêt sur http://0.0.0.0:8000"
exec php -S 0.0.0.0:8000 -t public