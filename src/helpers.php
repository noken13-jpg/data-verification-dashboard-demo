<?php

declare(strict_types=1);

/** Read an environment variable with an optional default. */
function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
}

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function view(string $name, array $data = []): string
{
    $file = dirname(__DIR__) . '/views/' . $name . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View not found: {$name}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

/** Label + CSS class for a verification status. */
function status_badge(string $status): array
{
    return match ($status) {
        'pending'  => ['Pending', 'badge-amber'],
        'verified' => ['Verified', 'badge-green'],
        'rejected' => ['Rejected', 'badge-red'],
        default    => [ucfirst($status), 'badge-gray'],
    };
}
