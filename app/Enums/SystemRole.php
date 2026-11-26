<?php

declare(strict_types=1);

namespace App\Enums;

/** Roles the platform itself relies on. */
enum SystemRole: string
{
    case Admin = 'admin';
    case Client = 'client';
}
