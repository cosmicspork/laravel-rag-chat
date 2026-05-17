<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for chat actions. 
 * If modifying this, ensure to update the JavaScript counterpart.
 */
enum ChatActionEnum: string
{
    case Completion = 'completion';
    case Regeneration = 'regeneration';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
