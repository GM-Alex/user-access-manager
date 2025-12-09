<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

class ObjectInformationFactory
{
    public function createObjectInformation(): ObjectInformation
    {
        return new ObjectInformation();
    }
}
