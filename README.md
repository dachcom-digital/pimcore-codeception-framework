# Pimcore Codeception Framework
This Package allows you to create fast and simple testing environments. 
It's also used by all pimcore Bundles created by [DACHCOM.DIGITAL](https://github.com/dachcom-digital?q=pimcore-).

### Support Table

| Branch  | Supported Pimcore Versions | Supported Symfony Versions |
|---------|----------------------------|----------------------------|
| **3.0** | `11.0`                     | `^6.2`                     |
| **2.0** | `10.1`- `10.6`             | `^5.4`                     |
| **1.0** | `6.6` - `6.9`              | `^4.4`, `^3.4`             |

### Upgrade Notes
#### 3.0.0
- Yaml extension changed from `yml` to `yaml`
- `_support` path has changed to `Support`. All `Dachcom\Codeception\*` namespaces have changed to `Dachcom\Codeception\Support\*`
- Using rsync for `setup_files` so you need to adjust your `setup_files` stack accordingly (see example below)
- You need to define PIMCORE `config_location` in your ci configuration (See section "Configuration Location" below)
- You have to add additional PIMCORE Bundles to the `bundles` config section (see example below)
- [SECURITY] `\Pimcore\Bundle\AdminBundle\EventListener\CsrfProtectionListener` is disabled while executing tests
- `Pimcore\Model\DataObject` namespace changed from `_output/var/classes/DataObject/DataObject` to `_output/var/classes/DataObject`
- TestKernel Class can be defined via `TEST_KERNEL_CLASS` env var

***

## Configuration
All test files need to be stored in `/tests`.

### Environment Variables

| Name                            | Example                                                 | Required | Description                                                                    |
|---------------------------------|---------------------------------------------------------|----------|--------------------------------------------------------------------------------|
| `TEST_BUNDLE_NAME`              | `ToolboxBundle`                                         | yes      | --                                                                             |
| `TEST_BUNDLE_INSTALLER_CLASS`   | `ToolboxBundle\\Tool\\Install`                          | yes      | Set to `false` if you don't have any installer class                           |
| `TEST_BUNDLE_TEST_DIR`          | `${{ github.workspace }}/tests`                         | yes      | --                                                                             |
| `TEST_PROJECT_ROOT_DIR`         | `${{ github.workspace }}`                               | yes      | This variable is required to setup test structure before any system is running |
| `PIMCORE_CODECEPTION_FRAMEWORK` | `${{ github.workspace }}/pimcore-codeception-framework` | yes      | --                                                                             |
| `PIMCORE_CODECEPTION_VERSION`   | `2.0`, `1.0`                                            | yes      | --                                                                             |

## Bootstrap
Create a file called `_bootstrap.php` in `tests/_bootstrap.php`

```php
<?php

$frameworkPath = getenv('PIMCORE_CODECEPTION_FRAMEWORK');
$bundleTestPath = getenv('TEST_BUNDLE_TEST_DIR');

$bootstrap = sprintf('%s/src/_bootstrap.php', $frameworkPath);

include_once $bootstrap;
```

## Setup File
Create a file called `config.yaml` in `tests/_etc/config.yaml`.

