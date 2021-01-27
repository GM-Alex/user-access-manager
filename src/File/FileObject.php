<?php
/**
 * FileObject.php
 *
 * The FileObject class file.
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

namespace UserAccessManager\File;

/**
 * Class FileObject
 * @package UserAccessManager\FileHandler
 */
class FileObject
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $file;

    /**
     * @var bool
     */
    private $isImage;

    /**
     * FileObject constructor.
     * @param string $id
     * @param string $type
     * @param string $file
     * @param bool $isImage
     */
    public function __construct(string $id, string $type, string $file, $isImage = false)
    {
        $this->id = $id;
        $this->type = $type;
        $this->file = $file;
        $this->isImage = $isImage;
    }

    /**
     * Returns the file object id.
     * @return string|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the file object type.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the file object file.
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * Returns true if the file is a image.
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->isImage;
    }
}
