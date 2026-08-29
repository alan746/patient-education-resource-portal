<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$data = ['email' => '', 'password' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['_token'] ?? null)) {
        httpError(419, 'Invalid CSRF token.');
    }

    $result = validateLoginInput($_POST);
    $data = $result['data'];
    $errors = $result['errors'];

    if ($errors === []) {
        $user = findUserByEmail(db(), $data['email']);

        if (credentialsAreValid($user, $data['password'])) {
            loginUser($user);
            setFlash('Welcome back.');
            redirect('index.php');
        }

        $errors['credentials'] = 'Email or password is incorrect.';
    }
}

$pageTitle = 'Log in';

require __DIR__ . '/includes/header.php';
?>
<main class="container">
    <h1>Log in</h1>

    <?php if (isset($errors['credentials'])): ?>
        <p role="alert"><?= e($errors['credentials']) ?></p>
    <?php endif; ?>

    <form action="login.php" method="post">
        <input type="hidden" name="_token" value="<?= e(csrfToken()) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="<?= e($data['email']) ?>" autocomplete="email">
        <?php if (isset($errors['email'])): ?>
            <p role="alert"><?= e($errors['email']) ?></p>
        <?php endif; ?>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password">
        <?php if (isset($errors['password'])): ?>
            <p role="alert"><?= e($errors['password']) ?></p>
        <?php endif; ?>

        <button type="submit">Log in</button>
    </form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
