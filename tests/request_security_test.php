<?php

declare(strict_types=1);

test('delete request is accepted only for POST', function (): void {
    assertSameValue(false, isMutationMethodAllowed('GET'));
    assertSameValue(true, isMutationMethodAllowed('POST'));
});
