#!/usr/bin/env bash

set -euo pipefail

project_dir="${1:-/home/ploi/v2.spaansspreken.nl}"

if [[ ! -f "${project_dir}/composer.json" || ! -f "${project_dir}/package-lock.json" ]]; then
    echo "Deploy afgebroken: composer.json of package-lock.json ontbreekt in ${project_dir}." >&2
    exit 1
fi

cd "${project_dir}"

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
npm ci --ignore-scripts --no-audit --no-fund
npm run build
node scripts/verify-built-assets.mjs

php artisan optimize:clear
php artisan migrate --force --no-interaction
php artisan optimize

echo "Deploy gereed: dependencies, database, caches en actuele Vite-assets zijn gecontroleerd."
