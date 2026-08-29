# Patient Education Resource Portal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a minimal server-rendered PHP/MySQL portal where anyone can browse and search patient education resources and a seeded demo user can manage only their own resources.

**Architecture:** Use one PHP entry-point file per browser action, small shared function files, and PDO repositories. Keep authentication and ownership in server-side helpers, use POST plus CSRF for every mutation, and render escaped HTML directly without a framework.

**Tech Stack:** PHP 8.1+, PDO MySQL, MySQL 8.0+, PHP sessions, HTML5, CSS3, native PHP test runner

**Spec:** `docs/superpowers/specs/2026-08-28-patient-education-resource-portal-design.md`

## Global Constraints

- Do not add registration, roles, tags, likes, comments, AI, AWS, Docker, frameworks, APIs, or distributed services.
- Use the exact `users` and `resources` columns from the specification.
- Use `PDO::prepare()` plus `PDOStatement::execute()` for every application SQL statement.
- Store only the result of `password_hash()` for the seeded demo password.
- Use PHP sessions for login state.
- Run edit and delete owner authorization on the server by comparing `$_SESSION['user_id']` with `resources.created_by`.
- Accept deletion through POST only and verify its CSRF token before deleting.
- Use server-side validation and escape all dynamic HTML output.
- Keep the interface responsive with one small stylesheet.
- Follow `issue-<issue number>-<name>-<layer>-<module>` for branches and `<type>(<scope>): <description>` for commits.

## File Map

- `bootstrap.php`: start the session safely and load shared functions.
- `config/database.php`: expose the single `db(): PDO` connection factory.
- `includes/auth.php`: login-state and owner-authorization decisions.
- `includes/csrf.php`: CSRF token generation and verification.
- `includes/resource_repository.php`: all resource database queries.
- `includes/user_repository.php`: demo-user lookup.
- `includes/validation.php`: normalize and validate login/resource form data.
- `includes/view.php`: escaping, flash messages, redirects, HTTP errors, and old-input access.
- `includes/header.php`, `includes/footer.php`, `includes/resource_form.php`: shared HTML fragments.
- `index.php`, `login.php`, `logout.php`, `create.php`, `edit.php`, `delete.php`: browser entry points.
- `database/schema.sql`, `database/seed.php`: MySQL schema and idempotent demo seed.
- `assets/style.css`: all responsive styling.
- `tests/bootstrap.php`, `tests/*_test.php`, `tests/run.php`: dependency-free automated tests.
- `README.md`: setup, credentials, commands, architecture, security, and interview walkthrough.

---

### Task 1: Native test harness, validation, and safe output

**Files:**
- Create: `tests/bootstrap.php`
- Create: `tests/validation_test.php`
- Create: `tests/view_test.php`
- Create: `tests/run.php`
- Create: `includes/validation.php`
- Create: `includes/view.php`

**Interfaces:**
- Produces: `normalizeResourceInput(array $input): array{title:string,description:string,url:string,category:string}`
- Produces: `validateResourceInput(array $input): array{data:array,errors:array<string,string>}`
- Produces: `validateLoginInput(array $input): array{data:array{email:string,password:string},errors:array<string,string>}`
- Produces: `e(?string $value): string`
- Produces: `searchTerm(array $query): string`, `setFlash(string $message): void`, `consumeFlash(): ?string`, `redirect(string $path): never`, and `httpError(int $status, string $message): never`
- Produces: native test helpers `test(string $name, callable $callback): void`, `assertSameValue(mixed $expected, mixed $actual): void`, and `finishTests(): never`

- [ ] **Step 1: Write failing validation and escaping tests**

