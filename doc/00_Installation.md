# Installation

## Installation Process
### For Pimcore >= 10.5
To install Advanced Object Search Bundle for Pimcore 10.5 or higher, follow the three steps below:

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

### For Older Versions
To install the Advanced Object Search Bundle for older versions of Pimcore, please run the following commands instead:

```bash 
composer require pimcore/advanced-object-search
bin/console pimcore:bundle:enable AdvancedObjectSearchBundle
bin/console pimcore:bundle:install AdvancedObjectSearchBundle
```

## Required Backend User Permission
To access Advanced Object Search feature, user needs to meet one of following criteria:
* be an `admin` or
* have `bundle_advancedsearch_search` permission