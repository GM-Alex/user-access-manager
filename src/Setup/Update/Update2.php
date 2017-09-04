<?php

namespace UserAccessManager\Setup\Update;

class Update2 extends Update implements UpdateInterface
{
    /**
     * Returns the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return '1.2';
    }

    /**
     * Executes the update.
     *
     * @return bool
     */
    public function update()
    {
        $dbAccessGroupToObject = $this->database->getUserGroupToObjectTable();
        $query = "ALTER TABLE `{$dbAccessGroupToObject}`
            CHANGE `object_id` `object_id` VARCHAR(64) NOT NULL,
            CHANGE `object_type` `object_type` VARCHAR(64) NOT NULL";

        return $this->database->query($query) !== false;
    }
}
