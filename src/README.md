# Pimcore Codeception Framework

This Packages allows you to create fast and simple testing environments. 
It's also used by all pimcore Bundles created by [DACHCOM.DIGITAL](https://github.com/dachcom-digital).

## Configuration
All test files needs to be stored in `/tests`.

### Environment Variables

| Name | Example    | Required   | Description |
|------|------------|------------|-------------|
| `TEST_BUNDLE_NAME` | `ToolboxBundle` | yes | -- |
| `TEST_BUNDLE_NAMESPACE` | `ToolboxBundle\ToolboxBundle` | yes | -- |
| `TEST_BUNDLE_INSTALLER_CLASS` | `ToolboxBundle\Tool\Install` | yes | Set to `false` if you don't have any installer class |
| `TEST_BUNDLE_TEST_DIR` | `${{ github.workspace }}/tests` | yes | -- |
| `TEST_PROJECT_ROOT_DIR` | `${{ github.workspace }}` | yes | This variable is required to setup test structure before any system is running |
| `PIMCORE_CODECEPTION_FRAMEWORK` | `${{ github.workspace }}/pimcore-codeception-framework` | yes | -- |
| `PIMCORE_CODECEPTION_VERSION` | `master`, `^1.0` | yes | -- |

## Bootstrap
Creat a file called `_bootstrap.php` in `tests/_bootstrap.php`

```php
<?php

$frameworkPath = getenv('PIMCORE_CODECEPTION_FRAMEWORK');
$bundleTestPath = getenv('TEST_BUNDLE_TEST_DIR');

$bootstrap = sprintf('%s/src/_bootstrap.php', $frameworkPath);

include_once $bootstrap;
```

## Setup File
Create a file called `config.yml` in `tests/_etc/config.yml`.

> ! Files in `setup_files` node needs to stored in `/tests/_etc/config`.

```yaml
setup_files:
    - { path: app/config.yml, dest: ./app/config/config.yml }
    - { path: app/system.yml, dest: ./var/config/system.yml }
    - { path: app/controller/DefaultController.php, dest: ./src/AppBundle/Controller/DefaultController.php }
    - { path: app/views/default.html.twig, dest: ./app/Resources/views/Default/default.html.twig }
    - { path: app/views/snippet.html.twig, dest: ./app/Resources/views/Default/snippet.html.twig }
```

## Bundle Configuration Files
This Framework allows you to use multiple (bundle) configuration setups.

TBD