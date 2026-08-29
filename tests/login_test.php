<?php

declare(strict_types=1);

test('credentials verify a password_hash value', function (): void {
    $user = ['password_hash' => password_hash('password', PASSWORD_DEFAULT)];
    assertSameValue(true, credentialsAreValid($user, 'password'));
    assertSameValue(false, credentialsAreValid($user, 'wrong'));
    assertSameValue(false, credentialsAreValid(null, 'password'));
});
