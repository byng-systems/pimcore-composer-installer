<?php

/**
 * This file is part of the pimcore-composer-copier package.
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

/**
 * Pimcore Copier
 *
 * @author Elliot Wright <elliot@elliotwright.co>
 */
final class PimcoreCopier
{
    /**
     * Copy Pimcore to the `document-root-path`, or a sensible default
     *
     * @param Event $event
     *
     * @return void
     */
    public static function installPimcore(Event $event)
    {
        $config = $event->getComposer()->getConfig();
        $cwd = getcwd();

        $installPath = realpath($cwd . "/" . ($config->get("document-root-path") ?: "./www"));
        $vendorPath = realpath($cwd . "/" . ($config->get("vendir-dir") ?: "./vendor"));

        if (!$installPath || !$vendorPath) {
            throw new \RuntimeException(
                "Invalid install path, or vendor path. Note: the directories must exist."
            );
        }

        $from = $vendorPath . "/pimcore/pimcore/pimcore";
        $to = $installPath . "/pimcore";

        $result = @rename($from, $to);

        if (!$result) {
            throw new \RuntimeException(
                "Failed to install Pimcore. Please ensure you have permission to write files in " .
                "your document root. Also make sure you haven't already installed Pimcore."
            );
        }
    }
}
