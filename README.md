# Pimcore Codeception Framework

This Packages allows you to create fast and simple testing environments. It's also used by all pimcore Bundles created
by [DACHCOM.DIGITAL](https://github.com/dachcom-digital?q=pimcore-).

## Configuration

All test files need to be stored in `/tests`.

### Environment Variables

| Name | Example    | Required   | Description |
|------|------------|------------|-------------|
| `TEST_BUNDLE_NAME` | `ToolboxBundle` | yes | -- |
| `TEST_BUNDLE_INSTALLER_CLASS` | `ToolboxBundle\\Tool\\Install` | yes | Set to `false` if you don't have any installer class |
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

```yaml
bundles:
    - { namespace: \MyTestBundle\MyTestBundle }
setup_files:
    - { path: app/config.yml, dest: ./app/config/config.yml }
    - { path: app/system.yml, dest: ./var/config/system.yml }
    - { path: app/controller/DefaultController.php, dest: ./src/AppBundle/Controller/DefaultController.php }
    - { path: app/views/default.html.twig, dest: ./app/Resources/views/Default/default.html.twig }
    - { path: app/views/snippet.html.twig, dest: ./app/Resources/views/Default/snippet.html.twig }
preload_files:
    - { path: Services/MySpecialTestService.php }
additional_composer_packages:
    - { package: vendor/foo-bar:^1.0 }
```

### Setup File Parameters

- **bundles** _(required)_: At least your test bundle should be registered here. Add more, if needed
- **setup_files** _(optional)_: All your template files which should to be available during test cycles. These files need to be
  stored under `/tests/_etc/config`
- **preload_files** _(optional)_: These files will be included at kernel setup. Since these files are not included via composer
  autoload, we need to define them here
- **additional_composer_packages** _(optional)_: Install additional composer packages which are not available in root
  composer.json

## Bundle Configuration Files

This Framework allows you to use multiple (bundle) configuration setups. You need to add at least one default config file
called `default_config.yml` and store it in `/tests/_etc/config/bundle`.

### Using Bundle Configuration Files

TBD

## Classes

If you want to provide some classes to install, all the definitions need to stored at `/tests/_etc/classes`.

***

## Actors

### [MODULE] PimcoreAdminCsv

| Name                                  | Description        |
|---------------------------------------|--------------------|
| `$I->seeResponseCsvHeaderHasValues(array $headerValues)` |     |
| `$I->seeResponseCsvRowValues(int $index, array $values)` | |
| `$I->seeResponseCsvLength(int $length)` | |
| `$I->seeResponseIsCsv()` | |

### [MODULE] PimcoreAdminJson

| Name                                  | Description        |
|---------------------------------------|--------------------|
| `$I->seeResponseContainsJson(array $json = [])` | |
| `$I->seeResponseIsJson()` | |

### [MODULE] PimcoreBackend

| Name                                  | Description        |
|---------------------------------------|--------------------|
| `$I->haveAPageDocument($key = 'bundle-page-test', array $params = [], $locale = null)` | |
| `$I->haveASubPageDocument(Document $parent, $key = 'bundle-sub-page-test', array $params = [], $locale = null)` | |
| `$I->haveTwoConnectedDocuments(Document\Page $sourceDocument, Document\Page $targetDocument)` | |
| `$I->haveAUnPublishedDocument(Document $document)` | |
| `$I->moveDocument(Document $document, Document $parentDocument)` | |
| `$I->haveASnippet($key = 'bundle-snippet-test', $params = [], $locale = null)` | |
| `$I->haveAEmail($key = 'bundle-email-test', array $params = [], $locale = null)` | |
| `$I->haveALink(Document\Page $source, $key = 'bundle-link-test', array $params = [], $locale = null)` | |
| `$I->haveASubLink(Document $parent, Document\Page $source, $key = 'bundle-sub-link-test', array $params = [], $locale = null)` | |
| `$I->haveAHardLink(Document\Page $source, $key = 'bundle-hardlink-test', array $params = [], $locale = null)` | |
| `$I->haveASubHardLink(Document $parent, Document\Page $source, $key = 'bundle-sub-hardlink-test', array $params = [], $locale = null)` | |
| `$I->haveAPimcoreObject(string $objectType, $key = 'bundle-object-test', array $params = [])` | |
| `$I->haveASubPimcoreObject(DataObject $parent, string $objectType, $key = 'bundle-sub-object-test', array $params = [])`
| `$I->moveObject(DataObject $object, DataObject $parentObject)` | |
| `$I->copyObject(DataObject $object, DataObject $targetObject)` | |
| `$I->haveAPimcoreObjectFolder($key = 'bundle-object-folder-test', array $params = [])` | |
| `$I->haveAPimcoreAsset($key = 'bundle-asset-test', array $params = [])` | |
| `$I->haveASubPimcoreAsset(Asset\Folder $parent, $key = 'bundle-sub-asset-test', array $params = [])` | |
| `$I->haveAPimcoreAssetFolder($key = 'bundle-asset-folder-test', array $params = [])` | |
| `$I->haveASubPimcoreAssetFolder(Asset\Folder $parent, $key = 'bundle-asset-sub-folder-test', array $params = [])` | |
| `$I->moveAsset(Asset $asset, Asset $parentAsset)` | |
| `$I->haveADummyFile($fileName, $fileSizeInMb = 1)` | |
| `$I->haveASite($siteKey, array $params = [], $locale = null, $add3w = false, $additionalDomains = [])` | |
| `$I->haveAPageDocumentForSite(Site $site, $key = 'document-test', array $params = [], $locale = null)` | |
| `$I->haveAHardlinkForSite(Site $site, Document\Page $document, $key = 'hardlink-test', array $params = [], $locale = null)` | |
| `$I->seeDownload($fileName)` | |
| `$I->seeEditablesPlacedOnDocument(Document $document, array $editables)` | |
| `$I->seeAnAreaElementPlacedOnDocument(Document $document, string $areaName, array $editables = [])` | |
| `$I->seeEmailIsSent(Document\Email $email)` | |
| `$I->seeEmailIsNotSent(Document\Email $email)` | |
| `$I->seePropertiesInEmail(Document\Email $mail, array $properties)` | |
| `$I->seePropertyKeysInEmail(Document\Email $mail, array $properties)` | |
| `$I->cantSeePropertyKeysInEmail(Document\Email $mail, array $properties)` | |
| `$I->seeInRenderedEmailBody(Document\Email $mail, string $string)` | |
| `$I->seeKeyInFrontendTranslations(string $key)` | |
| `$I->haveAFrontendTranslatedKey(string $key, string $translation, string $language)` | |
| `$I->haveAStaticRoute(string $name = 'test_route', array $params = [])` | |
| `$I->haveAPimcoreRedirect(array $data)` | |
| `$I->haveAPimcoreClass(string $name = 'TestClass')` | |
| `$I->submitDocumentToXliffExporter(Document $document)` | |

### [MODULE] PimcoreBundleCore

| Name                                  | Description        |
|---------------------------------------|--------------------|
| This module installs a bundle if `run_installer` option is set to `true` | |

### [MODULE] PimcoreCore

| Name                                  | Description        |
|---------------------------------------|--------------------|
| `$I->haveABootedSymfonyConfiguration(string $configuration)` | |

### [MODULE] PimcoreRest

| Name                                  | Description        |
|---------------------------------------|--------------------|
| -- | |

### [MODULE] PimcoreUser

| Name                                  | Description        |
|---------------------------------------|--------------------|
| `$I->haveAUser($username)` | |
| `$I->haveAUserWithAdminRights($username)` | |

### [MODULE] Unit

| Name                                  | Description        |
|---------------------------------------|--------------------|
| -- | |

## API

TBD