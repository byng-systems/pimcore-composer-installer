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
            "Byng\\Composer\\PimcoreInstaller::install",
            "Byng\\Composer\\PimcoreInstaller::installIndex",
            "Byng\\Composer\\PimcoreInstaller::installPlugins",
            "Byng\\Composer\\PimcoreInstaller::installWebsite",
            "Byng\\Composer\\PimcoreInstaller::installHtAccessFile"
        ],
        "post-update-cmd": [
            "Byng\\Composer\\PimcoreInstaller::install",
            "Byng\\Composer\\PimcoreInstaller::installIndex",
            "Byng\\Composer\\PimcoreInstaller::installPlugins",
            "Byng\\Composer\\PimcoreInstaller::installWebsite",
            "Byng\\Composer\\PimcoreInstaller::installHtAccessFile"
        ]
    }
}
```
