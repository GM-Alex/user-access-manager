<?php

namespace UserAccessManager\Tests\Unit\Setup\Update;

use PHPUnit\Framework\MockObject\MockObject;
use UserAccessManager\Setup\Update\DatabaseUpdate;
use UserAccessManager\Tests\Unit\UserAccessManagerTestCase;

/**
 * @coversDefaultClass \UserAccessManager\Setup\Update\DatabaseUpdate
 */
class DatabaseUpdateTest extends UserAccessManagerTestCase
{
    /**
     * @return MockObject|DatabaseUpdate
     */
    private function getStub(): DatabaseUpdate|MockObject
    {
        return $this->getMockForAbstractClass(
            DatabaseUpdate::class,
            [],
            '',
            false,
            true,
            true
        );
    }

    /**
     * @group   unit
     * @covers  ::__construct()
     */
    public function testCanCreateInstance()
    {
        $stub = $this->getStub();
        $stub->__construct($this->getDatabase(), $this->getObjectHandler());
        self::assertInstanceOf(DatabaseUpdate::class, $stub);
    }
}
