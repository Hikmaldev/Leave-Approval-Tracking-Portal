<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Supervisor = 'supervisor';
    case HrAdmin = 'hr_admin';

    /**
     * Plain-language label for the UI (never the raw enum value, per PRD 9.4).
     */
    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Employee',
            self::Supervisor => 'Supervisor',
            self::HrAdmin => 'HR/Admin',
        };
    }
}
