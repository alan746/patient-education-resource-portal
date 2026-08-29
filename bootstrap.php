<?php

declare(strict_types=1);

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/validation.php';
require __DIR__ . '/includes/view.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/includes/user_repository.php';
require __DIR__ . '/includes/resource_repository.php';
