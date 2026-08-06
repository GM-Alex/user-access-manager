<?php

declare(strict_types=1);

namespace UserAccessManager\Util;

use Exception;
use UserAccessManager\Wrapper\Php;

class Util
{
    public function __construct(private Php $php)
    {
    }

    public function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    public function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * @throws Exception
     */
    public function getRandomPassword(int $length = 32): string
    {
        $bytes = $this->php->opensslRandomPseudoBytes($length + 1, $strong);

        if ($bytes === false || $strong !== true) {
            throw new Exception('Unable to generate secure token from OpenSSL.');
        }

        return substr(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode($bytes)), 0, $length);
    }

    public function getCurrentUrl(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'];
        $secure = (($_SERVER['HTTPS'] ?? '') === 'on') ? 's' : '';
        $scheme = explode('/', strtolower($_SERVER['SERVER_PROTOCOL']))[0] . $secure;
        $port = ((int) $_SERVER['SERVER_PORT'] === 80) ? '' : ':' . $_SERVER['SERVER_PORT'];

        return $scheme . '://' . $_SERVER['SERVER_NAME'] . $port . $requestUri;
    }
}
