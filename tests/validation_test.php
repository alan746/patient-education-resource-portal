<?php

declare(strict_types=1);

test('valid resource input is normalized without errors', function (): void {
    $result = validateResourceInput([
        'title' => '  Diabetes Basics  ',
        'description' => '  Plain-language guide.  ',
        'url' => ' https://example.org/diabetes ',
        'category' => ' General ',
    ]);
    assertSameValue([], $result['errors']);
    assertSameValue('Diabetes Basics', $result['data']['title']);
});

test('resource validation rejects missing fields and unsafe URL schemes', function (): void {
    $result = validateResourceInput([
        'title' => '', 'description' => '',
        'url' => 'javascript:alert(1)', 'category' => '',
    ]);
    assertSameValue('Title is required.', $result['errors']['title']);
    assertSameValue('Enter a valid HTTP or HTTPS URL.', $result['errors']['url']);
    assertSameValue('Category is required.', $result['errors']['category']);
});

test('login validation rejects malformed email and blank password', function (): void {
    $result = validateLoginInput(['email' => 'invalid', 'password' => '']);
    assertSameValue('Enter a valid email address.', $result['errors']['email']);
    assertSameValue('Password is required.', $result['errors']['password']);
});
