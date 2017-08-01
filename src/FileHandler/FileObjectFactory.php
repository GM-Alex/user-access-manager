<?php
/**
 * FileObjectFactory.php
 *
 * The FileObjectFactory class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\FileHandler;

/**
 * Class FileObjectFactory
 * @package UserAccessManager\FileHandler
 */
class FileObjectFactory
{
    /**
     * Returns a new file object.
     *
     * @param string $id
     * @param string $type
     * @param string $file
     * @param bool   $isImage
     *
     * @return FileObject
     */
    public function createFileObject($id, $type, $file, $isImage = false)
    {
        return new FileObject($id, $type, $file, $isImage);
    }
}
