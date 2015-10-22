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

namespace SeerUK\Composer;

use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Pimcore Installer
 *
 * @author Elliot Wright <elliot@elliotwright.co>
 */
final class PimcoreInstaller
{
    /**
     * @var Filesystem
     */
    private static $fileSystem;


    /**
     * Copy Pimcore to the 'document-root-path', or a sensible default
     *
     * @param Event $event
     *
     * @return void
     */
    public static function install(Event $event)
    {
        list($installPath, $vendorPath) = self::prepareBaseDirectories($event);

        $from = $vendorPath . "/pimcore/pimcore/pimcore";
        $to = $installPath . "/pimcore";

        self::deleteFolder($to);
        self::copyFolder($from, $to);
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

        $fs = self::getFilesystem();
        $to = $installPath . "/index.php";

        $route = $fs->findShortestPath($to, $vendorPath);

        $contents = <<<EOF
<?php

require_once __DIR__ . "/{$route}/autoload.php";
require_once __DIR__ . "/pimcore/config/startup.php";

try {
    Pimcore::run();
} catch (Exception \$e) {
    if (class_exists("Logger")) {
        Logger::emerg(\$e);
    }

    throw \$e;
}

EOF;

        file_put_contents($to, $contents);
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
     * Prepare base directories for copying and installation
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
            $vendorPath
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
}
