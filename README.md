Pimcore Composer Installer
==========================

Installs Pimcore to your configured `document-root-path` via Composer.

## Example usage:

```
{
    ...
    "config": {
        "document-root-path": "./web"
    },
    "require": {
        "pimcore/pimcore": "^3.1",
        "byng/pimcore-composer-installer": "^1.0"
    },
    "scripts": {
        "post-install-cmd": [
            "Byng\\Pimcore\\Composer\\Installer::install"
        ],
        "post-update-cmd": [
            "Byng\\Pimcore\\Composer\\Installer::install"
        ]
    },
    ...
}
```

The `Installer` class has methods for installing individual components, this makes it very easy to
tailor the installation to your project's needs.

Note: This plugin will overwrite some files without warning. If you have modified default Pimcore 
code then you will more than likely lose it. We recommend extending the Pimcore code to augment the
behaviour (this also makes it easier to upgrade Pimcore in the future!)

## License

MIT
