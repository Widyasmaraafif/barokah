<?php

namespace App\Enums;

/**
 * @method static string value()
 */
enum SettingType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Json = 'json';
    case Color = 'color';
    case Image = 'image';
}
