<?php

/**
 * This file is part of the pimcore-composer-installer package.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Byng\Pimcore\Composer;

use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Pimcore Installer
 *
 * @author Elliot Wright <elliot@elliotwright.co>
 */
final class Installer
{
    /**
     * @var Filesystem
     */
    private static $fileSystem;


    /**
     * Download Pimcore, and extract it to a location we can copy it's contents from.
     *
     * @param Event $event
     *
     * @return void
     */
    public static function download(Event $event)
    {
        $version = self::preparePimcoreVersion($event);

        $downloadDir = __DIR__ . "/../cache/";
        $downloadPath = $downloadDir . "/pimcore-{$version}.zip";
        $downloadUrl = "https://github.com/pimcore/pimcore/archive/{$version}.zip";

        if (!file_exists($downloadPath)) {
            file_put_contents($downloadPath, fopen($downloadUrl, "r"));
        }

        if (!file_exists($downloadDir . "/pimcore-{$version}")) {
            $archive = new \ZipArchive();

            if ($archive->open($downloadPath)) {
                $archive->extractTo($downloadDir);
                $archive->close();
            } else {
                throw new \RuntimeException(
                    "Unable to extract archive '%s'.",
                    realpath($downloadPath)
                );
            }
        }
    }

    /**
     * Install everything with sensible defaults. See each install method for more information.
     *
     * @param Event $event
     *
     * @return void
     */
    public static function install(Event $event)
    {
        if (self::isInstalled($event)) {
            return;
        }
        
        self::download($event);
        self::installHtAccessFile($event);
        self::installIndex($event);
        self::installPimcore($event);
        self::installPlugins($event);
        self::installVendorLink($event);
        self::installWebsite($event);
        self::deleteCache($event);
    }

