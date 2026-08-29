<?php

declare(strict_types=1);

function csrfToken(): string
{
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function isValidCsrfToken(?string $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $submittedToken);
}
