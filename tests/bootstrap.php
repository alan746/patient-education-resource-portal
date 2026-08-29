<?php

declare(strict_types=1);

$tests = [];
$failures = [];

function test(string $name, callable $callback): void
{
    global $tests;

    $tests[] = ['name' => $name, 'callback' => $callback];
}

function assertSameValue(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.'
        );
    }
}

function finishTests(): never
{
    global $tests, $failures;

    foreach ($tests as $test) {
        try {
            $test['callback']();
            fwrite(STDOUT, "PASS: {$test['name']}\n");
        } catch (Throwable $exception) {
            $failures[] = "FAIL: {$test['name']}\n{$exception->getMessage()}";
        }
    }

    if ($failures !== []) {
        fwrite(STDERR, implode("\n", $failures) . "\n");
        exit(1);
    }

    fwrite(STDOUT, count($tests) . " tests passed.\n");
    exit(0);
}
