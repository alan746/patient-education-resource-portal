<?php

declare(strict_types=1);

test('escape helper encodes executable markup', function (): void {
    assertSameValue('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
});

test('search term is trimmed from the query string', function (): void {
    assertSameValue('diabetes', searchTerm(['q' => '  diabetes  ']));
});

test('flash message is consumed once', function (): void {
    $_SESSION = [];
    setFlash('Saved.');
    assertSameValue('Saved.', consumeFlash());
    assertSameValue(null, consumeFlash());
});
