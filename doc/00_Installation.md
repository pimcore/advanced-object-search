# Installation of Advanced Object Search

:::info

This bundle is only supported on Pimcore Core Framework 11.

This bundle requires minimum version of OpenSearch 2.7. or Elasticsearch 8.0.0.

:::

## Installation

To install Advanced Object Search Bundle, follow the three steps below:

1. Install the required dependencies:
```bash
composer require pimcore/advanced-object-search
```

2. Make sure the bundle is enabled in the `config/bundles.php` file. The following lines should be added:

```php

return [
    // ...
    AdvancedObjectSearchBundle\AdvancedObjectSearchBundle::class => ['all' => true],
    // ...
];
```

3. Install the bundle:

```bash
bin/console pimcore:bundle:install AdvancedObjectSearchBundle
```

## Required Backend User Permission
To access the Advanced Object Search feature, a user needs to meet at least one of the following criteria:
* Be an `admin` user.
* Have `bundle_advancedsearch_search` permission.