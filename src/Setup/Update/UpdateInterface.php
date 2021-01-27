<?php
/**
 * UpdateInterface.php
 *
 * The UpdateInterface interface file.
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

namespace UserAccessManager\Setup\Update;

/**
 * Interface UpdateInterface
 *
 * @package UserAccessManager\Setup
 */
interface UpdateInterface
{
    /**
     * @return string
     */
    public function getVersion(): string;

    /**
     * @return bool
     */
    public function update(): bool;
}
