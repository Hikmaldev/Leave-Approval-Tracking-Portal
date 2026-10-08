<?php

namespace App\Enums;

enum ApprovalStep: string
{
    case Supervisor = 'supervisor';
    case Hr = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::Supervisor => 'Supervisor',
            self::Hr => 'HR',
        };
    }
}
