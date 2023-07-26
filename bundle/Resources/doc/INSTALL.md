# Installation

## Requirements

* Ibexa 4.x
* PHP 8.1+

## Installation steps

Run `composer require ezsystems/ezplatform-automated-translation` to install the bundle and its dependencies:

### Change bundle's position in the configuration

The new bundle is automatically enabled in the configuration thanks to Flex. Even though, it's important and required to move `EzSystems\EzPlatformAutomatedTranslationBundle\EzPlatformAutomatedTranslationBundle::class => ['all' => true]` before `EzSystems\EzPlatformAdminUiBundle\EzPlatformAdminUiBundle::class => ['all' => true],` due to the templates loading order.

```php
<?php

return [
    ...
    EzSystems\EzPlatformAutomatedTranslationBundle\EzPlatformAutomatedTranslationBundle::class => ['all' => true],
    EzSystems\EzPlatformAdminUiBundle\EzPlatformAdminUiBundle::class => ['all' => true],
    ...
];
```
Run
```cmd
php bin/console doctrine:schema:update --force
```
