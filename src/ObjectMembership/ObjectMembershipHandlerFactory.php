<?php

declare(strict_types=1);

namespace UserAccessManager\ObjectMembership;

use Exception;
use UserAccessManager\Database\Database;
use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\Object\ObjectMapHandler;
use UserAccessManager\UserGroup\AssignmentInformationFactory;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class ObjectMembershipHandlerFactory
{
    public function __construct(
        private Php $php,
        private Wordpress $wordpress,
        private Database $database,
        private ObjectMapHandler $objectMapHandler,
        private AssignmentInformationFactory $assignmentInformationFactory
    ) {
    }

    /**
     * @throws Exception
     */
    public function createPostMembershipHandler(ObjectHandler $objectHandler): PostMembershipHandler
    {
        return new PostMembershipHandler(
            $this->assignmentInformationFactory,
            $this->wordpress,
            $objectHandler,
            $this->objectMapHandler
        );
    }

    /**
     * @throws Exception
     */
    public function createRoleMembershipHandler(): RoleMembershipHandler
    {
        return new RoleMembershipHandler($this->assignmentInformationFactory, $this->wordpress);
    }

    /**
     * @throws Exception
     */
    public function createTermMembershipHandler(ObjectHandler $objectHandler): TermMembershipHandler
    {
        return new TermMembershipHandler(
            $this->assignmentInformationFactory,
            $this->wordpress,
            $objectHandler,
            $this->objectMapHandler
        );
    }

    /**
     * @throws Exception
     */
    public function createUserMembershipHandler(ObjectHandler $objectHandler): UserMembershipHandler
    {
        return new UserMembershipHandler(
            $this->assignmentInformationFactory,
            $this->php,
            $this->database,
            $objectHandler
        );
    }
}
