<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 16.01.17
 * Time: 23:21
 */
namespace UserAccessManager\Controller;


use UserAccessManager\Config\Config;
use UserAccessManager\UserAccessManager;
use UserAccessManager\Wrapper\Wordpress;

class AdminSettingsController extends Controller
{
    /**
     * @var Config
     */
    protected $_oConfig;

    /**
     * @var UserAccessManager
     */
    protected $_oUserAccessManager;

    /**
     * @var string
     */
    protected $_sTemplate = 'AdminSettings.php';

    public function __construct(Wordpress $oWrapper, Config $oConfig, UserAccessManager $oUserAccessManager)
    {
        parent::__construct($oWrapper);
        $this->_oConfig = $oConfig;
        $this->_oUserAccessManager = $oUserAccessManager;
    }

    public function getGroupedConfigParameters()
    {
        $aConfigParameters = $this->_oConfig->getConfigParameters();
        $aGroupedConfigParameters = array(
            'post' => array(
                $aConfigParameters['hide_post'],
                $aConfigParameters['hide_post_title'],
                $aConfigParameters['post_title'],
                $aConfigParameters['post_content'],
                $aConfigParameters['hide_post_comment'],
                $aConfigParameters['post_comment_content'],
                $aConfigParameters['post_comments_locked']
            ),
            'page' => array(
                $aConfigParameters['hide_page'],
                $aConfigParameters['hide_page_title'],
                $aConfigParameters['page_title'],
                $aConfigParameters['page_content'],
                $aConfigParameters['hide_page_comment'],
                $aConfigParameters['page_comment_content'],
                $aConfigParameters['page_comments_locked']
            ),
            'file' => array(
                $aConfigParameters['lock_file'],
                $aConfigParameters['download_type'],
                $aConfigParameters['locked_file_types'],
                $aConfigParameters['not_locked_file_types'],
                $aConfigParameters['file_pass_type']
            ),
            'author' => array(
                $aConfigParameters['authors_has_access_to_own'],
                $aConfigParameters['authors_can_add_posts_to_groups'],
                $aConfigParameters['full_access_role'],
            ),
            'other' => array(
                $aConfigParameters['lock_recursive'],
                $aConfigParameters['hide_empty_categories'],
                $aConfigParameters['protect_feed'],
                $aConfigParameters['blog_admin_hint'],
                $aConfigParameters['blog_admin_hint_text'],
            )
        );

        return$aGroupedConfigParameters;
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
            $this->_oUserAccessManager->deleteFileProtectionFiles();
        } else {
            $this->_oUserAccessManager->createFileProtection();
        }

        $this->_oWrapper->doAction('uam_update_options', $this->_oConfig);
        $this->_setUpdateMessage(TXT_UAM_UPDATE_SETTINGS);
    }
}