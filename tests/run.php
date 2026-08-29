<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/../bootstrap.php';

require __DIR__ . '/validation_test.php';
require __DIR__ . '/view_test.php';
require __DIR__ . '/auth_test.php';
require __DIR__ . '/login_test.php';
require __DIR__ . '/csrf_test.php';
require __DIR__ . '/repository_test.php';

finishTests();
