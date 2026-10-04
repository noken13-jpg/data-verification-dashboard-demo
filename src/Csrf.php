<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/** Session-based CSRF protection (per-session token, constant-time compare). */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    public static function check(?string $token): void
    {
        $expected = $_SESSION['csrf_token'] ?? '';
        if ($expected === '' || $token === null || !hash_equals($expected, $token)) {
            http_response_code(419);
            throw new RuntimeException('Invalid or missing CSRF token.');
        }
    }
}
