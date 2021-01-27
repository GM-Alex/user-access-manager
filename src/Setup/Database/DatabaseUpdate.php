<?php
/**
 * Update.php
 *
 * The Update class file.
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

namespace UserAccessManager\Setup\Database;

use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Setup\Update\UpdateInterface;

/**
 * Class Update
 *
 * @package UserAccessManager\Setup\Update
 */
abstract class DatabaseUpdate implements UpdateInterface
{
    /**
     * @var Database
     */
    protected $database;

    /**
     * @var ObjectHandler
     */
    protected $objectHandler;

    /**
     * Update constructor.
     * @param Database $database
     * @param ObjectHandler $objectHandler
     */
    public function __construct(Database $database, ObjectHandler $objectHandler)
    {
        $this->database = $database;
        $this->objectHandler = $objectHandler;
    }
}
