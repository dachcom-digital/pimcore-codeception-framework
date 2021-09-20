#!/usr/bin/env bash

set -e

source $PIMCORE_CODECEPTION_FRAMEWORK/src/_etc/scripts/yaml_reader.sh

eval "$(parse_yaml $TEST_BUNDLE_TEST_DIR/_etc/config.yml)"

## define structure (bundle is available under lib/test-bundle)
## add test-bundle composer.json to skeleton composer.json
composer config repositories.local '{"type": "path", "url": "./lib/test-bundle", "options": {"symlink": true}}' --file composer.json

# add TestKernel to autoload-dev
sed -i '$s%}%,"autoload-dev":{"classmap" : ["src/TestKernel.php"]} }%' composer.json

# define variables
PACKAGES=''
NODE='additional_composer_packages'
PACKAGE_GITHUB_REPOSITORY=$(echo $GITHUB_REPOSITORY |sed  s/pimcore-//)

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
composer update --no-progress --prefer-dist --optimize-autoloader

# install pimcore test infrastructure
if [ ! -d "vendor/pimcore/pimcore/tests" ]; then

  CURRENT_PIMCORE_VERSION=$(composer show pimcore/pimcore | grep 'version' | grep -o -E '\*\ .+' | cut -d' ' -f2 | cut -d',' -f1);
  CURRENT_PIMCORE_HASH=$(composer show pimcore/pimcore | grep 'source' | grep -o -E '\git\ .+' | cut -d' ' -f2);

  echo "Installing pimcore test data for version $CURRENT_PIMCORE_VERSION ($CURRENT_PIMCORE_HASH)"

  git clone --depth 1 --filter=blob:none --no-checkout https://github.com/pimcore/pimcore
  cd pimcore
  git checkout $CURRENT_PIMCORE_HASH -- tests
  cd ../
  mv pimcore/tests vendor/pimcore/pimcore
  rm -r pimcore

fi
