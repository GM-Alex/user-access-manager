<?php
/**
 * TermObjectController.php
 *
 * The TermObjectController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Object\ObjectHandler;
use UserAccessManager\UserGroup\UserGroupTypeException;
use WP_Term;

/**
 * Class TermObjectController
 *
 * @package UserAccessManager\Controller\Backend
 */
class TermObjectController extends ObjectController
{
    /**
     * The function for the manage_categories_columns filter.
     * @param array $defaults The table headers.
     * @return array
     */
    public function addTermColumnsHeader(array $defaults): array
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $defaults;
    }

    /**
     * The function for the manage_categories_custom_column action.
     * @param string|null $content Content for the column. Multiple filter calls are possible, so we need to append.
     * @param string $columnName The column name.
     * @param int|string $id The id.
     * @return string|null $content with content appended for self::COLUMN_NAME column
     * @throws UserGroupTypeException
     */
    public function addTermColumn(?string $content, string $columnName, $id): ?string
    {
        if ($columnName === self::COLUMN_NAME) {
            $term = $this->objectHandler->getTerm($id);
            $objectType = ($term !== false) ? $term->taxonomy : ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
            $content .= $this->getGroupColumn($objectType, $id);
        }

        return $content;
    }

    /**
     * The function for the edit_{term}_form action.
     * @param string|WP_Term $term The term.
     * @throws UserGroupTypeException
     */
    public function showTermEditForm($term)
    {
        if ($term instanceof WP_Term) {
            $this->setObjectInformation($term->taxonomy, $term->term_id);
        } else {
            $this->setObjectInformation($term, null);
        }

        echo $this->getIncludeContents('TermEditForm.php');
    }

    /**
     * The function for the edit_term action.
     * @param int|string $termId The term id.
     * @throws UserGroupTypeException
     */
    public function saveTermData($termId)
    {
        $term = $this->objectHandler->getTerm($termId);
        $objectType = ($term !== false) ? $term->taxonomy : ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $this->saveObjectData($objectType, $termId);
    }

    /**
     * The function for the delete_{term} action.
     * @param int|string $termId The id of the term.
     */
    public function removeTermData($termId)
    {
        $this->removeObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId);
    }
}
