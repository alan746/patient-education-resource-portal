<?php

declare(strict_types=1);
?>
<main class="container">
    <h1><?= e($formTitle) ?></h1>

    <form action="<?= e($formAction) ?>" method="post">
        <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">

        <label for="title">Title</label>
        <input id="title" name="title" type="text" value="<?= e($data['title']) ?>">
        <?php if (isset($errors['title'])): ?>
            <p role="alert"><?= e($errors['title']) ?></p>
        <?php endif; ?>

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= e($data['description']) ?></textarea>

        <label for="url">URL</label>
        <input id="url" name="url" type="url" value="<?= e($data['url']) ?>">
        <?php if (isset($errors['url'])): ?>
            <p role="alert"><?= e($errors['url']) ?></p>
        <?php endif; ?>

        <label for="category">Category</label>
        <input id="category" name="category" type="text" value="<?= e($data['category']) ?>">
        <?php if (isset($errors['category'])): ?>
            <p role="alert"><?= e($errors['category']) ?></p>
        <?php endif; ?>

        <button type="submit"><?= e($submitLabel) ?></button>
    </form>
</main>
