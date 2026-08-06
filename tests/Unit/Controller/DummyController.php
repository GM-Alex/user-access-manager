<?php

namespace UserAccessManager\Tests\Unit\Controller;

use UserAccessManager\Controller\Controller;

class DummyController extends Controller
{
    public function testAction(): void
    {
        echo 'testAction';
    }

    public function multiWordTestAction(): void
    {
        echo 'multiWordTestAction';
    }
}
