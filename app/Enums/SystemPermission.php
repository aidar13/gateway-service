<?php

declare(strict_types=1);

namespace App\Enums;

/** Permissions guarding the admin API. */
enum SystemPermission: string
{
    case ServicesManage = 'services.manage';
    case AccessManage = 'access.manage';
    case ScopesManage = 'scopes.manage';
    case ClientsManage = 'clients.manage';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
