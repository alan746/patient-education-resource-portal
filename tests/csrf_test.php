<?php

declare(strict_types=1);

test('csrf token verifies only the stored token', function (): void {
    $_SESSION = [];
    $token = csrfToken();
    assertSameValue(true, isValidCsrfToken($token));
    assertSameValue(false, isValidCsrfToken('wrong-token'));
    assertSameValue(false, isValidCsrfToken(null));
});
