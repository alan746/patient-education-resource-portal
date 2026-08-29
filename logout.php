<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    httpError(405, 'Method not allowed.');
}

if (!isValidCsrfToken($_POST['_token'] ?? null)) {
    httpError(419, 'Invalid CSRF token.');
}

logoutUser();
redirect('index.php');
