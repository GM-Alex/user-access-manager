<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 21.04.17
 * Time: 00:47
 */

namespace UserAccessManager\FileHandler;

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
     * @covers  \UserAccessManager\FileHandler\FileObjectFactory::createFileObject()
     *
     * @param FileObjectFactory $fileObjectFactory
     */
    public function testCreateApacheFileProtection(FileObjectFactory $fileObjectFactory)
    {
        $fileObject = $fileObjectFactory->createFileObject('id', 'type', 'file');
        self::assertInstanceOf(FileObject::class, $fileObject);
    }
}
