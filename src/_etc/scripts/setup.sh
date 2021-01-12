#!/usr/bin/env bash

set -e

source $PIMCORE_CODECEPTION_FRAMEWORK/src/_etc/scripts/yaml_reader.sh

## create download dir
mkdir -p $TEST_BUNDLE_TEST_DIR/_data/downloads

## release empty bundle config
touch $TEST_BUNDLE_TEST_DIR/_data/config/config.yml

## release parameters.yml
cp $TEST_PROJECT_ROOT_DIR/app/config/parameters.example.yml $TEST_PROJECT_ROOT_DIR/app/config/parameters.yml

## move TestKernel
cp $PIMCORE_CODECEPTION_FRAMEWORK/src/_support/App/TestKernel.php $TEST_PROJECT_ROOT_DIR/app/TestKernel.php

## Register test variables in .env
echo "PIMCORE_KERNEL_CLASS=TestKernel" >> $TEST_PROJECT_ROOT_DIR/.env
echo "PIMCORE_CODECEPTION_FRAMEWORK=$PIMCORE_CODECEPTION_FRAMEWORK" >> $TEST_PROJECT_ROOT_DIR/.env
echo "TEST_BUNDLE_TEST_DIR=$TEST_BUNDLE_TEST_DIR" >> $TEST_PROJECT_ROOT_DIR/.env

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
