<?php
/**
 * ObjectHandler.php
 *
 * The ObjectHandler class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\ObjectHandler;

use UserAccessManager\Database\Database;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class ObjectHandler
 *
 * @package UserAccessManager\ObjectHandler
 */
class ObjectHandler
{
    const TREE_MAP_PARENTS = 'PARENT';
    const TREE_MAP_CHILDREN = 'CHILDREN';
    const GENERAL_ROLE_OBJECT_TYPE = '_role_';
    const GENERAL_USER_OBJECT_TYPE = '_user_';
    const GENERAL_POST_OBJECT_TYPE = '_post_';
    const GENERAL_TERM_OBJECT_TYPE = '_term_';
    const ATTACHMENT_OBJECT_TYPE = 'attachment';
    const POST_OBJECT_TYPE = 'post';
    const PAGE_OBJECT_TYPE = 'page';
    /**
     * @var Wordpress
     */
    protected $oWordpress;

    /**
     * @var array
     */
    protected $aPostTypes = null;

    /**
     * @var array
     */
    protected $aTaxonomies = null;

    /**
     * @var \WP_User
     */
    protected $aUsers = null;

    /**
     * @var \WP_Post[]
     */
    protected $aPosts = null;

    /**
     * @var \WP_Term[]
     */
    protected $aTerms = null;

    /**
     * @var array
     */
    protected $aTermPostMap = null;

    /**
     * @var array
     */
    protected $aPostTermMap = null;

    /**
     * @var array
     */
    protected $aTermTreeMap = null;

    /**
     * @var array
     */
    protected $aPostTreeMap = null;

    /**
     * @var array
     */
    protected $aPluggableObjects = [];

    /**
     * @var array
     */
    protected $aObjectTypes = null;

    /**
     * @var array
     */
    protected $aAllObjectTypes = null;

    /**
     * @var array
     */
    protected $aValidObjectTypes = [];

    /**
     * Cache constructor.
     *
     * @param Wordpress $oWordpress
     * @param Database  $oDatabase
     */
    public function __construct(Wordpress $oWordpress, Database $oDatabase)
    {
        $this->oWordpress = $oWordpress;
        $this->oDatabase = $oDatabase;
    }

    /**
     * Returns all post types.
     *
     * @return array
     */
    public function getPostTypes()
    {
        if ($this->aPostTypes === null) {
            $this->aPostTypes = $this->oWordpress->getPostTypes(['public' => true]);
        }

        return $this->aPostTypes;
    }

    /**
     * Returns the taxonomies.
     *
     * @return array
     */
    public function getTaxonomies()
    {
        if ($this->aTaxonomies === null) {
            $this->aTaxonomies = $this->oWordpress->getTaxonomies(['public' => true]);
        }

        return $this->aTaxonomies;
    }

    /**
     * Returns a user.
     *
     * @param int|string $sId The user id.
     *
     * @return \WP_User|false
     */
    public function getUser($sId)
    {
        if (!isset($this->aUsers[$sId])) {
            $this->aUsers[$sId] = $this->oWordpress->getUserData($sId);
        }

        return $this->aUsers[$sId];
    }

    /**
     * Returns a post.
     *
     * @param string $sId The post id.
     *
     * @return \WP_Post|array|false
     */
    public function getPost($sId)
    {
        if (!isset($this->aPosts[$sId])) {
            $oPost = $this->oWordpress->getPost($sId);
            $this->aPosts[$sId] = ($oPost === null) ? false : $oPost;
        }

        return $this->aPosts[$sId];
    }

    /**
     * Returns a term.
     *
     * @param string $sId       The term id.
     * @param string $sTaxonomy The taxonomy.
     *
     * @return array|false|\WP_Error|\WP_Term
     */
    public function getTerm($sId, $sTaxonomy = '')
    {
        $sFullId = $sId.'|'.$sTaxonomy;

        if (!isset($this->aTerms[$sFullId])) {
            $oTerm = $this->oWordpress->getTerm($sId, $sTaxonomy);
            $this->aTerms[$sFullId] = ($oTerm === null) ? false : $oTerm;
        }

        return $this->aTerms[$sFullId];
    }

