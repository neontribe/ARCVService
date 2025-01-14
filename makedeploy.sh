#!/usr/bin/env bash

set -e

usage() {
    echo "Usage: $0 <release-version> [--tests]"
    echo
    echo "  <release-version>  The release version to be appended to the output tarball name."
    echo "  --tests            If set, tests and related files will NOT be excluded from the tarball."
    exit 1
}

# Make sure a release version was supplied.
if [ -z "$1" ]; then
    usage
fi

RELVER="$1"
shift

# Check for optional flags
INCLUDE_TESTS=false
while [[ "$#" -gt 0 ]]; do
    case "$1" in
        --tests)
            INCLUDE_TESTS=true
            shift
            ;;
        *)
            echo "Unknown option: $1"
            usage
            ;;
    esac
done

# Load nvm (adjust paths as needed for your environment)
source ~/.nvm/nvm.sh
source ~/.profile
source ~/.bashrc

# Ensure correct Node version is installed and in use
nvm install
nvm use

# Re-install Yarn dependencies
rm -rf ./node_modules
yarn install

# Build production CSS (or other production assets)
yarn prod

# Reduce the size of vendor directory by installing only production dependencies
rm -rf ./vendor

if [ "${INCLUDE_TESTS}" = false ]; then
  composer install --no-dev -o
else
  composer install
fi

SRCNAME=${PWD##*/}
cd ..

# Remove any old tarball
rm -f "${SRCNAME}.tgz" "${SRCNAME}_${RELVER}.tgz"

# Build our array of exclusion patterns
EXCLUDES=(
    # Docker
    "--exclude=${SRCNAME}/.docker"
    "--exclude=${SRCNAME}/Dockerfile"
    "--exclude=${SRCNAME}/docker-compose.yml"

    # Git
    "--exclude=${SRCNAME}/.git"
    "--exclude=${SRCNAME}/.gitattributes"
    "--exclude=${SRCNAME}/.gitignore"
    "--exclude=${SRCNAME}/**/.keep"

    # IDE / Editor
    "--exclude=${SRCNAME}/.idea"
    "--exclude=${SRCNAME}/.phpstorm.meta.php"
    "--exclude=${SRCNAME}/_ide_helper.php"
    "--exclude=${SRCNAME}/_ide_helper_models.php"
    "--exclude=${SRCNAME}/.vscode"
    "--exclude=${SRCNAME}/**/.DS_STORE"

    # symlink-ables
    "--exclude=${SRCNAME}/.env"
    "--exclude=${SRCNAME}/.env.*"
    "--exclude=${SRCNAME}/storage"
    "--exclude=${SRCNAME}/build"
    "--exclude=${SRCNAME}/.phpunit.*"

    # Node, Yarn, Composer
    "--exclude=${SRCNAME}/.nvmrc"
    "--exclude=${SRCNAME}/node_modules"
    "--exclude=${SRCNAME}/yarn*"
    "--exclude=${SRCNAME}/composer.lock"
    "--exclude=${SRCNAME}/staging_rsa.enc"
    "--exclude=${SRCNAME}/webpack.mix.js"

    # This deployment file
    "--exclude=${SRCNAME}/makedeploy.sh"
)

# Only exclude tests if --tests is NOT provided
if [ "${INCLUDE_TESTS}" = false ]; then
    EXCLUDES+=(
        "--exclude=${SRCNAME}/phpunit.xml"
        "--exclude=${SRCNAME}/tests"
    )
fi

# Create the tarball
tar -cvzf "${SRCNAME}_${RELVER}.tgz" \
    "${EXCLUDES[@]}" \
    "${SRCNAME}"

cd "${SRCNAME}"

# Re-install composer dev dependencies if needed
composer install
