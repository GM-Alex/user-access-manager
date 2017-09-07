<?php
/**
 * FileObjectFactoryTest.php
 *
 * The FileObjectFactoryTest unit test class file.
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

use UserAccessManager\File\FileObject;
use UserAccessManager\File\FileObjectFactory;

/**
 * Class FileObjectFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\FileObjectFactory
 */
class FileObjectFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group  unit
     *
     * @return FileObjectFactory
     */
    public function testCanCreateInstance()
    {
        $fileObjectFactory = new FileObjectFactory();

        self::assertInstanceOf(FileObjectFactory::class, $fileObjectFactory);

        return $fileObjectFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFileObject()
     *
     * @param FileObjectFactory $fileObjectFactory
     */
    public function testCreateApacheFileProtection(FileObjectFactory $fileObjectFactory)
    {
        $fileObject = $fileObjectFactory->createFileObject('id', 'type', 'file');
        self::assertInstanceOf(FileObject::class, $fileObject);
    }
}