    /**
     * Resolves all tree map elements
     *
     * @param array $aMap
     * @param array $aSubMap
     *
     * @return array
     */
    protected function processTreeMapElements(array &$aMap, array $aSubMap = null)
    {
        $aProcessMap = ($aSubMap === null) ? $aMap : $aSubMap;

        foreach ($aProcessMap as $iId => $aSubIds) {
            foreach ($aSubIds as $iSubId => $sType) {
                if (isset($aMap[$iSubId])) {
                    $aMap[$iId] += $this->processTreeMapElements($aMap, [$iSubId => $aMap[$iSubId]])[$iSubId];
                }
            }
        }

        return $aMap;
    }

    /**
     * Returns the tree map for the query.
     *
     * @param string $sSelect
     * @param string $sGeneralType
     *
     * @return array
     */
    protected function getTreeMap($sSelect, $sGeneralType)
    {
        $aTreeMap = [
            self::TREE_MAP_CHILDREN => [
                $sGeneralType => []
            ],
            self::TREE_MAP_PARENTS => [
                $sGeneralType => []
            ]
        ];
        $aResults = $this->oDatabase->getResults($sSelect);

        foreach ($aResults as $oResult) {
            if (isset($aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type]) === false) {
                $aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type] = [];
            }

            if (isset($aTreeMap[self::TREE_MAP_PARENTS][$oResult->type]) === false) {
                $aTreeMap[self::TREE_MAP_PARENTS][$oResult->type] = [];
            }

