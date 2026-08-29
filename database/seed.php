<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

$pdo = db();
$email = 'demo@example.com';
$findUser = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$findUser->execute(['email' => $email]);

if ($findUser->fetch() === false) {
    $insertUser = $pdo->prepare(
        'INSERT INTO users (email, password_hash) VALUES (:email, :password_hash)'
    );
    $insertUser->execute([
        'email' => $email,
        'password_hash' => password_hash('password', PASSWORD_DEFAULT),
    ]);
}
