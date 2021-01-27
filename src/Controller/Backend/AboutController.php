<?php
/**
 * AboutController.php
 *
 * The AboutController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Controller\Controller;

/**
 * Class AboutController
 *
 * @package UserAccessManager\Controller
 */
class AboutController extends Controller
{
    const SUPPORTER_FILE = 'supporters.json';
    const SUPPORTER_FILE_URL = 'https://gm-alex.github.io/user-access-manager/supporters.json';

    /**
     * @var string
     */
    protected $template = 'AdminAbout.php';

    /**
     * @var null|array
     */
    private $supporters = null;

    /**
     * Returns all the supporters.
     * @return array
     */
    private function getAllSupporters(): ?array
    {
        if ($this->supporters === null) {
            $realPath = rtrim($this->wordpressConfig->getRealPath(), DIRECTORY_SEPARATOR);
            $path = [$realPath, 'assets'];
            $path = implode(DIRECTORY_SEPARATOR, $path) . DIRECTORY_SEPARATOR;
            $fileWithPath = $path . self::SUPPORTER_FILE;
            $needsUpdate = is_file($fileWithPath) === false
                || filemtime($fileWithPath) < $this->wordpress->currentTime('timestamp') - 24 * 60 * 60;
            $fileContent = ($needsUpdate === true) ? @file_get_contents(self::SUPPORTER_FILE_URL) : false;

            if ($fileContent !== false) {
                file_put_contents($fileWithPath, $fileContent);
            } elseif (is_file($fileWithPath) === true) {
                $fileContent = file_get_contents($fileWithPath);
            }

            $this->supporters = (is_string($fileContent) === true) ? json_decode($fileContent, true) : [];
        }

        return $this->supporters;
    }

    /**
     * Returns the people which earn a special thanks.
     * @return array
     */
    public function getSpecialThanks(): array
    {
        $supporters = $this->getAllSupporters();
        return isset($supporters['special-thanks']) === true ? $supporters['special-thanks'] : [];
    }

    /**
     * Returns the people which earn a special thanks.
     * @return array
     */
    public function getTopSupporters(): array
    {
        $supporters = $this->getAllSupporters();
        return isset($supporters['top-supporters']) === true ? $supporters['top-supporters'] : [];
    }

    /**
     * Returns the people which earn a special thanks.
     * @return array
     */
    public function getSupporters(): array
    {
        $supporters = $this->getAllSupporters();
        return isset($supporters['supporters']) === true ? $supporters['supporters'] : [];
    }
}
