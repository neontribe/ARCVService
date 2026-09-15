#!/bin/sh
set -e

FILES="${0} script/* $(git ls-files "*.sh")"

for I in ${FILES}; do
  echo "Checking ${I}..."
  perl -pe 's/\{\{.*?\}\}/TEMPLATE_VALUE/g' < "${I}" | shellcheck -
done

echo OK
