<?php

declare(strict_types=1);

function repositoryTestDatabase(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec(
        'CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
    $pdo->exec(
        'CREATE TABLE resources (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            url TEXT NOT NULL,
            category TEXT NOT NULL,
            created_by INTEGER NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )'
    );

    $userStatement = $pdo->prepare(
        'INSERT INTO users (email, password_hash) VALUES (:email, :password_hash)'
    );
    $userStatement->execute([
        'email' => 'owner@example.com',
        'password_hash' => 'owner-hash',
    ]);
    $userStatement->execute([
        'email' => 'other@example.com',
        'password_hash' => 'other-hash',
    ]);

    $resourceStatement = $pdo->prepare(
        'INSERT INTO resources (title, description, url, category, created_by, created_at)
         VALUES (:title, :description, :url, :category, :created_by, :created_at)'
    );
    $resourceStatement->execute([
        'title' => 'Diabetes Basics',
        'description' => 'An introduction to diabetes care.',
        'url' => 'https://example.com/diabetes',
        'category' => 'Chronic conditions',
        'created_by' => 1,
        'created_at' => '2026-01-02 00:00:00',
    ]);
    $resourceStatement->execute([
        'title' => 'Healthy Movement',
        'description' => 'Gentle activity guidance.',
        'url' => 'https://example.com/movement',
        'category' => 'Wellness',
        'created_by' => 2,
        'created_at' => '2026-01-01 00:00:00',
    ]);

    return $pdo;
}

function validResourceData(): array
{
    return [
        'title' => 'Heart Health Guide',
        'description' => 'Practical information about heart health.',
        'url' => 'https://example.com/heart-health',
        'category' => 'Cardiology',
    ];
}

test('resource repository lists and searches resources', function (): void {
    $pdo = repositoryTestDatabase();

    assertSameValue(2, count(listResources($pdo)));
    $matches = listResources($pdo, 'diabetes');
    assertSameValue(1, count($matches));
    assertSameValue('Diabetes Basics', $matches[0]['title']);
    assertSameValue('owner@example.com', $matches[0]['creator_email']);
});

test('resource repository lists categories and combines search with category filtering', function (): void {
    $pdo = repositoryTestDatabase();

    assertSameValue(['Chronic conditions', 'Wellness'], listResourceCategories($pdo));

    $matches = listResources($pdo, '', 'Wellness');
    assertSameValue(1, count($matches));
    assertSameValue('Healthy Movement', $matches[0]['title']);

    assertSameValue(1, count(listResources($pdo, 'gentle', 'Wellness')));
    assertSameValue(0, count(listResources($pdo, 'diabetes', 'Wellness')));
});

test('resource repository creates updates finds and deletes a resource', function (): void {
    $pdo = repositoryTestDatabase();

    $id = createResource($pdo, validResourceData(), 1);
    assertSameValue(1, (int) findResource($pdo, $id)['created_by']);
    updateResource($pdo, $id, [...validResourceData(), 'title' => 'Updated title']);
    assertSameValue('Updated title', findResource($pdo, $id)['title']);
    deleteResource($pdo, $id);
    assertSameValue(null, findResource($pdo, $id));
});

test('resource repository finds public resource details with creator email', function (): void {
    $pdo = repositoryTestDatabase();

    $resource = findPublicResource($pdo, 1);
    assertSameValue('Diabetes Basics', $resource['title']);
    assertSameValue('owner@example.com', $resource['creator_email']);
    assertSameValue(null, findPublicResource($pdo, 999));
});

test('user repository finds a user by email', function (): void {
    $pdo = repositoryTestDatabase();

    assertSameValue('owner@example.com', findUserByEmail($pdo, 'owner@example.com')['email']);
    assertSameValue(null, findUserByEmail($pdo, 'missing@example.com'));
});