```yaml
bundles:
    - { namespace: \MyTestBundle\MyTestBundle }
    - { namespace: \Pimcore\Bundle\SeoBundle\PimcoreSeoBundle, priority: 0, execute_installer: true } # if you need the seo bundle
setup_files:
    - { path: app/config.yaml, dest: ./config/ }
    - { path: app/system_settings.yaml, dest: ./var/config/system_settings/ }
    - { path: app/controller/DefaultController.php, dest: ./src/Controller/ }
    - { path: app/templates/default.html.twig, dest: ./templates/default/ }
    - { path: app/templates/snippet.html.twig, dest: ./templates/default/ }
preload_files:
    - { path: Services/MySpecialTestService.php }
additional_composer_packages:
    - { package: pimcore/admin-ui-classic-bundle, version: ^1.0 } # this one is most likely needed
    - { package: vendor/foo-bar, version: ^1.0}                   # additional packages
```
### Configuration Location
In your `app/config.yaml` you should move all config locations to `settings-store`,
see pimcore ci [example](https://github.com/pimcore/pimcore/blob/11.x/.github/ci/files/config/config.yaml)

> Attention! Do not set `system_settings` to `settings-store`!

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
called `default_config.yaml` and store it in `/tests/_etc/config/bundle`.

### Using Bundle Configuration Files
TBD

## Classes
If you want to provide some classes to install, all the definitions need to stored at `/tests/_etc/classes`.

***

## Actors

### [MODULE] PimcoreAdminCsv

| Name                                                     | Description |
|----------------------------------------------------------|-------------|
| `$I->seeResponseCsvHeaderHasValues(array $headerValues)` |             |
| `$I->seeResponseCsvRowValues(int $index, array $values)` |             |
| `$I->seeResponseCsvLength(int $length)`                  |             |
| `$I->seeResponseIsCsv()`                                 |             |

### [MODULE] PimcoreAdminJson

| Name                                            | Description |
|-------------------------------------------------|-------------|
| `$I->seeResponseContainsJson(array $json = [])` |             |
| `$I->seeResponseIsJson()`                       |             |

### [MODULE] PimcoreBackend

| Name                                                                                                                                   | Description |
|----------------------------------------------------------------------------------------------------------------------------------------|-------------|
| `$I->haveAPageDocument($key = 'bundle-page-test', array $params = [], $locale = null)`                                                 |             |
| `$I->haveASubPageDocument(Document $parent, $key = 'bundle-sub-page-test', array $params = [], $locale = null)`                        |             |
| `$I->haveTwoConnectedDocuments(Document\Page $sourceDocument, Document\Page $targetDocument)`                                          |             |
| `$I->haveAUnPublishedDocument(Document $document)`                                                                                     |             |
| `$I->moveDocument(Document $document, Document $parentDocument)`                                                                       |             |
| `$I->haveASnippet($key = 'bundle-snippet-test', $params = [], $locale = null)`                                                         |             |
| `$I->haveAEmail($key = 'bundle-email-test', array $params = [], $locale = null)`                                                       |             |
| `$I->haveALink(Document\Page $source, $key = 'bundle-link-test', array $params = [], $locale = null)`                                  |             |
| `$I->haveASubLink(Document $parent, Document\Page $source, $key = 'bundle-sub-link-test', array $params = [], $locale = null)`         |             |
| `$I->haveAHardLink(Document\Page $source, $key = 'bundle-hardlink-test', array $params = [], $locale = null)`                          |             |
| `$I->haveASubHardLink(Document $parent, Document\Page $source, $key = 'bundle-sub-hardlink-test', array $params = [], $locale = null)` |             |
| `$I->haveAPimcoreObject(string $objectType, $key = 'bundle-object-test', array $params = [])`                                          |             |
| `$I->haveASubPimcoreObject(DataObject $parent, string $objectType, $key = 'bundle-sub-object-test', array $params = [])`               |             |
| `$I->refreshObject(DataObject $object)`                                                                                                |             |
| `$I->moveObject(DataObject $object, DataObject $parentObject)`                                                                         |             |
| `$I->copyObject(DataObject $object, DataObject $targetObject)`                                                                         |             |
| `$I->createNewObjectVersion(DataObject\Concrete $object)`                                                                              |             |
| `$I->deleteObjectVersion(Version $version)`                                                                                            |             |
| `$I->publishObjectVersion(Version $version)`                                                                                           |             |
| `$I->moveObjectToRecycleBin(DataObject $object)`                                                                                       |             |
| `$I->restoreObjectFromRecycleBin(DataObject $object, Item $item)`                                                                      |             |
| `$I->haveAPimcoreObjectFolder($key = 'bundle-object-folder-test', array $params = [])`                                                 |             |
| `$I->haveAPimcoreAsset($key = 'bundle-asset-test', array $params = [])`                                                                |             |
| `$I->haveASubPimcoreAsset(Asset\Folder $parent, $key = 'bundle-sub-asset-test', array $params = [])`                                   |             |
| `$I->haveAPimcoreAssetFolder($key = 'bundle-asset-folder-test', array $params = [])`                                                   |             |
| `$I->haveASubPimcoreAssetFolder(Asset\Folder $parent, $key = 'bundle-asset-sub-folder-test', array $params = [])`                      |             |
| `$I->moveAsset(Asset $asset, Asset $parentAsset)`                                                                                      |             |
| `$I->haveADummyFile($fileName, $fileSizeInMb = 1)`                                                                                     |             |
| `$I->haveASite($siteKey, array $params = [], $locale = null, $add3w = false, $additionalDomains = [], array $errorDocuments = [])`     |             |
| `$I->haveAPageDocumentForSite(Site $site, $key = 'document-test', array $params = [], $locale = null)`                                 |             |
| `$I->haveAHardlinkForSite(Site $site, Document\Page $document, $key = 'hardlink-test', array $params = [], $locale = null)`            |             |
| `$I->seeDownload($fileName)`                                                                                                           |             |
| `$I->seeEditablesPlacedOnDocument(Document $document, array $editables)`                                                               |             |
| `$I->seeAnAreaElementPlacedOnDocument(Document $document, string $areaName, array $editables = [])`                                    |             |
| `$I->seeEmailIsSent(Document\Email $email)`                                                                                            |             |
| `$I->seeEmailIsNotSent(Document\Email $email)`                                                                                         |             |
| `$I->seePropertiesInEmail(Document\Email $mail, array $properties)`                                                                    |             |
| `$I->seePropertyKeysInEmail(Document\Email $mail, array $properties)`                                                                  |             |
| `$I->cantSeePropertyKeysInEmail(Document\Email $mail, array $properties)`                                                              |             |
| `$I->seeInRenderedEmailBody(Document\Email $mail, string $string)`                                                                     |             |
| `$I->seeKeyInFrontendTranslations(string $key)`                                                                                        |             |
| `$I->haveAFrontendTranslatedKey(string $key, string $translation, string $language)`                                                   |             |
| `$I->haveAStaticRoute(string $name = 'test_route', array $params = [])`                                                                |             |
| `$I->haveAPimcoreRedirect(array $data)`                                                                                                |             |
| `$I->haveAPimcoreClass(string $name = 'TestClass')`                                                                                    |             |
| `$I->submitDocumentToXliffExporter(Document $document)`                                                                                |             |

### [MODULE] PimcoreBundleCore

| Name                                                                     | Description |
|--------------------------------------------------------------------------|-------------|
| This module installs a bundle if `run_installer` option is set to `true` |             |

### [MODULE] PimcoreCore

| Name                                                                        | Description |
|-----------------------------------------------------------------------------|-------------|
| `$I->haveABootedSymfonyConfiguration(string $configuration)`                |             |
| `$I->haveAKernelWithoutDebugMode()`                                         |             |
| `$I->seeException(string $exception, ?string $message, \Closure $callback)` |             |

### [MODULE] PimcoreUser

| Name                                      | Description |
|-------------------------------------------|-------------|
| `$I->haveAUser($username)`                |             |
| `$I->haveAUserWithAdminRights($username)` |             |

***

### [MODULE] Browser/PhpBrowser

| Name                                                                                                                        | Description |
|-----------------------------------------------------------------------------------------------------------------------------|-------------|
| `$I->haveReplacedPimcoreRuntimeConfigurationNode(array $overrideConfig)`                                                    |             |
| `$I->amOnPageInEditMode(string $page)`                                                                                      |             |
| `$I->amOnPageWithLocale(string $url, null string array $locale)`                                                            |             |
| `$I->amOnPageWithLocaleAndCountry(string $url, ?string $locale, string $country)`                                           |             |
| `$I->seeDownloadLink(AbstractModel $element, string $link)`                                                                 |             |
| `$I->seeDownloadLinkZip(string $fileName, string $link)`                                                                    |             |
| `$I->amOnStaticRoute(string $routeName, array $args)`                                                                       |             |
| `$I->seeCurrentHostEquals(string $host)`                                                                                    |             |
| `$I->seeAEditableConfiguration(string $name, string $type, ?string $label, array $options, $data = null, $selector = null)` |             |
| `$I->seeEmailIsSentTo(string $recipient, Email $email)`                                                                     |             |
| `$I->seeSentEmailHasPropertyValue(Email $email, string $property, string $value)`                                           |             |
| `$I->seeSentEmailHasHeaderValue(Email $email, string $property, string $value)`                                             |             |
| `$I->seeEmailSubmissionType(string $submissionType, string $type, Email $email)`                                            |             |
| `$I->seeEmptyEmailSubmissionType(string $type, Email $email)`                                                               |             |
| `$I->seeInSubmittedEmailBody(string $string, Email $email)`                                                                 |             |
| `$I->dontSeeInSubmittedEmailBody(string $string, Email $email)`                                                             |             |
| `$I->seeInSubmittedEmailBodyOfType(string $string, string $type, Email $email)`                                             |             |
| `$I->dontSeeInSubmittedEmailBodyOfType(string $string, string $type, Email $email)`                                         |             |
| `$I->amLoggedInAsFrontendUser(?UserInterface $user, string $firewallName)`                                                  |             |
| `$I->amLoggedInAs(string $username)`                                                                                        |             |
| `$I->sendTokenAjaxPostRequest(string $url, array $params = [])`                                                             |             |
| `$I->seeLastRequestIsInPath(string $expectedPath)`                                                                          |             |
| `$I->seeCanonicalLinkInResponse()`                                                                                          |             |
| `$I->dontSeeCanonicalLinkInResponse()`                                                                                      |             |
| `$I->seePimcoreOutputCacheDisabledHeader(string $disabledReasonMessage)`                                                    |             |
| `$I->dontSeePimcoreOutputCacheDisabledHeader()`                                                                             |             |
| `$I->seePimcoreOutputCacheDate()`                                                                                           |             |
| `$I->seeEmptySessionBag(string $bagName)`                                                                                   |             |
| `$I->seePropertiesInLastFragmentRequest(array $properties = [])`                                                            |             |

### [MODULE] Browser/WebDriver

| Name                                                                                                                        | Description |
|-----------------------------------------------------------------------------------------------------------------------------|-------------|
| `$I->amOnPageInEditMode(string $page)`                                                                                      |             |
| `$I->setDownloadPathForWebDriver($path = null)`                                                                             |             |
| `$I->clearWebDriverCache()`                                                                                                 |             |
| `$I->seeAEditableConfiguration(string $name, string $type, ?string $label, array $options, $data = null, $selector = null)` |             |
| `$I->sendWebDriverCommand(array $body)`                                                                                     |             |