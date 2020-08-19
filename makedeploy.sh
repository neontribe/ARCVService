#!/bin/bash

# get the right version of npm using nvm
source ~/.nvm/nvm.sh
source ~/.profile
source ~/.bashrc
nvm install
nvm use

# install any yarn packages
rm -rf ./node_modules
yarn install

# build production css
yarn prod

# reduce the size of the vendor directory to things we need.
rm -rf ./vendor
composer install --no-dev -o

SRCNAME=${PWD##*/}
cd ..
# pack it up without most of the dev extras
rm -f ${SRCNAME}.tgz
tar -cvzf ${SRCNAME}.tgz  \
    --exclude="${SRCNAME}/.docker" \
    --exclude="${SRCNAME}/.git" \
    --exclude="${SRCNAME}/.gitattributes" \
    --exclude="${SRCNAME}/.gitignore" \
    --exclude="${SRCNAME}/.idea" \
    --exclude="${SRCNAME}/node_modules" \
    --exclude="${SRCNAME}/storage" \
    --exclude="${SRCNAME}/tests" \
    --exclude="${SRCNAME}/.env" \
    --exclude="${SRCNAME}/.env.example" \
    --exclude="${SRCNAME}/.env.travis" \
    --exclude="${SRCNAME}/.nvmrc" \
    --exclude="${SRCNAME}/.phpstorm.meta.php" \
    --exclude="${SRCNAME}/_ide_helper.php" \
    --exclude="${SRCNAME}/_ide_helper_models.php" \
    --exclude="${SRCNAME}/yarn.lock" \
    --exclude="${SRCNAME}/composer.lock" \
    --exclude="${SRCNAME}/staging_rsa.enc" \
    --exclude="${SRCNAME}/.travis.yml" \
    --exclude="${SRCNAME}/docker-compose.yml" \
    --exclude="${SRCNAME}/README.md" \
    --exclude="${SRCNAME}/UPGRADE.md" \
    --exclude="${SRCNAME}/Docker.md" \
    --exclude="${SRCNAME}/makedeploy.sh" \
    --exclude="${SRCNAME}/Makefile" \
    --exclude="${SRCNAME}/phpunit.xml" \
    --exclude="${SRCNAME}/webpack.mix.js" \
    --exclude="${SRCNAME}/.phpunit.result.cache" \
    "${SRCNAME}"

cd ${SRCNAME}
# put it back
composer install