<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function searchTerm(array $query): string
{
    return trim((string) ($query['q'] ?? ''));
}

function setFlash(string $message): void
{
    $_SESSION['_flash'] = $message;
}

function consumeFlash(): ?string
{
    if (!isset($_SESSION['_flash'])) {
        return null;
    }

    $message = $_SESSION['_flash'];
    unset($_SESSION['_flash']);

    return $message;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function httpError(int $status, string $message): never
{
    http_response_code($status);
    echo e($message);
    exit;
}
