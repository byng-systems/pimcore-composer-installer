# pimcore-composer-installer

Installs Pimcore to your configured document-root-path via Composer.

## Example usage:

composer.json

```
{
    "name": "acme/our-pimcore-website",
    "description": "Our cool pimcore site.",
    "config": {
        "document-root-path": "./web"
    },
    "require": {
        "pimcore/pimcore": "^3.1",
        "byng/pimcore-composer-installer": "^1.0"
    },
    "scripts": {
        "post-install-cmd": [
            "Byng\\Pimcore\\Composer\\Installer::install",
            "Byng\\Pimcore\\Composer\\Installer::installIndex",
            "Byng\\Pimcore\\Composer\\Installer::installPlugins",
            "Byng\\Pimcore\\Composer\\Installer::installWebsite",
            "Byng\\Pimcore\\Composer\\Installer::installHtAccessFile"
        ],
        "post-update-cmd": [
            "Byng\\Pimcore\\Composer\\Installer::install",
            "Byng\\Pimcore\\Composer\\Installer::installIndex",
            "Byng\\Pimcore\\Composer\\Installer::installPlugins",
            "Byng\\Pimcore\\Composer\\Installer::installWebsite",
            "Byng\\Pimcore\\Composer\\Installer::installHtAccessFile"
        ]
    }
}
```