```php
test('valid resource input is normalized without errors', function (): void {
    $result = validateResourceInput([
        'title' => '  Diabetes Basics  ',
        'description' => '  Plain-language guide.  ',
        'url' => ' https://example.org/diabetes ',
        'category' => ' General ',
    ]);
    assertSameValue([], $result['errors']);
    assertSameValue('Diabetes Basics', $result['data']['title']);
});

test('resource validation rejects missing fields and unsafe URL schemes', function (): void {
    $result = validateResourceInput([
        'title' => '', 'description' => '',
        'url' => 'javascript:alert(1)', 'category' => '',
    ]);
    assertSameValue('Title is required.', $result['errors']['title']);
    assertSameValue('Enter a valid HTTP or HTTPS URL.', $result['errors']['url']);
    assertSameValue('Category is required.', $result['errors']['category']);
});

test('login validation rejects malformed email and blank password', function (): void {
    $result = validateLoginInput(['email' => 'invalid', 'password' => '']);
    assertSameValue('Enter a valid email address.', $result['errors']['email']);
    assertSameValue('Password is required.', $result['errors']['password']);
});

test('escape helper encodes executable markup', function (): void {
    assertSameValue('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
});
```

- [ ] **Step 2: Run the tests and verify RED**

Run: `php tests/run.php`

Expected: FAIL because the validation and escaping functions do not exist.

- [ ] **Step 3: Implement the minimal validation and view helpers**

Implement the helpers with these concrete rules:

```php
function textLength(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function normalizeResourceInput(array $input): array
{
    return [
        'title' => trim((string) ($input['title'] ?? '')),
        'description' => trim((string) ($input['description'] ?? '')),
        'url' => trim((string) ($input['url'] ?? '')),
        'category' => trim((string) ($input['category'] ?? '')),
    ];
}

function validateResourceInput(array $input): array
{
    $data = normalizeResourceInput($input);
    $errors = [];
    if ($data['title'] === '') $errors['title'] = 'Title is required.';
    elseif (textLength($data['title']) > 255) $errors['title'] = 'Title must be 255 characters or fewer.';
    if ($data['category'] === '') $errors['category'] = 'Category is required.';
    elseif (textLength($data['category']) > 100) $errors['category'] = 'Category must be 100 characters or fewer.';
    $scheme = strtolower((string) parse_url($data['url'], PHP_URL_SCHEME));
    if (textLength($data['url']) > 2048 || filter_var($data['url'], FILTER_VALIDATE_URL) === false || !in_array($scheme, ['http', 'https'], true)) {
        $errors['url'] = 'Enter a valid HTTP or HTTPS URL.';
    }
    return ['data' => $data, 'errors' => $errors];
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

`validateLoginInput()` trims/lowercases email, preserves the password exactly, applies `FILTER_VALIDATE_EMAIL`, and requires a non-empty password. `searchTerm()` trims `q`. Flash helpers store one string in `$_SESSION['_flash']`; `consumeFlash()` removes it. `redirect()` sends `Location` and exits. `httpError()` sets the status code, renders an escaped message, and exits.

- [ ] **Step 4: Run the tests and verify GREEN**

Run: `php tests/run.php`

Expected: all validation and view tests pass with exit code 0.

- [ ] **Step 5: Commit**

```bash
git add tests includes/validation.php includes/view.php
git commit -m "feat(validation): Add form validation and safe output"
```

---

### Task 2: Session authentication, CSRF, and ownership

**Files:**
- Create: `tests/auth_test.php`
- Create: `tests/csrf_test.php`
- Create: `includes/auth.php`
- Create: `includes/csrf.php`
- Create: `bootstrap.php`
- Modify: `tests/run.php`

**Interfaces:**
- Produces: `currentUserId(): ?int`, `isLoggedIn(): bool`, `requireLogin(): void`, `resourceBelongsToCurrentUser(array $resource): bool`
- Produces: `csrfToken(): string`, `isValidCsrfToken(?string $submittedToken): bool`
- Produces: `loginUser(array $user): void`, `logoutUser(): void`
- Consumes: `redirect(string $path): never` and `httpError(int $status, string $message): never` from `includes/view.php`

- [ ] **Step 1: Write failing authentication, ownership, and CSRF tests**

```php
test('authentication state comes from the numeric session user id', function (): void {
    $_SESSION = [];
    assertSameValue(null, currentUserId());
    $_SESSION['user_id'] = 7;
    assertSameValue(7, currentUserId());
});

test('ownership compares session user id with created_by', function (): void {
    $_SESSION = ['user_id' => 7];
    assertSameValue(true, resourceBelongsToCurrentUser(['created_by' => '7']));
    assertSameValue(false, resourceBelongsToCurrentUser(['created_by' => '8']));
});

