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
        return $needle === '' || str_starts_with($haystack, $needle);
    }

    public function endsWith(string $haystack, string $needle): bool
    {
        return $needle === '' || str_ends_with($haystack, $needle);
    }

    /**
     * @throws Exception
     */
    public function getRandomPassword(int $length = 32): string
    {
        $bytes = $this->php->opensslRandomPseudoBytes($length + 1, $strong);

        if ($bytes !== false && $strong === true) {
            return substr(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode($bytes)), 0, $length);
        }

        throw new Exception('Unable to generate secure token from OpenSSL.');
    }

    public function getCurrentUrl(): string
    {
        if (isset($_SERVER['REQUEST_URI']) === false) {
            $serverRequestUri = $_SERVER['PHP_SELF'];
        } else {
            $serverRequestUri = $_SERVER['REQUEST_URI'];
        }

        $https = $_SERVER['HTTPS'] ?? '';
        $secure = $https === 'on' ? 's' : '';
        $protocols = explode('/', strtolower($_SERVER['SERVER_PROTOCOL']));
        $protocol = $protocols[0] . $secure;
        $port = ((int) $_SERVER['SERVER_PORT'] === 80) ? '' : (':' . $_SERVER['SERVER_PORT']);

        return $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . $serverRequestUri;
    }
}