    /**
     * Checks if Pimcore is already installed and is the same version as requested.
     * 
     * @param Event $event
     * 
     * @return boolean
     */
    public static function isInstalled(Event $event)
    {
        $version = self::preparePimcoreVersion($event);
        list($installPath, $pimcorePath) = self::prepareBaseDirectories($event);
        $versionFile = $installPath . "/pimcore/lib/Pimcore/Version.php";
        
        if (file_exists($versionFile)) {
            require_once $versionFile;
            if (\Pimcore\Version::$version === $version) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Deleted the cache folder after Pimcore has been installed to prevent IDEs
     * from indexing the Pimcore folder twice.
     *
     * @param Event $event
     *
     * @return void
     */
    public static function deleteCache(Event $event)
    {
        $version = self::preparePimcoreVersion($event);

        $downloadDir = __DIR__ . "/../cache/";
        $downloadPath = $downloadDir . "/pimcore-{$version}/";

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $downloadPath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $command = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $command($fileinfo->getRealPath());
        }

        rmdir($downloadPath);
    }

    /**
     * Copy Pimcore index.php to the 'document-root-path', or a sensible default
     *
     * @param Event $event
     *
     * @return void
     */
    public static function installIndex(Event $event)
    {
        list($installPath, $pimcorePath) = self::prepareBaseDirectories($event);

        $from = $pimcorePath . "/index.php";
        $to = $installPath . "/index.php";

        if (!file_exists($from)) {
            // unknown .htaccess file location within pimcore
            return;
        }

        copy($from, $to);
    }

    /**
     * Copy Pimcore to the 'document-root-path', or a sensible default
     *
     * @param Event $event
     *
     * @return void
     */
    public static function installPimcore(Event $event)
    {
        list($installPath, $pimcorePath) = self::prepareBaseDirectories($event);

        $from = $pimcorePath . "/pimcore";
        $to = $installPath . "/pimcore";

        self::deleteFolder($to);
        self::copyFolder($from, $to);
    }

    /**
     * Copy the Pimcore skeleton plugins folder to the 'document-root-path', or a sensible default
     *
     * @param Event $event
     *
     * @return void
     */
    public static function installPlugins(Event $event)
    {
        list($installPath, $pimcorePath) = self::prepareBaseDirectories($event);

        $from = $pimcorePath . "/plugins_example";
        $to = $installPath . "/plugins";

        self::copyFolder($from, $to);
    }

    /**
     * Copy the Pimcore skeleton website folder to the 'document-root-path', or a sensible default,
     * if one does not already exist
     *
     * @param Event $event
     *
     * @return void
     */
    public static function installWebsite(Event $event)
    {
        list($installPath, $pimcorePath) = self::prepareBaseDirectories($event);

        $from = $pimcorePath . "/website_example";
        $to = $installPath . "/website";

        self::copyFolder($from, $to);
    }

    /**
     * Copy the Pimcore default .htaccess file to the 'document-root-path' if it does not exist
     *
     * @param Event $event
     *
     * @return void
     */
    public static function installHtAccessFile(Event $event)
    {
        list($installPath, $pimcorePath) = self::prepareBaseDirectories($event);

        $from = $pimcorePath . "/.htaccess";
        $to = $installPath . "/.htaccess";

        if (!file_exists($from)) {
            // unknown .htaccess file location within pimcore
            return;
        }

        copy($from, $to);
    }

    /**
     * Install a symlink to the vendor folder in the webroot for Pimcore.
     *
     * @param Event $event
     *
     * @return void
     */
    public static function installVendorLink(Event $event)
    {
        $vendorPath = $config = $event->getComposer()->getConfig()->get("vendor-dir");

        list($installPath, $pimcorePath) = self::prepareBaseDirectories($event);

        $to = $installPath . "/vendor";
        $link = self::getRelativePath($to, $vendorPath);

        if (!file_exists($to)) {
            symlink($link, $to);
        }
    }

    /**
     * Prepare base directories for copying and installation
     *
     * @param Event $event
     *
     * @return array Tuple, installation path on the left, Pimcore source path on the right.
     */
    private static function prepareBaseDirectories(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $cwd = getcwd();

        $version = self::preparePimcoreVersion($event);

        $installPath = realpath($cwd . DIRECTORY_SEPARATOR . ($config->get("document-root-path") ?: "./www"));
        $pimcorePath = __DIR__ . "/../cache/pimcore-{$version}";

        if (!$installPath || !$pimcorePath) {
            throw new \RuntimeException(
                "Invalid install path, or pimcore path. Note: the directories must exist. " .
                "Install path was '" . $installPath . "'. Pimcore path was '" . $pimcorePath . "'. " .
                "Aborting Pimcore installation. "
            );
        }

        return [
            $installPath,
            $pimcorePath,
        ];
    }

    /**
     * Prepare the Pimcore version value for the installers.
     *
     * @param Event $event
     * @return string
     *
     * @throws \RuntimeException if the `pimcore-version` config value is missing.
     */
    private static function preparePimcoreVersion(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $version = $config->get("pimcore-version");

        if (!$version) {
            throw new \RuntimeException("Missing `pimcore-version` in composer.json.");
        }

        return $version;
    }

    /**
     * Deletes the folder at the given path
     *
     * @param string $folder
     *
     * @return void
     */
    private static function deleteFolder($folder)
    {
        $fs = self::getFilesystem();
        $fs->remove($folder);
    }

    /**
     * Copy a given folder to a given location
     *
     * @param string $from
     * @param string $to
     *
     * @return void
     */
    private static function copyFolder($from, $to)
    {
        $objects = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($objects as $name => $object) {
            $startsAt = substr(dirname($name), strlen($from));
            $dir = $to . $startsAt;

            if (!is_dir($dir)) {
                mkdir($dir, 0755);
            }

            if (is_writable($to) && $object->isFile()) {
                copy($name, $dir . DIRECTORY_SEPARATOR . basename($name));
            }
        }
    }

    /**
     * Get filesystem
     *
     * @return Filesystem
     */
    private static function getFilesystem()
    {
        if (!self::$fileSystem) {
            self:: $fileSystem = new Filesystem();
        }

        return self::$fileSystem;
    }

    /**
     * Get a relative path from one path (e.g. the target for a symlink) to another path (e.g. the
     * path a symlink will point to).
     *
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    private static function getRelativePath($from, $to)
    {
        $from = explode(DIRECTORY_SEPARATOR, $from);
        $to = explode(DIRECTORY_SEPARATOR, $to);

        foreach ($from as $depth => $dir) {
            if (isset($to[$depth])) {
                if ($dir === $to[$depth]) {
                    unset($to[$depth]);
                    unset($from[$depth]);
                } else {
                    break;
                }
            }
        }

        for ($i = 0; $i < count($from) - 1; $i++) {
            array_unshift($to, "..");
        }

        $result = implode(DIRECTORY_SEPARATOR, $to);

        return $result;
    }
}