test('csrf token verifies only the stored token', function (): void {
    $_SESSION = [];
    $token = csrfToken();
    assertSameValue(true, isValidCsrfToken($token));
    assertSameValue(false, isValidCsrfToken('wrong-token'));
    assertSameValue(false, isValidCsrfToken(null));
});
```

- [ ] **Step 2: Run the tests and verify RED**

Run: `php tests/run.php`

Expected: FAIL because authentication and CSRF functions do not exist.

- [ ] **Step 3: Implement session and security helpers**

Use the following implementation shape:

```php
function currentUserId(): ?int
{
    $id = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? null : $id;
}

function resourceBelongsToCurrentUser(array $resource): bool
{
    return currentUserId() !== null && currentUserId() === (int) ($resource['created_by'] ?? 0);
}

function csrfToken(): string
{
    if (!isset($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}

function isValidCsrfToken(?string $submittedToken): bool
{
    return is_string($submittedToken)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $submittedToken);
}
```

`loginUser()` calls `session_regenerate_id(true)` before storing integer `user_id` and `email`. `logoutUser()` clears `$_SESSION`, expires the session cookie when cookies are active, and calls `session_destroy()` when a session is active. `bootstrap.php` enables strict session mode and HTTP-only, SameSite=Lax cookies before `session_start()`, then requires every shared function file.

- [ ] **Step 4: Run the tests and verify GREEN**

Run: `php tests/run.php`

Expected: all tests pass with exit code 0.

- [ ] **Step 5: Commit**

```bash
git add bootstrap.php includes/auth.php includes/csrf.php tests
git commit -m "feat(auth): Add session CSRF and ownership helpers"
```

---

### Task 3: PDO repositories, MySQL schema, and demo seed

**Files:**
- Create: `tests/repository_test.php`
- Create: `config/database.php`
- Create: `includes/resource_repository.php`
- Create: `includes/user_repository.php`
- Create: `database/schema.sql`
- Create: `database/seed.php`
- Modify: `tests/run.php`

**Interfaces:**
- Produces: `db(): PDO`
- Produces: `findUserByEmail(PDO $pdo, string $email): ?array`
- Produces: `listResources(PDO $pdo, string $search = ''): array`
- Produces: `findResource(PDO $pdo, int $id): ?array`
- Produces: `createResource(PDO $pdo, array $data, int $userId): int`
- Produces: `updateResource(PDO $pdo, int $id, array $data): void`
- Produces: `deleteResource(PDO $pdo, int $id): void`

- [ ] **Step 1: Write failing repository integration tests**

Create an in-memory SQLite schema in the test, insert two users and two resources, then assert:

```php
test('resource repository lists and searches resources', function (): void {
    $pdo = repositoryTestDatabase();
    assertSameValue(2, count(listResources($pdo)));
    $matches = listResources($pdo, 'diabetes');
    assertSameValue(1, count($matches));
    assertSameValue('Diabetes Basics', $matches[0]['title']);
    assertSameValue('owner@example.com', $matches[0]['creator_email']);
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
```

- [ ] **Step 2: Run the tests and verify RED**

Run: `php tests/run.php`

Expected: FAIL because repository functions do not exist.

- [ ] **Step 3: Implement repositories and database connection**

Use `PDO::prepare()` and `execute()` for every SELECT, INSERT, UPDATE, and DELETE, including unfiltered listing. The query pattern is:

```php
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

function createResource(PDO $pdo, array $data, int $userId): int
{
    $statement = $pdo->prepare(
        'INSERT INTO resources (title, description, url, category, created_by)
         VALUES (:title, :description, :url, :category, :created_by)'
    );
    $statement->execute($data + ['created_by' => $userId]);
    return (int) $pdo->lastInsertId();
}
```

`findResource()`, `updateResource()`, `deleteResource()`, and `findUserByEmail()` follow the same prepare/execute pattern with named parameters. Configure production PDO with exceptions, associative fetches, and disabled emulated prepares. Read `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` from environment variables with documented local defaults.

- [ ] **Step 4: Add the exact MySQL schema and idempotent seed**

Use this schema shape and seed rule:

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE resources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    url VARCHAR(2048) NOT NULL,
    category VARCHAR(100) NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_resources_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`database/seed.php` checks for `demo@example.com` with a prepared SELECT and inserts it with a prepared INSERT containing `password_hash('password', PASSWORD_DEFAULT)` only when absent.

- [ ] **Step 5: Run the tests and verify GREEN**

Run: `php tests/run.php`

Expected: every test passes with exit code 0.

- [ ] **Step 6: Commit**

```bash
git add config database includes/resource_repository.php includes/user_repository.php tests
git commit -m "feat(database): Add PDO repositories schema and demo seed"
```

---

### Task 4: Public browsing and search page

**Files:**
- Create: `tests/page_helpers_test.php`
- Create: `includes/header.php`
- Create: `includes/footer.php`
- Create: `index.php`
- Modify: `tests/run.php`

**Interfaces:**
- Consumes: `db()`, `listResources()`, `e()`, `searchTerm()`, `isLoggedIn()`, `currentUserId()`, and `csrfToken()`
- Produces: public GET browsing at `/` and search at `/?q=<term>`

- [ ] **Step 1: Add failing tests for page-facing helper behaviour**

Assert the exact page-facing helpers:

```php
test('search term is trimmed from q', function (): void {
    assertSameValue('diabetes', searchTerm(['q' => '  diabetes  ']));
    assertSameValue('', searchTerm([]));
});

test('flash message is consumed once', function (): void {
    $_SESSION = [];
    setFlash('Saved.');
    assertSameValue('Saved.', consumeFlash());
    assertSameValue(null, consumeFlash());
});
```

- [ ] **Step 2: Run the tests and verify RED**

Run: `php tests/run.php`

Expected: FAIL for the missing search and flash helper behaviour.

- [ ] **Step 3: Implement the public page and shared layout**

Read only `$_GET['q']`, call `listResources()`, and render the search value and every database value through `e()`:

```php
$search = searchTerm($_GET);
$resources = listResources(db(), $search);

foreach ($resources as $resource) {
    // Render title, category, description, URL, creator email, and timestamps with e().
    if (resourceBelongsToCurrentUser($resource)) {
        // Render edit link and POST delete form with hidden id and _token fields.
    }
}
```

The header exposes a POST logout form for logged-in users and a login link otherwise. The page shows a clear empty state when no resources match.

- [ ] **Step 4: Run automated tests and PHP syntax checks**

Run: `php tests/run.php`

Run: `php -l index.php` and lint each new include.

Expected: tests pass and every lint command reports no syntax errors.

- [ ] **Step 5: Commit**

```bash
git add index.php includes tests
git commit -m "feat(resources): Add public browse and search page"
```

---

### Task 5: Demo login and POST logout

**Files:**
- Create: `tests/login_test.php`
- Create: `login.php`
- Create: `logout.php`
- Modify: `includes/auth.php`
- Modify: `tests/run.php`

**Interfaces:**
- Produces: `credentialsAreValid(?array $user, string $password): bool`
- Consumes: `findUserByEmail()`, `validateLoginInput()`, `loginUser()`, `logoutUser()`, `isValidCsrfToken()`, `redirect()`, and `e()`

- [ ] **Step 1: Write failing credential tests**

```php
test('credentials verify a password_hash value', function (): void {
    $user = ['password_hash' => password_hash('password', PASSWORD_DEFAULT)];
    assertSameValue(true, credentialsAreValid($user, 'password'));
    assertSameValue(false, credentialsAreValid($user, 'wrong'));
    assertSameValue(false, credentialsAreValid(null, 'password'));
});
```

- [ ] **Step 2: Run the tests and verify RED**

Run: `php tests/run.php`

Expected: FAIL because `credentialsAreValid()` does not exist.

- [ ] **Step 3: Implement login and logout**

Add the credential helper and use it in the POST handler:

```php
function credentialsAreValid(?array $user, string $password): bool
{
    return $user !== null && password_verify($password, (string) $user['password_hash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['_token'] ?? null)) httpError(419, 'Invalid CSRF token.');
    $result = validateLoginInput($_POST);
    if ($result['errors'] === []) {
        $user = findUserByEmail(db(), $result['data']['email']);
        if (credentialsAreValid($user, $result['data']['password'])) {
            loginUser($user);
            setFlash('Welcome back.');
            redirect('index.php');
        }
        $result['errors']['credentials'] = 'Email or password is incorrect.';
    }
}
```

Login GET renders the form. Logout checks `$_SERVER['REQUEST_METHOD'] === 'POST'`, otherwise returns 405; it then verifies CSRF, clears the session, and redirects.

- [ ] **Step 4: Run tests and lint entry points**

Run: `php tests/run.php`

Run: `php -l login.php` and `php -l logout.php`.

Expected: tests pass and both files have no syntax errors.

- [ ] **Step 5: Commit**

```bash
git add login.php logout.php includes/auth.php tests
git commit -m "feat(login): Add demo user authentication"
```

---

### Task 6: Authenticated create and owner-only edit/delete

**Files:**
- Create: `tests/request_security_test.php`
- Create: `includes/resource_form.php`
- Create: `create.php`
- Create: `edit.php`
- Create: `delete.php`
- Modify: `tests/run.php`

**Interfaces:**
- Consumes: all validation, authentication, CSRF, view, and repository functions.
- Produces: `isMutationMethodAllowed(string $method): bool`
- Produces: authenticated create; server-authorized owner edit; POST-only, CSRF-protected, server-authorized owner delete.

- [ ] **Step 1: Write failing request-security decision tests**

```php
test('delete request is accepted only for POST', function (): void {
    assertSameValue(false, isMutationMethodAllowed('GET'));
    assertSameValue(true, isMutationMethodAllowed('POST'));
});

test('owner authorization rejects a different session user', function (): void {
    $_SESSION = ['user_id' => 2];
    assertSameValue(false, resourceBelongsToCurrentUser(['created_by' => 1]));
});
```

- [ ] **Step 2: Run the tests and verify RED**

Run: `php tests/run.php`

Expected: FAIL for the missing request-method helper.

- [ ] **Step 3: Implement create**

Require login before rendering or processing. Use this POST flow:

```php
$data = ['title' => '', 'description' => '', 'url' => '', 'category' => ''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken($_POST['_token'] ?? null)) httpError(419, 'Invalid CSRF token.');
    $result = validateResourceInput($_POST);
    $data = $result['data'];
    $errors = $result['errors'];
    if ($errors === []) {
        createResource(db(), $data, currentUserId());
        setFlash('Resource created.');
        redirect('index.php');
    }
}
```

Render `includes/resource_form.php` with `$data`, `$errors`, a form title, and a submit label.

- [ ] **Step 4: Implement edit with server-side ownership**

Parse a positive integer `id`, load the resource, and enforce ownership with this server-side sequence:

```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) httpError(404, 'Resource not found.');
$resource = findResource(db(), $id);
if ($resource === null) httpError(404, 'Resource not found.');
if (!resourceBelongsToCurrentUser($resource)) httpError(403, 'You cannot edit this resource.');
```

On POST, verify CSRF, reload the record and repeat the ownership comparison immediately before `updateResource()`, validate input, update by ID, flash `Resource updated.`, and redirect.

- [ ] **Step 5: Implement POST-only delete with CSRF and server-side ownership**

Implement the delete endpoint in this exact order:

```php
if (!isMutationMethodAllowed($_SERVER['REQUEST_METHOD'])) httpError(405, 'Method not allowed.');
requireLogin();
if (!isValidCsrfToken($_POST['_token'] ?? null)) httpError(419, 'Invalid CSRF token.');
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) httpError(404, 'Resource not found.');
$resource = findResource(db(), $id);
if ($resource === null) httpError(404, 'Resource not found.');
if (!resourceBelongsToCurrentUser($resource)) httpError(403, 'You cannot delete this resource.');
deleteResource(db(), $id);
setFlash('Resource deleted.');
redirect('index.php');
```

- [ ] **Step 6: Run tests and lint all CRUD entry points**

Run: `php tests/run.php`

Run: `php -l create.php`, `php -l edit.php`, `php -l delete.php`, and `php -l includes/resource_form.php`.

Expected: all tests pass and every file has no syntax errors.

- [ ] **Step 7: Commit**

```bash
git add create.php edit.php delete.php includes/resource_form.php tests
git commit -m "feat(resources): Add owner-authorized resource management"
```

---

### Task 7: Responsive styling and complete documentation

**Files:**
- Create: `assets/style.css`
- Create: `.env.example`
- Modify: `includes/header.php`
- Modify: `includes/footer.php`
- Modify: `README.md`

**Interfaces:**
- Consumes: the existing semantic HTML and environment-variable names.
- Produces: readable desktop and mobile presentation plus reproducible local setup instructions.

- [ ] **Step 1: Add the responsive stylesheet**

Use a restrained neutral palette, visible focus states, readable form labels, clear errors, resource cards, and this responsive foundation. Do not add JavaScript or external assets.

```css
:root { color-scheme: light; --ink: #17202a; --muted: #65717d; --accent: #176b5b; --line: #dce3e1; --surface: #fff; --canvas: #f5f7f6; }
* { box-sizing: border-box; }
body { margin: 0; color: var(--ink); background: var(--canvas); font-family: system-ui, sans-serif; line-height: 1.55; }
.container { width: min(100% - 2rem, 960px); margin-inline: auto; }
input, textarea, select, button { width: 100%; min-height: 2.75rem; font: inherit; }
a:focus-visible, button:focus-visible, input:focus-visible, textarea:focus-visible { outline: 3px solid #80c7ba; outline-offset: 2px; }
@media (max-width: 700px) {
    .site-header__inner, .search-form, .resource-actions, .resource-meta { align-items: stretch; flex-direction: column; }
}
```

- [ ] **Step 2: Write the setup and interview walkthrough**

Create `.env.example` as a reference list:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=patient_resources
DB_USER=root
DB_PASSWORD=
```

Document PHP/MySQL requirements, PowerShell environment-variable setup, `mysql < database/schema.sql`, `php database/seed.php`, `php -S localhost:8000`, `php tests/run.php`, demo credentials `demo@example.com` / `password`, directory structure, the two core request chains, prepared statements, CSRF, password hashing, session authentication, and server-side ownership checks.

- [ ] **Step 3: Verify documentation commands and responsive structure**

Run every local command that the environment supports. Start the PHP server and inspect desktop and narrow mobile widths. Confirm navigation, search, cards, forms, errors, and action buttons fit without horizontal scrolling.

- [ ] **Step 4: Commit**

```bash
git add assets .env.example includes/header.php includes/footer.php README.md
git commit -m "docs: Add setup guide and responsive interface"
```

---

### Task 8: Full verification, GitHub update, and pull request

**Files:**
- Modify only files required to correct verification failures.

**Interfaces:**
- Consumes: the complete application, tests, issue #1, and repository conventions.
- Produces: a verified branch and convention-formatted pull request closing issue #1.

- [ ] **Step 1: Run the complete automated suite**

Run: `php tests/run.php`

Expected: all tests pass with zero failures and exit code 0.

- [ ] **Step 2: Lint every PHP file**

Run in PowerShell:

```powershell
$failed = $false
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { $failed = $true }
}
if ($failed) { exit 1 }
```

Expected: every PHP file reports `No syntax errors detected` and the command exits 0.

- [ ] **Step 3: Run MySQL-backed manual verification**

Create the schema, run the seed twice, and confirm only one demo user exists. Verify public browse/search, correct and incorrect login, authenticated create, owner edit/delete, GET delete rejection, invalid-CSRF rejection, and non-owner edit/delete rejection. Confirm the database contains a password hash rather than `password`.

- [ ] **Step 4: Review scope and security constraints**

Use `rg` to confirm no registration, role, tag, like, comment, AI, AWS, Docker, framework, or JavaScript feature was added. Inspect every application SQL call for `prepare()` followed by `execute()`. Inspect edit and delete to confirm direct session-to-`created_by` authorization.

- [ ] **Step 5: Push and open the pull request**

Push `issue-1-alan746-Application-PatientEducationResourcePortal`. Create a PR titled `Build minimal patient education resource portal` whose body contains `Fixes #1`, Behaviours Completed, Files Changed, Brief Explanation, Testing, and Unsure About sections exactly as required by the provided convention.
