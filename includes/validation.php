<?php

declare(strict_types=1);

function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function normalizeResourceInput(array $input): array
{
    return [
        'title' => trim((string) ($input['title'] ?? '')),
        'description' => trim((string) ($input['description'] ?? '')),
        'url' => trim((string) ($input['url'] ?? '')),
        'category' => trim((string) ($input['category'] ?? '')),
    ];
}

function validateResourceInput(array $input): array
{
    $data = normalizeResourceInput($input);
    $errors = [];

    if ($data['title'] === '') {
        $errors['title'] = 'Title is required.';
    } elseif (textLength($data['title']) > 255) {
        $errors['title'] = 'Title must be 255 characters or fewer.';
    }

    if ($data['category'] === '') {
        $errors['category'] = 'Category is required.';
    } elseif (textLength($data['category']) > 100) {
        $errors['category'] = 'Category must be 100 characters or fewer.';
    }

    $scheme = strtolower((string) parse_url($data['url'], PHP_URL_SCHEME));
    if (textLength($data['url']) > 2048 || filter_var($data['url'], FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true)) {
        $errors['url'] = 'Enter a valid HTTP or HTTPS URL.';
    }

    return ['data' => $data, 'errors' => $errors];
}

function validateLoginInput(array $input): array
{
    $data = [
        'email' => strtolower(trim((string) ($input['email'] ?? ''))),
        'password' => (string) ($input['password'] ?? ''),
    ];
    $errors = [];

    if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($data['password'] === '') {
        $errors['password'] = 'Password is required.';
    }

    return ['data' => $data, 'errors' => $errors];
}
