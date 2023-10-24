#!/usr/bin/env bash

set -e

source $PIMCORE_CODECEPTION_FRAMEWORK/src/_etc/scripts/yaml_reader.sh

eval "$(parse_yaml $TEST_BUNDLE_TEST_DIR/_etc/config.yaml)"

# define variables
PACKAGES=''
NODE='additional_composer_packages'
PACKAGE_GITHUB_REPOSITORY=$(echo $GITHUB_REPOSITORY |sed  s/pimcore-//)
REQUIRE_DEV_DATA=$(sed -nr '/.*(\brequire-dev\b).*\{/,/\}/p' ./lib/test-bundle/composer.json)
SAVE_REQUIRE_DEV_DATA=$(printf "%s\n" "$REQUIRE_DEV_DATA" | sed -e 's/[]\/${}\n"*.:^[]/\\&/g' | tr -d '\n' | sed -z 's/\(.*\}\),/\1/');

## define structure (bundle is available under lib/test-bundle)
## add test-bundle composer.json to skeleton composer.json
composer config repositories.local '{"type": "path", "url": "./lib/test-bundle", "options": {"symlink": true}}' --file composer.json

## use pimcore source
composer config 'preferred-install.pimcore/pimcore' 'source' --file composer.json
composer config 'preferred-install.*' 'dist' --file composer.json

# replace require-dev from bundle
sed -i "/require-dev/{:a;N;/}/!ba;N;s/.*\n/$SAVE_REQUIRE_DEV_DATA,\n/}" composer.json

# add TestKernel to autoload-dev
sed -i '$s%}%,"autoload-dev":{"classmap" : ["src/TestKernel.php"]} }%' composer.json

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
  echo "Installing pimcore $TEST_PIMCORE_VERSION with additional composer packages$PACKAGES"
fi

composer req pimcore/pimcore:$TEST_PIMCORE_VERSION $PACKAGES $PACKAGE_GITHUB_REPOSITORY:@dev --no-interaction --no-scripts --no-update
composer update --no-progress --no-scripts