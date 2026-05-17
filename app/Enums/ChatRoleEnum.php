<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for chat roles.
 * If modifying this, ensure to update the JavaScript counterpart.
 */
enum ChatRoleEnum: string
{
    case System = 'system'; 
    case User = 'user'; 
    case Assistant = 'assistant';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
