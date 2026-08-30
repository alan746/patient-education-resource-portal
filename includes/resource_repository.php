<?php

declare(strict_types=1);

function listResources(PDO $pdo, string $search = '', string $category = ''): array
{
    $sql = 'SELECT resources.*, users.email AS creator_email
            FROM resources JOIN users ON users.id = resources.created_by';
    $params = [];
    $conditions = [];

    if ($search !== '') {
        $conditions[] = '(resources.title LIKE :search_title
                          OR resources.description LIKE :search_description
                          OR resources.category LIKE :search_category)';
        $term = '%' . $search . '%';
        $params += [
            'search_title' => $term,
            'search_description' => $term,
            'search_category' => $term,
        ];
    }

    if ($category !== '') {
        $conditions[] = 'resources.category = :filter_category';
        $params['filter_category'] = $category;
    }

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY resources.created_at DESC, resources.id DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function listResourceCategories(PDO $pdo): array
{
    $statement = $pdo->prepare(
        'SELECT DISTINCT category FROM resources ORDER BY category ASC'
    );
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_COLUMN);
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
