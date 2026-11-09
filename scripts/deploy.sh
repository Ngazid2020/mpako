#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
# Déploiement BeeZ sur le serveur — exécuté par .github/workflows/deploy.yml
# (ou à la main : `bash scripts/deploy.sh` depuis le dossier de l'application).
#
# Le code est déjà à jour (git checkout de la version testée) et public/build
# a été construit par la CI. Ce script met à jour dépendances, base et caches.
# ─────────────────────────────────────────────────────────────
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

echo "▶ Déploiement de $(git rev-parse --short HEAD) dans $(pwd)"

# Remettre l'application en ligne quoi qu'il arrive
trap '"$PHP_BIN" artisan up || true' EXIT

"$PHP_BIN" artisan down --retry=30 --refresh=15 || true

echo "▶ Dépendances PHP (production)"
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

echo "▶ Migrations"
"$PHP_BIN" artisan migrate --force

echo "▶ Caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize
"$PHP_BIN" artisan filament:optimize
"$PHP_BIN" artisan icons:cache || true
"$PHP_BIN" artisan storage:link 2>/dev/null || true

echo "▶ Workers et nettoyage"
"$PHP_BIN" artisan queue:restart
"$PHP_BIN" artisan sanctum:prune-expired --hours=24 || true

echo "✔ Déploiement terminé"
