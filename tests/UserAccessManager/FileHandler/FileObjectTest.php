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
namespace UserAccessManager\FileHandler;

class FileObjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileObject::__construct()
     */
    public function testCanCreateInstance()
    {
        $fileObject = new FileObject('id', 'type', 'file', false);
        self::assertInstanceOf(FileObject::class, $fileObject);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\FileHandler\FileObject::getId()
     * @covers \UserAccessManager\FileHandler\FileObject::getType()
     * @covers \UserAccessManager\FileHandler\FileObject::getFile()
     * @covers \UserAccessManager\FileHandler\FileObject::isImage()
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
