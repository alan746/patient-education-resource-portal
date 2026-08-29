<?php

declare(strict_types=1);

function listResources(PDO $pdo, string $search = ''): array
{
    $sql = 'SELECT resources.*, users.email AS creator_email
            FROM resources JOIN users ON users.id = resources.created_by';
    $params = [];

    if ($search !== '') {
        $sql .= ' WHERE resources.title LIKE :title
                  OR resources.description LIKE :description
                  OR resources.category LIKE :category';
        $term = '%' . $search . '%';
        $params = ['title' => $term, 'description' => $term, 'category' => $term];
    }

    $sql .= ' ORDER BY resources.created_at DESC, resources.id DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function findResource(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare('SELECT * FROM resources WHERE id = :id');
    $statement->execute(['id' => $id]);
    $resource = $statement->fetch();

    return $resource === false ? null : $resource;
}

function createResource(PDO $pdo, array $data, int $userId): int
{
    $statement = $pdo->prepare(
        'INSERT INTO resources (title, description, url, category, created_by)
         VALUES (:title, :description, :url, :category, :created_by)'
    );
    $statement->execute($data + ['created_by' => $userId]);

    return (int) $pdo->lastInsertId();
}

function updateResource(PDO $pdo, int $id, array $data): void
{
    $statement = $pdo->prepare(
        'UPDATE resources
         SET title = :title, description = :description, url = :url, category = :category
         WHERE id = :id'
    );
    $statement->execute($data + ['id' => $id]);
}

function deleteResource(PDO $pdo, int $id): void
{
    $statement = $pdo->prepare('DELETE FROM resources WHERE id = :id');
    $statement->execute(['id' => $id]);
}
