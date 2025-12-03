<?php

namespace Giganteck\Opcean;

function get_opcean(string $optionId, string $fieldId, $default = '')
{
    $values = \get_option($optionId);

    if (is_array($values) && array_key_exists($fieldId, $values)) {
        return $values[$fieldId];
    }

    return $default;
}
