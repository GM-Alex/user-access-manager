<?php

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend\ObjectMembership;

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

    private function getTermObjectType(int|string|null $termId): string
    {
        $term = $this->objectHandler->getTerm($termId);

        return ($term !== false) ? $term->taxonomy : ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
    }

    /**
     * @throws UserGroupTypeException
     */
    public function addTermColumn(?string $content, string $columnName, int|string|null $id): ?string
    {
        if ($columnName === self::COLUMN_NAME) {
            $content .= $this->getGroupColumn($this->getTermObjectType($id), $id);
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
        $this->saveObjectData($this->getTermObjectType($termId), $termId);
    }

    public function removeTermData(int|string|null $termId): void
    {
        $this->removeObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId);
    }
}
