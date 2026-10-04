<?php

declare(strict_types=1);

namespace App;

/** Central permission map. */
final class Rbac
{
    private const MAP = [
        'record.view'   => ['admin', 'verifikator', 'staff'],
        'record.import' => ['admin', 'staff'],
        'record.verify' => ['admin', 'verifikator'],
        'record.reject' => ['admin', 'verifikator'],
        'record.export' => ['admin', 'verifikator', 'staff'],
        'audit.view'    => ['admin', 'verifikator'],
    ];

    public static function allows(string $role, string $permission): bool
    {
        return in_array($role, self::MAP[$permission] ?? [], true);
    }
}
