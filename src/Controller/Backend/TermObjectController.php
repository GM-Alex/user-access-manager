<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_Term;

class TermObjectController extends ObjectController
{
    public function addTermColumnsHeader(array $defaults): array
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $defaults;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function addTermColumn(?string $content, string $columnName, int|string $id): ?string
    {
        if ($columnName === self::COLUMN_NAME) {
            $term = $this->objectHandler->getTerm($id);
            $objectType = ($term !== false) ? $term->taxonomy : ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
            $content .= $this->getGroupColumn($objectType, $id);
        }

        return $content;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function showTermEditForm($term): void
    {
        if ($term instanceof WP_Term) {
            $this->setObjectInformation($term->taxonomy, $term->term_id);
        } else {
            $this->setObjectInformation($term, null);
        }

        echo $this->getIncludeContents('TermEditForm.php');
    }

    /**
     * @throws UserGroupTypeException
     */
    public function saveTermData($termId): void
    {
        $term = $this->objectHandler->getTerm($termId);
        $objectType = ($term !== false) ? $term->taxonomy : ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $this->saveObjectData($objectType, $termId);
    }

    public function removeTermData(int|string $termId): void
    {
        $this->removeObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId);
    }
}
