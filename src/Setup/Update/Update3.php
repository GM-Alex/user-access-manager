<?php

namespace UserAccessManager\Setup\Update;

use UserAccessManager\Object\ObjectHandler;

class Update3 extends Update implements UpdateInterface
{
    /**
     * Returns the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.3';
    }

    /**
     * Executes the update.
     *
     * @return bool
     */
    public function update()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $generalTermType = ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $update = $this->database->update(
            $dbAccessGroupToObject,
            ['object_type' => $generalTermType],
            ['object_type' => 'category']
        );

        return $update !== false;
    }
}
