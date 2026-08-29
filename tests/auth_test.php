<?php

declare(strict_types=1);

test('authentication state comes from the numeric session user id', function (): void {
    $_SESSION = [];
    assertSameValue(null, currentUserId());
    $_SESSION['user_id'] = 7;
    assertSameValue(7, currentUserId());
});

test('ownership compares session user id with created_by', function (): void {
    $_SESSION = ['user_id' => 7];
    assertSameValue(true, resourceBelongsToCurrentUser(['created_by' => '7']));
    assertSameValue(false, resourceBelongsToCurrentUser(['created_by' => '8']));
});
