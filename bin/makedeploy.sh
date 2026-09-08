#!/bin/bash
RELVER=$1

# get the right version of npm using nvm
# shellcheck source=/dev/null
source ~/.nvm/nvm.sh
# shellcheck source=/dev/null
source ~/.profile
# shellcheck source=/dev/null
source ~/.bashrc

cd ..

nvm install
nvm use

# install any packages
npm ci

# build production css
npm run prod

# reduce the size of the vendor directory to things we need.
rm -rf ./vendor

composer install --no-dev --optimize-autoloader --prefer-dist

php artisan config:clear
php artisan route:clear
php artisan view:clear

SRCNAME=${PWD##*/}
cd ..
# pack it up without most of the dev extras
rm -f "${SRCNAME}"_"${RELVER}".tgz
tar -cvzf "${SRCNAME}"_"${RELVER}".tgz  \
    --exclude="${SRCNAME}/.editorconfig" \
    --exclude="${SRCNAME}/.env" \
    --exclude="${SRCNAME}/.env.example" \
    --exclude="${SRCNAME}/.env.ghactions" \
    --exclude="${SRCNAME}/.docker" \
    --exclude="${SRCNAME}/.git" \
    --exclude="${SRCNAME}/.gitattributes" \
    --exclude="${SRCNAME}/.github" \
    --exclude="${SRCNAME}/.gitignore" \
    --exclude="${SRCNAME}/.husky" \
    --exclude="${SRCNAME}/.idea" \
    --exclude="${SRCNAME}/.nvmrc" \
    --exclude="${SRCNAME}/build" \
    --exclude="${SRCNAME}/docs" \
    --exclude="${SRCNAME}/node_modules" \
    --exclude="${SRCNAME}/storage" \
    --exclude="${SRCNAME}/tests" \
    --exclude="${SRCNAME}/.phpstorm.meta.php" \
    --exclude="${SRCNAME}/_ide_helper.php" \
    --exclude="${SRCNAME}/_ide_helper_models.php" \
    --exclude="${SRCNAME}/composer.lock" \
    --exclude="${SRCNAME}/Dockerfile" \
    --exclude="${SRCNAME}/phpstan.neon" \
    --exclude="${SRCNAME}/phpunit.xml" \
    --exclude="${SRCNAME}/README.md" \
    --exclude="${SRCNAME}/staging_rsa.enc" \
    --exclude="${SRCNAME}/package-lock.json" \
    "${SRCNAME}"

cd "${SRCNAME}" || exit
# put it back
composer install
