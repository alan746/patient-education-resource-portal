<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$search = searchTerm($_GET);
$resources = listResources(db(), $search);
$flash = consumeFlash();
$pageTitle = 'Patient Education Resources';

require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <h1>Patient Education Resources</h1>

    <?php if ($flash !== null): ?>
        <p role="status"><?= e($flash) ?></p>
    <?php endif; ?>

    <form class="search-form" action="index.php" method="get">
        <label for="q">Search resources</label>
        <input id="q" name="q" type="search" value="<?= e($search) ?>">
        <button type="submit">Search</button>
    </form>

    <?php if ($resources === []): ?>
        <p>No resources match your search.</p>
    <?php else: ?>
        <section aria-label="Resources">
            <?php foreach ($resources as $resource): ?>
                <article class="resource-card">
                    <h2><?= e((string) $resource['title']) ?></h2>
                    <p>Category: <?= e((string) $resource['category']) ?></p>
                    <p><?= e((string) $resource['description']) ?></p>
                    <p><a href="<?= e((string) $resource['url']) ?>">Visit resource</a></p>
                    <p>
                        Added by <?= e((string) $resource['creator_email']) ?>
                        on <?= e((string) $resource['created_at']) ?>.
                        Last updated <?= e((string) $resource['updated_at']) ?>.
                    </p>

                    <?php if (resourceBelongsToCurrentUser($resource)): ?>
                        <div class="resource-actions">
                            <a href="edit.php?id=<?= e((string) $resource['id']) ?>">Edit</a>
                            <form action="delete.php" method="post">
                                <input type="hidden" name="id" value="<?= e((string) $resource['id']) ?>">
                                <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
