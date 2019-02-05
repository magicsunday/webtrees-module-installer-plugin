# webtrees-module-installer-plugin
A composer plugin installer to install [webtrees](https://www.webtrees.net) modules directly to the ``modules_v4`` directory.

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

``` 
composer require your-vendor-name/your-package-name
```

To install a specific branch use:

``` 
composer require your-vendor-name/your-package-name:branch-name
``` 

For instance ``dev-master``.

If your package is not listed on packagist you may try to load it via:

```
composer config repositories.your-repo-name vcs https://github.com/your-vendor-name/your-package-name
composer require your-vendor-name/your-package-name[:optional branch name]
```

