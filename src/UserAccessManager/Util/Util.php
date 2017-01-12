<?php
namespace UserAccessManager\Util;


class Util
{
    /**
     * Checks if a string starts with the given needle.
     *
     * @param string $sHaystack The haystack.
     * @param string $sNeedle   The needle.
     *
     * @return boolean
     */
    public function startsWith($sHaystack, $sNeedle)
    {
        return $sNeedle === '' || strpos($sHaystack, $sNeedle) === 0;
    }

    /**
     * Checks if a string ends with the given needle.
     *
     * @param string $sHaystack
     * @param string $sNeedle
     *
     * @return bool
     */
    public function endsWith($sHaystack, $sNeedle)
    {
        return $sNeedle === '' || substr($sHaystack, -strlen($sNeedle)) === $sNeedle;
    }
}