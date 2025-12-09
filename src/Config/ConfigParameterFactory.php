<?php

declare(strict_types=1);

namespace UserAccessManager\Config;

use Exception;

class ConfigParameterFactory
{
    /**
     * @throws Exception
     */
    public function createBooleanConfigParameter(string $id, mixed $defaultValue = false): BooleanConfigParameter
    {
        return new BooleanConfigParameter($id, $defaultValue);
    }

    /**
     * @throws Exception
     */
    public function createSelectionConfigParameter(
        string $id,
        mixed  $defaultValue,
        array  $selection
    ): SelectionConfigParameter {
        return new SelectionConfigParameter($id, $defaultValue, $selection);
    }

    /**
     * @throws Exception
     */
    public function createStringConfigParameter(string $id, mixed $defaultValue = ''): StringConfigParameter
    {
        return new StringConfigParameter($id, $defaultValue);
    }
}
