<?php

namespace UserAccessManager\Tests\Unit\Setup\Update;

use UserAccessManager\Setup\Update\DatabaseUpdate8;
use UserAccessManager\Tests\StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate8
 */
class DatabaseUpdate8Test extends UserAccessManagerTestCase
{
    /**
     * @group  unit
     * @covers ::__construct()
     */
    public function testCanCreateInstance()
    {
        $update = new DatabaseUpdate8(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf(DatabaseUpdate8::class, $update);
    }

    /**
     * @group  unit
     * @covers ::getVersion()
     */
    public function testGetVersion()
    {
        $update = new DatabaseUpdate8(
            $this->getDatabase(),
            $this->getObjectHandler()
        );

        self::assertEquals('1.6.3', $update->getVersion());
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\Setup\Update\DatabaseUpdate7::update()
     */
    public function testUpdateRepeatsTheUpdateOfItsParent()
    {
        $database = $this->getDatabase();

        $database->expects($this->once())
            ->method('getUserGroupTable')
            ->will($this->returnValue('userGroupTable'));

        $database->expects($this->once())
            ->method('query')
            ->with(
                new MatchIgnoreWhitespace(
                    'ALTER TABLE userGroupTable
                    MODIFY ID INT NOT NULL AUTO_INCREMENT'
                )
            )
            ->will($this->returnValue(5));

        $update = new DatabaseUpdate8($database, $this->getObjectHandler());

        self::assertTrue($update->update());
    }
}
