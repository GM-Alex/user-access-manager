<?php
/**
 * Controller.php
 *
 * The Controller class file.
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

use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Controller
 *
 * @package UserAccessManager\Controller
 */
abstract class Controller
{
    const ACTION_PARAMETER = 'uam_action';
    const ACTION_SUFFIX = 'Action';

    /**
     * @var Wordpress
     */
    protected $_oWrapper;

    /**
     * @var string
     */
    protected $_sTemplate = null;

    /**
     * @var string
     */
    protected $_sUpdateMessage = null;

    /**
     * Controller constructor.
     *
     * @param Wordpress $oWrapper
     */
    public function __construct(Wordpress $oWrapper)
    {
        $this->_oWrapper = $oWrapper;
    }

    /**
     * Process the action.
     */
    protected function _processAction()
    {
        $sPostAction = $this->getRequestParameter(self::ACTION_PARAMETER);
        $aPostAction = explode('_', $sPostAction);
        $sPostAction = array_shift($aPostAction);
        $sPostAction .= implode('', array_map('ucfirst', $aPostAction));
        $sActionMethod = $sPostAction.self::ACTION_SUFFIX;

        if (method_exists($this, $sActionMethod)) {
            $this->{$sActionMethod}();
        }
    }

    /**
     * Returns the content of the excluded php file.
     *
     * @param string $sFileName The view file name
     *
     * @return string
     */
    protected function _getIncludeContents($sFileName)
    {
        $sContents = '';
        $aPath = array(UAM_REALPATH, 'src', 'UserAccessManager', 'View');
        $sPath = implode(DIRECTORY_SEPARATOR, $aPath).DIRECTORY_SEPARATOR;
        $sFileWithPath = $sPath.$sFileName;

        if (is_file($sFileWithPath)) {
            ob_start();
            include $sFileWithPath;
            $sContents = ob_get_contents();
            ob_end_clean();
        }

        return $sContents;
    }

    /**
     * Renders the given template
     */
    public function render()
    {
        $this->_processAction();

        if ($this->_sTemplate !== null) {
            echo $this->_getIncludeContents($this->_sTemplate);
        }
    }

    /**
     * Returns the current request url.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * Returns the request parameter.
     *
     * @param string $sName
     * @param mixed  $mDefault
     *
     * @return mixed
     */
    public function getRequestParameter($sName, $mDefault = null)
    {
        $mReturn = isset($_POST[$sName]) ? $_POST[$sName] : null;

        if ($mReturn === null) {
            $mReturn = isset($_GET[$sName]) ? $_GET[$sName] : $mDefault;
        }

        return $mReturn;
    }

    /**
     * Returns the nonce field.
     *
     * @param string $sName
     *
     * @return string
     */
    public function createNonceField($sName)
    {
        return $this->_oWrapper->getNonceField($sName, $sName.'Nonce');
    }

    /**
     * Returns the nonce.
     *
     * @param string $sName
     *
     * @return string
     */
    public function getNonce($sName)
    {
        return $this->_oWrapper->createNonce($sName);
    }

    /**
     * Verifies the nonce and terminates the application if the nonce is wrong.
     *
     * @param $sName
     */
    protected function _verifyNonce($sName)
    {
        $sNonce = $this->getRequestParameter($sName.'Nonce');

        if ($this->_oWrapper->verifyNonce($sNonce, $sName) === false) {
            $this->_oWrapper->wpDie(TXT_UAM_NONCE_FAILURE);
        }
    }

    /**
     * Sets the update message.
     *
     * @param $sMessage
     */
    protected function _setUpdateMessage($sMessage)
    {
        $this->_sUpdateMessage = $sMessage;
    }

    /**
     * Returns the update message.
     *
     * @return string
     */
    public function getUpdateMessage()
    {
        return $this->_sUpdateMessage;
    }

    /**
     * Returns true if a update message is set.
     *
     * @return bool
     */
    public function hasUpdateMessage()
    {
        return $this->_sUpdateMessage !== null;
    }
}