<?php

declare(strict_types=1);

function currentUserId(): ?int
{
    $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : $id;
}

function isLoggedIn(): bool
{
    return currentUserId() !== null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function resourceBelongsToCurrentUser(array $resource): bool
{
    return currentUserId() !== null && currentUserId() === (int) ($resource['created_by'] ?? 0);
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['email'] = (string) ($user['email'] ?? '');
}

function logoutUser(): void
{
    $_SESSION = [];

    if ((bool) ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parameters['path'],
            'domain' => $parameters['domain'],
            'secure' => $parameters['secure'],
            'httponly' => $parameters['httponly'],
            'samesite' => $parameters['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
