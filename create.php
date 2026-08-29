<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

requireLogin();

$data = ['title' => '', 'description' => '', 'url' => '', 'category' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['_token'] ?? null)) {
        httpError(419, 'Invalid CSRF token.');
    }

    $result = validateResourceInput($_POST);
    $data = $result['data'];
    $errors = $result['errors'];

    if ($errors === []) {
        createResource(db(), $data, currentUserId());
        setFlash('Resource created.');
        redirect('index.php');
    }
}

$pageTitle = 'Create resource';
$formTitle = 'Create resource';
$formAction = 'create.php';
$submitLabel = 'Create resource';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/resource_form.php';
require __DIR__ . '/includes/footer.php';
