<?php

declare(strict_types=1);

test('search term is trimmed from q', function (): void {
    assertSameValue('diabetes', searchTerm(['q' => '  diabetes  ']));
    assertSameValue('', searchTerm([]));
});

test('flash message is consumed once', function (): void {
    $_SESSION = [];
    setFlash('Saved.');
    assertSameValue('Saved.', consumeFlash());
    assertSameValue(null, consumeFlash());
});
