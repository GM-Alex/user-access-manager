<?php
/**
 * ObjectInformationFactory.php
 *
 * The ObjectInformationFactory class file.
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

/**
 * Class ObjectInformationFactory
 *
 * @package UserAccessManager\Controller\Backend
 */
class ObjectInformationFactory
{
    /**
     * Creates and returns a new object information object.
     * @return ObjectInformation
     */
    public function createObjectInformation(): ObjectInformation
    {
        return new ObjectInformation();
    }
}
