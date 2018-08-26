# webtrees-module-installer-plugin
A composer plugin installer to install webtrees modules directly to the ``modules_v3`` directory.

## Usage
To install a new webtrees module with composer, just add this module to the ``require`` section
of your ``composer.json`` file.

```
"require": {
    "magicsunday/webtrees-module-installer-plugin": "^1"
},
```

Afterwards you can install your webtrees module with the following command from the root directory of
your webtrees installation.

``` 
composer require your-vendor-name/your-package-name
```
