<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    httpError(404, 'Resource not found.');
}

$resource = findPublicResource(db(), $id);
if ($resource === null) {
    httpError(404, 'Resource not found.');
}

$pageTitle = (string) $resource['title'];

require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <p><a href="index.php">Back to resources</a></p>

    <article class="resource-card resource-detail">
        <h1><?= e((string) $resource['title']) ?></h1>
        <p>Category: <?= e((string) $resource['category']) ?></p>
        <p><?= e((string) $resource['description']) ?></p>
        <p><a href="<?= e((string) $resource['url']) ?>">Visit resource</a></p>
        <p class="resource-meta">
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
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
