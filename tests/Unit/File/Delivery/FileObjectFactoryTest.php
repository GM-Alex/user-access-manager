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

namespace UserAccessManager\Tests\Unit\File\Delivery;

use PHPUnit\Framework\TestCase;
use UserAccessManager\File\Delivery\FileObject;
use UserAccessManager\File\Delivery\FileObjectFactory;

/**
 * Class FileObjectFactoryTest
 *
 * @package UserAccessManager\Tests\Unit\File
 * @coversDefaultClass \UserAccessManager\File\Delivery\FileObjectFactory
 */
class FileObjectFactoryTest extends TestCase
{
    /**
     * @group  unit
     */
    public function testCanCreateInstance(): FileObjectFactory
    {
        $fileObjectFactory = new FileObjectFactory();

        self::assertInstanceOf(FileObjectFactory::class, $fileObjectFactory);

        return $fileObjectFactory;
    }

    /**
     * @group   unit
     * @depends testCanCreateInstance
     * @covers  ::createFileObject()
     */
    public function testCreateApacheFileProtection(FileObjectFactory $fileObjectFactory)
    {
        $fileObject = $fileObjectFactory->createFileObject('id', 'type', 'file');
        self::assertInstanceOf(FileObject::class, $fileObject);
        self::assertFalse($fileObject->isImage());
    }
}
