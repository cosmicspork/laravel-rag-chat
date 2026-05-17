<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for chat feedback.
 * If modifying this, ensure to update the JavaScript counterpart.
 */
enum ChatFeedbackEnum: string
{
    case Good = 'good';
    case Bad = 'bad';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
