<?php

declare(strict_types=1);

function findUserByEmail(PDO $pdo, string $email): ?array
{
    $statement = $pdo->prepare(
        'SELECT id, email, password_hash, created_at FROM users WHERE email = :email'
    );
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    return $user === false ? null : $user;
}
