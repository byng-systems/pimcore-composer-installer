Pimcore Composer Installer
==========================

Installs a given version of Pimcore to your configured `document-root-path` via Composer. The main
goal of this package is to make it easier to ramp developers up onto a Pimcore project, and make it
easier to set up Pimcore projects. It achieves this by ensuring that you don't have to commit 
Pimcore to your project's repository, and automates the task of downloading, extracting, and placing
the Pimcore files in your project.

## Usage:

Two config entries are required in your project's composer.json file:

* `document-root-path`: This is the Pimcore web-root, i.e. where index.php will be, and where files
will be served from.
* `pimcore-version`: This is the exact Pimcore version that you want to use. This cannot be part of
your project's required dependenies because the version downloaded by Composer would clash with the
Pimcore autoloader.

You must then also specify the installer as `post-install-cmd` and `post-update-cmd` scripts.

For example:

```
{
    ...
    "config": {
        "document-root-path": "./web",
        "pimcore-version": "3.1.1"
    },
    "require": {
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
tailor the installation to your project's needs. If you are planning on using these methods, make
sure you first always called the `download` method, all of the install methods will need it to have
been called first by Composer.

Note: This plugin will overwrite some files without warning. If you have modified default Pimcore 
code then you will more than likely lose it. We recommend extending the Pimcore code to augment the
behaviour (this also makes it easier to upgrade Pimcore in the future!)

## License

MIT
