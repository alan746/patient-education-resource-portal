<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Patient Education Resources';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="container site-header__inner">
        <a class="site-header__brand" href="index.php">Patient Education Resources</a>
        <nav class="site-header__nav" aria-label="Account">
            <?php if (isLoggedIn()): ?>
                <a href="create.php">Create resource</a>
                <span>Signed in as <?= e((string) ($_SESSION['email'] ?? '')) ?></span>
                <form action="logout.php" method="post">
                    <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                    <button type="submit">Log out</button>
                </form>
            <?php else: ?>
                <a href="login.php">Log in</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
