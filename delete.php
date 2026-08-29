<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

if (!isMutationMethodAllowed($_SERVER['REQUEST_METHOD'])) {
    httpError(405, 'Method not allowed.');
}
requireLogin();
if (!isValidCsrfToken($_POST['_token'] ?? null)) {
    httpError(419, 'Invalid CSRF token.');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    httpError(404, 'Resource not found.');
}
$resource = findResource(db(), $id);
if ($resource === null) {
    httpError(404, 'Resource not found.');
}
if (!resourceBelongsToCurrentUser($resource)) {
    httpError(403, 'You cannot delete this resource.');
}
deleteResource(db(), $id);
setFlash('Resource deleted.');
redirect('index.php');
