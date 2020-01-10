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
    --exclude="${SRCNAME}/.git*" \
    --exclude="${SRCNAME}/.idea" \
    --exclude="${SRCNAME}/node_modules" \
    --exclude="${SRCNAME}/storage" \
    --exclude="${SRCNAME}/tests" \
    --exclude="${SRCNAME}/.env*" \
    --exclude="${SRCNAME}/.nvmrc" \
    --exclude="${SRCNAME}/.phpstorm.meta.php" \
    --exclude="${SRCNAME}/_ide_helper.php" \
    --exclude="${SRCNAME}/*.lock" \
    --exclude="${SRCNAME}/*.enc" \
    --exclude="${SRCNAME}/*.yml" \
    --exclude="${SRCNAME}/*.md" \
    --exclude="${SRCNAME}/*.sh" \
    --exclude="${SRCNAME}/Makefile" \
    --exclude="${SRCNAME}/*.xml" \
    --exclude="${SRCNAME}/*.js" \
    --exclude="${SRCNAME}/*gz" \
    "${SRCNAME}"

cd ${SRCNAME}
# put it back
composer install