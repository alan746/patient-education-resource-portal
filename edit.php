<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    httpError(404, 'Resource not found.');
}

$resource = findResource(db(), $id);
if ($resource === null) {
    httpError(404, 'Resource not found.');
}
if (!resourceBelongsToCurrentUser($resource)) {
    httpError(403, 'You cannot edit this resource.');
}

$data = [
    'title' => (string) $resource['title'],
    'description' => (string) $resource['description'],
    'url' => (string) $resource['url'],
    'category' => (string) $resource['category'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['_token'] ?? null)) {
        httpError(419, 'Invalid CSRF token.');
    }

    $result = validateResourceInput($_POST);
    $data = $result['data'];
    $errors = $result['errors'];

    if ($errors === []) {
        $resource = findResource(db(), $id);
        if ($resource === null) {
            httpError(404, 'Resource not found.');
        }
        if (!resourceBelongsToCurrentUser($resource)) {
            httpError(403, 'You cannot edit this resource.');
        }
        updateResource(db(), $id, $data);
        setFlash('Resource updated.');
        redirect('index.php');
    }
}

$pageTitle = 'Edit resource';
$formTitle = 'Edit resource';
$formAction = 'edit.php?id=' . rawurlencode((string) $id);
$submitLabel = 'Update resource';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/resource_form.php';
require __DIR__ . '/includes/footer.php';
