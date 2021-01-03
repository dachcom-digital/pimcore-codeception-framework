#!/usr/bin/env bash

set -e

source $PIMCORE_CODECEPTION_FRAMEWORK/src/_etc/scripts/yaml_reader.sh

## create download dir
mkdir -p $DACHCOM_BUNDLE_TEST_DIR/_data/downloads

## release parameters.yml
cp $DACHCOM_BUNDLE_ROOT_DIR/app/config/parameters.example.yml $DACHCOM_BUNDLE_ROOT_DIR/app/config/parameters.yml

eval "$(parse_yaml $DACHCOM_BUNDLE_TEST_DIR/_etc/config.yml)"

NODE='setup_files'
for CURRENT_CONFIG_NODE in ${__}; do
  if [ $CURRENT_CONFIG_NODE != $NODE ]; then continue; fi

  SECTIONS="${CURRENT_CONFIG_NODE}__"
  for FILE in ${!SECTIONS}; do

    FILE_PATH=${FILE}_path
    FILE_DEST=${FILE}_dest
    realK="$(echo ${!FILE_PATH})"
    realV="$(echo ${!FILE_DEST})"

    cp "$DACHCOM_BUNDLE_TEST_DIR/_etc/config/$realK" "$DACHCOM_BUNDLE_ROOT_DIR/$realV"

  done
done
