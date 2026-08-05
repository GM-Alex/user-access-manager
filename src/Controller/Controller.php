<?php

declare(strict_types=1);

namespace UserAccessManager\Controller;

use UserAccessManager\Config\WordpressConfig;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

abstract class Controller
{
    use BaseControllerTrait {
        render as traitRender;
    }

    public const ACTION_PARAMETER = 'uam_action';
    public const ACTION_SUFFIX = 'Action';
    public const UAM_ERRORS = 'UAM_ERRORS';

    protected ?string $updateMessage = null;

    public function __construct(
        protected Php $php,
        protected Wordpress $wordpress,
        protected WordpressConfig $wordpressConfig
    ) {
    }

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
        if (isset($_SESSION[self::UAM_ERRORS]) === false) {
            $_SESSION[self::UAM_ERRORS] = [];
        }

        $_SESSION[self::UAM_ERRORS][] = $message;
    }

    public function getUpdateMessage(): ?string
    {
        return $this->updateMessage;
    }

    public function hasUpdateMessage(): bool
    {
        return $this->updateMessage !== null;
    }

    private function toCamelCase(string $snakeCaseName): string
    {
        $words = explode('_', $snakeCaseName);
        return array_shift($words).implode('', array_map('ucfirst', $words));
    }

    protected function processAction(): void
    {
        $requestedAction = (string) $this->getRequestParameter(self::ACTION_PARAMETER);
        $actionMethod = $this->toCamelCase($requestedAction).self::ACTION_SUFFIX;

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