            if (isset($aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type][$oResult->parentId]) === false) {
                $aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type][$oResult->parentId] = [];
            }

            if (isset($aTreeMap[self::TREE_MAP_PARENTS][$oResult->type][$oResult->id]) === false) {
                $aTreeMap[self::TREE_MAP_PARENTS][$oResult->type][$oResult->id] = [];
            }

            $aTreeMap[self::TREE_MAP_CHILDREN][$sGeneralType][$oResult->parentId][$oResult->id] = $oResult->type;
            $aTreeMap[self::TREE_MAP_CHILDREN][$oResult->type][$oResult->parentId][$oResult->id] = $oResult->type;
            $aTreeMap[self::TREE_MAP_PARENTS][$sGeneralType][$oResult->id][$oResult->parentId] = $oResult->type;
            $aTreeMap[self::TREE_MAP_PARENTS][$oResult->type][$oResult->id][$oResult->parentId] = $oResult->type;
        }

        //Process elements
        foreach ($aTreeMap as $sMapType => $aMayTypeMap) {
            foreach ($aMayTypeMap as $sObjectType => $aMap) {
                $aTreeMap[$sMapType][$sObjectType] = $this->processTreeMapElements($aMap);
            }
        }

        return $aTreeMap;
    }

    /**
     * Returns the post tree map.
     *
     * @return array
     */
    public function getPostTreeMap()
    {
        if ($this->aPostTreeMap === null) {
            $sSelect = "
                SELECT ID AS id, post_parent AS parentId, post_type AS type 
                FROM {$this->oDatabase->getPostsTable()}
                  WHERE post_parent != 0";

            $this->aPostTreeMap = $this->getTreeMap($sSelect, self::GENERAL_POST_OBJECT_TYPE);
        }

        return $this->aPostTreeMap;
    }

    /**
     * Returns the term tree map.
     *
     * @return array
     */
    public function getTermTreeMap()
    {
        if ($this->aTermTreeMap === null) {
            $sSelect = "
                SELECT term_id AS id, parent AS parentId, taxonomy AS type
                FROM {$this->oDatabase->getTermTaxonomyTable()}
                  WHERE parent != 0";

            $this->aTermTreeMap = $this->getTreeMap($sSelect, self::GENERAL_TERM_OBJECT_TYPE);
        }

        return $this->aTermTreeMap;
    }

    /**
     * Returns the term post map.
     *
     * @return array
     */
    public function getTermPostMap()
    {
        if ($this->aTermPostMap === null) {
            $this->aTermPostMap = [];

            $sSelect = "
                SELECT tr.object_id AS objectId, tt.term_id AS termId, p.post_type AS postType
                FROM {$this->oDatabase->getTermRelationshipsTable()} AS tr
                  LEFT JOIN {$this->oDatabase->getPostsTable()} AS p
                   ON (tr.object_id = p.ID)
                  LEFT JOIN {$this->oDatabase->getTermTaxonomyTable()} AS tt
                    ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

            $aResults = $this->oDatabase->getResults($sSelect);

            foreach ($aResults as $oResult) {
                if (!isset($this->aTermPostMap[$oResult->termId])) {
                    $this->aTermPostMap[$oResult->termId] = [];
                }

                $this->aTermPostMap[$oResult->termId][$oResult->objectId] = $oResult->postType;
            }
        }

        return $this->aTermPostMap;
    }

    /**
     * Returns the post term map.
     *
     * @return array
     */
    public function getPostTermMap()
    {
        if ($this->aPostTermMap === null) {
            $this->aPostTermMap = [];

            $sSelect = "
                SELECT tr.object_id AS objectId, tt.term_id AS termId, tt.taxonomy AS termType
                FROM {$this->oDatabase->getTermRelationshipsTable()} AS tr 
                  LEFT JOIN {$this->oDatabase->getTermTaxonomyTable()} AS tt
                    ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";

            $aResults = $this->oDatabase->getResults($sSelect);

            foreach ($aResults as $oResult) {
                if (!isset($this->aPostTermMap[$oResult->objectId])) {
                    $this->aPostTermMap[$oResult->objectId] = [];
                }

                $this->aPostTermMap[$oResult->objectId][$oResult->termId] = $oResult->termType;
            }
        }

        return $this->aPostTermMap;
    }

    /**
     * Used for adding custom post types using the registered_post_type hook
     * @see http://wordpress.org/support/topic/modifying-post-type-using-the-registered_post_type-hook
     *
     * @param string        $sPostType  The string for the new post_type
     * @param \WP_Post_Type $oArguments The array of arguments used to create the post_type
     */
    public function registeredPostType($sPostType, \WP_Post_Type $oArguments)
    {
        if ((bool)$oArguments->public === true) {
            $this->aPostTypes = $this->getPostTypes();
            $this->aPostTypes[$sPostType] = $sPostType;
            $this->aObjectTypes = null;
            $this->aAllObjectTypes = null;
            $this->aValidObjectTypes = null;
        }
    }

    /**
     * Adds an custom taxonomy.
     *
     * @param string $sTaxonomy
     * @param string $sObjectType
     * @param array  $aArguments
     */
    public function registeredTaxonomy($sTaxonomy, $sObjectType, array $aArguments)
    {
        if ((bool)$aArguments['public'] === true) {
            $this->aTaxonomies = $this->getTaxonomies();
            $this->aTaxonomies[$sTaxonomy] = $sTaxonomy;
            $this->aObjectTypes = null;
            $this->aAllObjectTypes = null;
            $this->aValidObjectTypes = null;
        }
    }

    /**
     * Checks if type is postable.
     *
     * @param string $sType
     *
     * @return bool
     */
    public function isPostType($sType)
    {
        $aPostableTypes = $this->getPostTypes();
        return isset($aPostableTypes[$sType]);
    }

    /**
     * Checks if the taxonomy is a valid one.
     *
     * @param string $sTaxonomy
     *
     * @return bool
     */
    public function isTaxonomy($sTaxonomy)
    {
        $aTaxonomies = $this->getTaxonomies();
        return in_array($sTaxonomy, $aTaxonomies);
    }

    /**
     * Registers object that should be handel by the user access manager.
     *
     * @param PluggableObject $oObject The object which you want to register.
     */
    public function registerPluggableObject(PluggableObject $oObject)
    {
        $this->aPluggableObjects[$oObject->getName()] = $oObject;
    }

    /**
     * Returns a registered pluggable object.
     *
     * @param string $sObjectName The name of the object which should be returned.
     *
     * @return PluggableObject
     */
    public function getPluggableObject($sObjectName)
    {
        if (isset($this->aPluggableObjects[$sObjectName])) {
            return $this->aPluggableObjects[$sObjectName];
        }

        return null;
    }

    /**
     * Returns true if the object is a pluggable object.
     *
     * @param string $sObjectName
     *
     * @return bool
     */
    public function isPluggableObject($sObjectName)
    {
        return isset($this->aPluggableObjects[$sObjectName]);
    }

    /**
     * Returns all registered pluggable objects.
     *
     * @return PluggableObject[]
     */
    public function getPluggableObjects()
    {
        return $this->aPluggableObjects;
    }

    /**
     * Returns the predefined object types.
     *
     * @return array
     */
    public function getObjectTypes()
    {
        if ($this->aObjectTypes === null) {
            $this->aObjectTypes = array_merge(
                $this->getPostTypes(),
                $this->getTaxonomies()
            );
        }

        return $this->aObjectTypes;
    }

    /**
     * Returns all objects types.
     *
     * @return array
     */
    public function getAllObjectTypes()
    {
        if ($this->aAllObjectTypes === null) {
            $aObjectTypes = $this->getObjectTypes();
            $aPluggableObjects = $this->getPluggableObjects();
            $aPluggableObjectKeys = array_keys($aPluggableObjects);
            $aPluggableObjectKeys = array_combine($aPluggableObjectKeys, $aPluggableObjectKeys);

            $this->aAllObjectTypes = array_merge(
                [
                    self::GENERAL_ROLE_OBJECT_TYPE => self::GENERAL_ROLE_OBJECT_TYPE,
                    self::GENERAL_USER_OBJECT_TYPE => self::GENERAL_USER_OBJECT_TYPE,
                    self::GENERAL_POST_OBJECT_TYPE => self::GENERAL_POST_OBJECT_TYPE,
                    self::GENERAL_TERM_OBJECT_TYPE => self::GENERAL_TERM_OBJECT_TYPE
                ],
                $aObjectTypes,
                $aPluggableObjectKeys
            );
        }

        return $this->aAllObjectTypes;
    }

    /**
     * Returns the general object type.
     *
     * @param string $sObjectType
     *
     * @return string
     */
    public function getGeneralObjectType($sObjectType)
    {
        if ($sObjectType === self::GENERAL_USER_OBJECT_TYPE
            || $sObjectType === self::GENERAL_ROLE_OBJECT_TYPE
            || $sObjectType === self::GENERAL_TERM_OBJECT_TYPE
            || $sObjectType === self::GENERAL_POST_OBJECT_TYPE
        ) {
            return $sObjectType;
        } elseif ($this->isPostType($sObjectType)) {
            return self::GENERAL_POST_OBJECT_TYPE;
        } elseif ($this->isTaxonomy($sObjectType)) {
            return self::GENERAL_TERM_OBJECT_TYPE;
        }

        return null;
    }

    /**
     * Checks if the object type is a valid one.
     *
     * @param string $sObjectType The object type to check.
     *
     * @return boolean
     */
    public function isValidObjectType($sObjectType)
    {
        if (!isset($this->aValidObjectTypes[$sObjectType])) {
            $aObjectTypesMap = $this->getAllObjectTypes();
            $this->aValidObjectTypes[$sObjectType] = isset($aObjectTypesMap[$sObjectType]);
        }

        return $this->aValidObjectTypes[$sObjectType];
    }
}
