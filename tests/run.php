<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$validationPath = __DIR__ . '/../includes/validation.php';
$viewPath = __DIR__ . '/../includes/view.php';

if (is_file($validationPath)) {
    require $validationPath;
}

if (is_file($viewPath)) {
    require $viewPath;
}

require __DIR__ . '/validation_test.php';
require __DIR__ . '/view_test.php';

finishTests();
