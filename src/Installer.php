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
     * Install everything with sensible defaults. See each install method for more information.
     *
     * @param Event $event
     *
     * @return void
     */
    public static function install(Event $event)
    {
        self::installHtAccessFile($event);
        self::installIndex($event);
        self::installPimcore($event);
        self::installPlugins($event);
        self::installVendorLink($event);
        self::installWebsite($event);
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
        list($installPath, $vendorPath) = self::prepareBaseDirectories($event);

        $from = $vendorPath . "/pimcore/pimcore/index.php";
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
        list($installPath, $vendorPath) = self::prepareBaseDirectories($event);

        $from = $vendorPath . "/pimcore/pimcore/pimcore";
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
        list($installPath, $vendorPath) = self::prepareBaseDirectories($event);

        $from = $vendorPath . "/pimcore/pimcore/plugins_example";
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
        list($installPath, $vendorPath) = self::prepareBaseDirectories($event);

        $from = $vendorPath . "/pimcore/pimcore/website_example";
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
        list($installPath, $vendorPath) = self::prepareBaseDirectories($event);

        $from = $vendorPath . "/pimcore/pimcore/.htaccess";
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
        list($installPath, $vendorPath) = self::prepareBaseDirectories($event);

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
     * @return array
     */
    private static function prepareBaseDirectories(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $cwd = getcwd();

        $installPath = realpath($cwd . DIRECTORY_SEPARATOR . ($config->get("document-root-path") ?: "./www"));
        $vendorPath = $config->get("vendor-dir");

        if (!$installPath || !$vendorPath) {
            throw new \RuntimeException(
                "Invalid install path, or vendor path. Note: the directories must exist. " .
                "Install path was '" . $installPath . "'. Vendor path was '" . $vendorPath . "'. " .
                "Aborting Pimcore installation. "
            );
        }

        return [
            $installPath,
            $vendorPath,
        ];
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
