# Webtrees Module Installer Plugin

[![Latest version](https://img.shields.io/github/v/release/magicsunday/webtrees-module-installer-plugin?sort=semver)](https://github.com/magicsunday/webtrees-module-installer-plugin/releases/latest)
[![License](https://img.shields.io/github/license/magicsunday/webtrees-module-installer-plugin)](https://github.com/magicsunday/webtrees-module-installer-plugin/blob/main/LICENSE)
[![CI](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/magicsunday/webtrees-module-installer-plugin/actions/workflows/ci.yml)

A powerful Composer plugin that simplifies the installation of modules for the [webtrees](https://www.webtrees.net) genealogy application by automatically placing them in the correct directory structure.

### 🚀 Seamless Integration
Automatically installs webtrees modules to the correct <code>modules_v4</code> directory without manual file copying.

### ⚙️ Easy Configuration
Simple setup with minimal configuration required in your composer.json file.

## 🌟 Why Use This Plugin?
When developing or using modules for webtrees, managing the installation process can be cumbersome. This plugin solves that problem by:

- Automatically detecting and installing modules with the `webtrees-module` type
- Placing modules in the correct `modules_v4` directory structure
- Supporting both direct installation and installation via a separate composer.json
- Eliminating the need for manual file copying or symlink creation

## 📋 Requirements
### System Requirements
- PHP 8.2 or higher (compatible up to PHP 8.4)
- Composer 2.6 or higher

## 🔧 Installation
Add this plugin to the `require` or `require-dev` section of your `composer.json` file:

```json
"require": {
    "magicsunday/webtrees-module-installer-plugin": "^1.6"
},
```

Or install it using Composer:

```bash
composer require magicsunday/webtrees-module-installer-plugin
```

Make sure to allow the plugin in your composer.json:

```json
"config": {
    "allow-plugins": {
        "magicsunday/webtrees-module-installer-plugin": true
    }
}
```

## 📦 Usage
### For Module Users
To install a webtrees module with composer, simply require the module in your composer.json:

```bash
composer require vendor-name/module-name
```

The plugin will automatically install the module to the `modules_v4` directory.

### For Module Developers
When creating a webtrees module, set the package type to `webtrees-module` in your module's composer.json:

```json
{
    "name": "your-vendor-name/your-module-name",
    "description": "Your module description",
    "type": "webtrees-module",
    "require": {
        "php": ">=8.2.0"
    }
}
```

#### Pro Tip
The module name in the composer.json file will determine the directory name in the `modules_v4` directory.

### Installing from GitHub
If your module is not listed on Packagist, you can install it directly from GitHub:

```bash
composer config repositories.your-repo-name vcs https://github.com/your-vendor-name/your-module-name
composer require your-vendor-name/your-module-name[:optional-branch-name]
```

For example, to install the dev-master branch:

```bash
composer require your-vendor-name/your-module-name:dev-master
```

## 🧪 Testing
The plugin includes several testing tools to ensure code quality:

```bash
# Run all tests
composer ci:test

# Run specific tests
composer ci:test:php:phpstan  # Static analysis
composer ci:test:php:lint     # PHP linting
composer ci:test:php:rector   # Code quality checks
composer ci:test:php:cgl      # Coding guidelines
```

## 🔍 How It Works
The plugin works by:

1. Registering a custom installer with Composer's installation manager
2. Detecting packages with the `webtrees-module` type
3. Determining the correct installation path in the `modules_v4` directory
4. Handling both direct installation and installation via a separate composer.json

The main components are:
- `ModuleInstallerPlugin`: Implements Composer's PluginInterface
- `ModuleInstaller`: Extends Composer's LibraryInstaller to handle module installation
- `Config`: Manages configuration settings and path resolution

## 👥 Contributing
Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please make sure your code follows the project's coding standards by running the tests before submitting.

## 📄 License
This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgements
- [webtrees](https://www.webtrees.net) - The open source web genealogy application
- [Composer](https://getcomposer.org/) - Dependency Manager for PHP
