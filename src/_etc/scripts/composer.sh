#!/usr/bin/env bash

set -e

source $PIMCORE_CODECEPTION_FRAMEWORK/src/_etc/scripts/yaml_reader.sh

eval "$(parse_yaml $TEST_BUNDLE_TEST_DIR/_etc/config.yml)"

PACKAGES=''
NODE='additional_composer_packages'

for CURRENT_CONFIG_NODE in ${__}; do
  if [ $CURRENT_CONFIG_NODE != $NODE ]; then continue; fi
  SECTIONS="${CURRENT_CONFIG_NODE}__"
  for FILE in ${!SECTIONS}; do
    PACKAGE=${FILE}_package
    VERSION=${FILE}_version
    PACKAGES+=" ${!PACKAGE}:${!VERSION}"
  done
done

if [ ! -z "$PACKAGES" ]; then
  echo "Installing pimcore $TEST_PIMCORE_VERSION and symfony $TEST_SYMFONY_VERSION with additional composer packages$PACKAGES"
fi

composer req pimcore/pimcore:$TEST_PIMCORE_VERSION symfony/symfony:$TEST_SYMFONY_VERSION $PACKAGES --no-interaction --ignore-platform-reqs --no-scripts --with-all-dependencies
composer install --no-progress --prefer-dist --optimize-autoloader --ignore-platform-reqs
