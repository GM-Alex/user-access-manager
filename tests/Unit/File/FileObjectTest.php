<?php
/**
 * FileObjectTest.php
 *
 * The FileObjectTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

namespace UserAccessManager\Tests\Unit\File;

use PHPUnit\Framework\TestCase;
use UserAccessManager\File\FileObject;

/**
 * Class FileObjectTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\FileObject
 */
class FileObjectTest extends TestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $fileObject = new FileObject('id', 'type', 'file', false);
        self::assertInstanceOf(FileObject::class, $fileObject);
    }

    /**
     * @group  unit
     * @covers ::getId()
     * @covers ::getType()
     * @covers ::getFile()
     * @covers ::isImage()
     */
    public function testGetters()
    {
        $fileObject = new FileObject('id', 'type', 'file');
        self::assertEquals('id', $fileObject->getId());
        self::assertEquals('type', $fileObject->getType());
        self::assertEquals('file', $fileObject->getFile());
        self::assertFalse($fileObject->isImage());

        $fileObject = new FileObject('id', 'type', 'file', true);
        self::assertTrue($fileObject->isImage());
    }
}
