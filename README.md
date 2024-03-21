[![Latest version](https://img.shields.io/github/v/release/magicsunday/webtrees-module-installer-plugin?sort=semver)](https://github.com/magicsunday/webtrees-module-installer-plugin/releases/latest)
[![License](https://img.shields.io/github/license/magicsunday/webtrees-module-installer-plugin)](https://github.com/magicsunday/webtrees-module-installer-plugin/blob/main/LICENSE)
[![CI](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/ci.yml)

# webtrees-module-installer-plugin
A composer plugin installer to install [webtrees](https://www.webtrees.net) modules directly to the ``modules_v4`` directory.

## Requirements

### System Requirements

PHP 8.2+

## Usage
To install a new webtrees module with composer, just add this module to the ``require`` section
of your ``composer.json`` file.

```
"require": {
    "magicsunday/webtrees-module-installer-plugin": "*"
},
```

The module itself must also be of the type ``webtrees-module``.

``` 
"type": "webtrees-module",
``` 

Afterwards you can install your webtrees module with the following command from the root directory of
your webtrees installation if there exists a package at [packagist.org](https://packagist.org).

```shell
composer require your-vendor-name/your-package-name
```

To install a specific branch use:

```shell
composer require your-vendor-name/your-package-name:branch-name
``` 

For instance ``dev-master``.

If your package is not listed on packagist you may try to load it via:

```shell
composer config repositories.your-repo-name vcs https://github.com/your-vendor-name/your-package-name
composer require your-vendor-name/your-package-name[:optional branch name]
```

## Testing
```shell
composer update

composer ci:test
composer ci:test:php:phpstan
composer ci:test:php:lint
composer ci:test:php:unit
composer ci:test:php:rector
```
