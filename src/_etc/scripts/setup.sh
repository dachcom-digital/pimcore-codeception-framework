#!/usr/bin/env bash

set -e

source $PIMCORE_CODECEPTION_FRAMEWORK/src/_etc/scripts/yaml_reader.sh

## create download dir
mkdir -p $TEST_BUNDLE_TEST_DIR/_data/downloads

## release parameters.yml
cp $TEST_PROJECT_ROOT_DIR/app/config/parameters.example.yml $TEST_PROJECT_ROOT_DIR/app/config/parameters.yml

eval "$(parse_yaml $TEST_BUNDLE_TEST_DIR/_etc/config.yml)"

NODE='setup_files'
for CURRENT_CONFIG_NODE in ${__}; do
  if [ $CURRENT_CONFIG_NODE != $NODE ]; then continue; fi

  SECTIONS="${CURRENT_CONFIG_NODE}__"
  for FILE in ${!SECTIONS}; do

    FILE_PATH=${FILE}_path
    FILE_DEST=${FILE}_dest
    realK="$(echo ${!FILE_PATH})"
    realV="$(echo ${!FILE_DEST})"

    cp "$TEST_BUNDLE_TEST_DIR/_etc/config/$realK" "$TEST_PROJECT_ROOT_DIR/$realV"

  done
done
