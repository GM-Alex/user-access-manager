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
namespace UserAccessManager\Controller\Backend;

use UserAccessManager\Object\ObjectHandler;

/**
 * Class TermObjectController
 *
 * @package UserAccessManager\Controller\Backend
 */
class TermObjectController extends ObjectController
{
    /**
     * The function for the manage_categories_columns filter.
     *
     * @param array $defaults The table headers.
     *
     * @return array
     */
    public function addTermColumnsHeader($defaults)
    {
        $defaults[self::COLUMN_NAME] = TXT_UAM_COLUMN_ACCESS;
        return $defaults;
    }

    /**
     * The function for the manage_categories_custom_column action.
     *
     * @param string  $content    Content for the column. Multiple filter calls are possible, so we need to append.
     * @param string  $columnName The column name.
     * @param integer $id         The id.
     *
     * @return string $content with content appended for self::COLUMN_NAME column
     */
    public function addTermColumn($content, $columnName, $id)
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
     *
     * @param string|\WP_Term $term The term.
     */
    public function showTermEditForm($term)
    {
        if ($term instanceof \WP_Term) {
            $this->setObjectInformation($term->taxonomy, $term->term_id);
        } else {
            $this->setObjectInformation($term, null);
        }

        echo $this->getIncludeContents('TermEditForm.php');
    }

    /**
     * The function for the edit_term action.
     *
     * @param integer $termId The term id.
     */
    public function saveTermData($termId)
    {
        $term = $this->objectHandler->getTerm($termId);
        $objectType = ($term !== false) ? $term->taxonomy : ObjectHandler::GENERAL_TERM_OBJECT_TYPE;
        $this->saveObjectData($objectType, $termId);
    }

    /**
     * The function for the delete_{term} action.
     *
     * @param integer $termId The id of the term.
     */
    public function removeTermData($termId)
    {
        $this->removeObjectData(ObjectHandler::GENERAL_TERM_OBJECT_TYPE, $termId);
    }
}
