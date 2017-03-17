<?php
/**
 * AdminSettingsController.php
 *
 * The AdminSettingsController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $Id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\Config\Config;
use UserAccessManager\Config\ConfigParameter;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Wrapper\Wordpress;

class AdminSettingsController extends Controller
{
    /**
     * @var ObjectHandler
     */
    protected $_oObjectHandler;

    /**
     * @var FileHandler
     */
    protected $_oFileHandler;

    /**
     * @var string
     */
    protected $_sTemplate = 'AdminSettings.php';

    /**
     * AdminSettingsController constructor.
     *
     * @param Wordpress     $oWrapper
     * @param Config        $oConfig
     * @param ObjectHandler $oObjectHandler
     * @param FileHandler   $oFileHandler
     */
    public function __construct(Wordpress $oWrapper, Config $oConfig, ObjectHandler $oObjectHandler, FileHandler $oFileHandler)
    {
        parent::__construct($oWrapper, $oConfig);
        $this->_oObjectHandler = $oObjectHandler;
        $this->_oFileHandler = $oFileHandler;
    }

    /**
     * Returns true if the server is a nginx server.
     *
     * @return bool
     */
    public function isNginx()
    {
        return $this->_oWrapper->isNginx();
    }

    /**
     * Returns the pages.
     *
     * @return array
     */
    public function getPages()
    {
        $aPages = $this->_oWrapper->getPages('sort_column=menu_order');
        return is_array($aPages) !== false ? $aPages : array();
    }

    /**
     * Returns the config parameters.
     *
     * @return \UserAccessManager\Config\ConfigParameter[]
     */
    public function getConfigParameters()
    {
        return $this->_oConfig->getConfigParameters();
    }

    /**
     * Returns the post types as object.
     *
     * @return \WP_Post_Type[]
     */
    protected function _getPostTypes()
    {
        return $this->_oWrapper->getPostTypes(array('public' => true), 'objects');
    }

    /**
     * Returns the taxonomies as objects.
     *
     * @return \WP_Taxonomy[]
     */
    protected function _getTaxonomies()
    {
        return $this->_oWrapper->getTaxonomies(array('public' => true), 'objects');
    }

    /**
     * Returns the grouped config parameters.
     *
     * @return array
     */
    public function getGroupedConfigParameters()
    {
        $aConfigParameters = $this->_oConfig->getConfigParameters();

        $aGroupedConfigParameters = array();
        $aPostTypes = $this->_getPostTypes();

        foreach ($aPostTypes as $sPostType => $oPostType) {
            if ($sPostType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
                continue;
            }

            $aGroupedConfigParameters[$sPostType] = array(
                $aConfigParameters["hide_{$sPostType}"],
                $aConfigParameters["hide_{$sPostType}_title"],
                $aConfigParameters["{$sPostType}_title"],
                $aConfigParameters["{$sPostType}_content"],
                $aConfigParameters["hide_{$sPostType}_comment"],
                $aConfigParameters["{$sPostType}_comment_content"],
                $aConfigParameters["{$sPostType}_comments_locked"]
            );

            if ($sPostType === 'post') {
                $aGroupedConfigParameters[$sPostType][] = $aConfigParameters["show_{$sPostType}_content_before_more"];
            }
        }

        $aTaxonomies = $this->_getTaxonomies();

        foreach ($aTaxonomies as $sTaxonomy => $oTaxonomy) {
            $aGroupedConfigParameters[$sTaxonomy][] = $aConfigParameters["hide_empty_{$sTaxonomy}"];
        }

        $aGroupedConfigParameters['file'] = array(
            $aConfigParameters['lock_file'],
            $aConfigParameters['download_type']
        );

        $aGroupedConfigParameters['author'] = array(
            $aConfigParameters['authors_has_access_to_own'],
            $aConfigParameters['authors_can_add_posts_to_groups'],
            $aConfigParameters['full_access_role'],
        );

        $aGroupedConfigParameters['other'] = array(
            $aConfigParameters['lock_recursive'],
            $aConfigParameters['protect_feed'],
            $aConfigParameters['redirect'],
            $aConfigParameters['blog_admin_hint'],
            $aConfigParameters['blog_admin_hint_text'],
        );

        if ($this->_oConfig->isPermalinksActive() === true) {
            $aGroupedConfigParameters['file'][] = $aConfigParameters['lock_file_types'];
            $aGroupedConfigParameters['file'][] = $aConfigParameters['file_pass_type'];
        }

        return $aGroupedConfigParameters;
    }

    /**
     * Update settings action.
     */
    public function updateSettingsAction()
    {
        $this->_verifyNonce('uamUpdateSettings');

        $aNewConfigParameters = $this->getRequestParameter('config_parameters');
        $aNewConfigParameters = array_map('htmlentities', $aNewConfigParameters);
        $this->_oConfig->setConfigParameters($aNewConfigParameters);

        if ($this->_oConfig->lockFile() === false) {
            $this->_oFileHandler->deleteFileProtection();
        } else {
            $this->_oFileHandler->createFileProtection();
        }

        $this->_oWrapper->doAction('uam_update_options', $this->_oConfig);
        $this->_setUpdateMessage(TXT_UAM_UPDATE_SETTINGS);
    }

    /**
     * Checks if the group is a post type.
     *
     * @param string $sGroupKey
     *
     * @return bool
     */
    public function isPostTypeGroup($sGroupKey)
    {
        $aPostTypes = $this->_getPostTypes();

        return isset($aPostTypes[$sGroupKey]);
    }

    /**
     * Returns the right translation string.
     *
     * @param string $sGroupKey
     * @param string $sIdent
     * @param bool   $blDescription
     *
     * @return mixed|string
     */
    protected function _getObjectText($sGroupKey, $sIdent, $blDescription = false)
    {
        $aObjects = $this->_getPostTypes() + $this->_getTaxonomies();
        $sIdent .= ($blDescription === true) ? '_DESC' : '';

        if (isset($aObjects[$sGroupKey]) === true) {
            $sIdent = str_replace(strtoupper($sGroupKey), 'OBJECT', $sIdent);
            $sText = constant($sIdent);
            $iCount = substr_count($sText, '%s');
            $aArguments = array_fill(0, $iCount, $aObjects[$sGroupKey]->labels->name);
            return vsprintf($sText, $aArguments);
        }

        return constant($sIdent);
    }

    /**
     * @param string $sGroupKey
     * @param bool   $blDescription
     *
     * @return string
     */
    public function getSectionText($sGroupKey, $blDescription = false)
    {
        return $this->_getObjectText(
            $sGroupKey,
            'TXT_UAM_'.strtoupper($sGroupKey).'_SETTING',
            $blDescription
        );
    }

    /**
     * Returns the label for the parameter.
     *
     * @param string          $sGroupKey
     * @param ConfigParameter $oConfigParameter
     * @param bool            $blDescription
     *
     * @return string
     */
    public function getParameterText($sGroupKey, ConfigParameter $oConfigParameter, $blDescription = false)
    {
        $sIdent = 'TXT_UAM_'.strtoupper($oConfigParameter->getId());

        return $this->_getObjectText(
            $sGroupKey,
            $sIdent,
            $blDescription
        );
    }
}