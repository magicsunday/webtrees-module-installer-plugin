[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://opensource.org/licenses/MIT)

[![PHPStan](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/phpstan.yml/badge.svg)](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/phpstan.yml)
[![PHP_CodeSniffer](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/phpcs.yml/badge.svg)](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/phpcs.yml)

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/magicsunday/webtrees-module-installer-plugin/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/magicsunday/webtrees-module-installer-plugin/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/magicsunday/webtrees-module-installer-plugin/badges/build.png?b=master)](https://scrutinizer-ci.com/g/magicsunday/webtrees-module-installer-plugin/build-status/master)
[![Code Climate](https://codeclimate.com/github/magicsunday/webtrees-module-installer-plugin/badges/gpa.svg)](https://codeclimate.com/github/magicsunday/webtrees-module-installer-plugin)

# webtrees-module-installer-plugin
A composer plugin installer to install [webtrees](https://www.webtrees.net) modules directly to the ``modules_v4`` directory.

## Requirements

### System Requirements

PHP 7.4+ or PHP 8.0+

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
vendor/bin/phpcs src/ --standard=PSR12
vendor/bin/phpstan analyse -c phpstan.neon
```
