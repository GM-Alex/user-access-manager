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
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */

declare(strict_types=1);

namespace UserAccessManager\Controller;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Controller\Backend\BackendController;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class Controller
 *
 * @package UserAccessManager\Controller
 */
abstract class Controller
{
    use BaseControllerTrait {
        render as traitRender;
    }

    public const ACTION_PARAMETER = 'uam_action';
    public const ACTION_SUFFIX = 'Action';

    protected ?string $updateMessage = null;

    public function __construct(
        protected Php $php,
        protected Wordpress $wordpress,
        protected WordpressConfig $wordpressConfig
    ) {}

    protected function getPhp(): Php
    {
        return $this->php;
    }

    protected function getWordpressConfig(): WordpressConfig
    {
        return $this->wordpressConfig;
    }

    public function createNonceField(string $name): string
    {
        return $this->wordpress->getNonceField($name, $name.'Nonce');
    }

    public function getNonce(string $name): string
    {
        return $this->wordpress->createNonce($name);
    }

    protected function verifyNonce(string $name): void
    {
        $nonce = $this->getRequestParameter($name.'Nonce');

        if ($this->wordpress->verifyNonce($nonce, $name) === false) {
            $this->wordpress->wpDie(TXT_UAM_NONCE_FAILURE_MESSAGE, TXT_UAM_NONCE_FAILURE_TITLE, ['response' => 401]);
        }
    }

    protected function setUpdateMessage(string $message): void
    {
        $this->updateMessage = $message;
    }

    protected function addErrorMessage(string $message): void
    {
        if (isset($_SESSION[BackendController::UAM_ERRORS]) === false) {
            $_SESSION[BackendController::UAM_ERRORS] = [];
        }

        $_SESSION[BackendController::UAM_ERRORS][] = $message;
    }

    public function getUpdateMessage(): ?string
    {
        return $this->updateMessage;
    }

    public function hasUpdateMessage(): bool
    {
        return $this->updateMessage !== null;
    }

    protected function processAction(): void
    {
        $postAction = (string) $this->getRequestParameter(self::ACTION_PARAMETER);
        $postActionSplit = explode('_', $postAction);
        $postAction = array_shift($postActionSplit);
        $postAction .= implode('', array_map('ucfirst', $postActionSplit));
        $actionMethod = $postAction.self::ACTION_SUFFIX;

        if (method_exists($this, $actionMethod) === true) {
            $this->{$actionMethod}();
        }
    }

    public function render(): void
    {
        $this->processAction();
        $this->traitRender();
    }
}
