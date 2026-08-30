# Patient Education Resource Portal Architecture

## Goal

The portal is a PHP/MySQL CRUD application for browsing, searching, and managing patient education resources. Its core request paths are:

1. Browser → HTML form → PHP → server-side validation → PDO prepared statement → MySQL → result → HTML.
2. Login → `password_verify()` → PHP session → server-side authorization → CRUD.

## Scope

The application provides only:

- Public resource browsing.
- Public search across resource title, description, and category.
- Login with one seeded demo account.
- Authenticated resource creation.
- Owner-only resource editing and deletion.
- MySQL persistence.
- Responsive desktop and mobile layouts.

It does not provide registration, roles, tags, likes, comments, cloud services, Docker, frameworks, APIs, or distributed services.

## Technology

- PHP 8.1 or newer, without a framework.
- MySQL 8.0 or newer.
- PDO with the MySQL driver.
- PHP sessions.
- Server-rendered HTML and one small CSS file.
- Native PHP test scripts with no Composer dependencies.

## Data Model

### `users`

| Column | Definition |
| --- | --- |
| `id` | Unsigned auto-incrementing primary key |
| `email` | Unique, required email address |
| `password_hash` | Required hash returned by `password_hash()` |
| `created_at` | Required timestamp, default current timestamp |

### `resources`

| Column | Definition |
| --- | --- |
| `id` | Unsigned auto-incrementing primary key |
| `title` | Required string, maximum 255 characters |
| `description` | Optional text |
| `url` | Required HTTP or HTTPS URL, maximum 2048 characters |
| `category` | Required string, maximum 100 characters |
| `created_by` | Required foreign key referencing `users.id` |
| `created_at` | Required timestamp, default current timestamp |
| `updated_at` | Required timestamp, updated automatically |

The foreign key uses `ON DELETE RESTRICT`. No user-deletion feature exists.

## Pages and Responsibilities

- `index.php`: browse all resources and handle the optional `q` search query.
- `login.php`: render and process the login form.
- `logout.php`: accept POST, validate the CSRF token, and end the session.
- `create.php`: require login, render the form, validate POST data, and insert a resource owned by the current user.
- `edit.php`: require login, load the resource, verify ownership on the server, validate POST data, and update it.
- `delete.php`: accept POST only, require login, validate the CSRF token, load the resource, verify ownership on the server, and delete it.
- `config/database.php`: create the PDO connection from environment variables.
- `includes/auth.php`: session authentication and owner-authorization helpers.
- `includes/csrf.php`: CSRF token creation and verification.
- `includes/resource_repository.php`: prepared resource queries.
- `includes/validation.php`: login and resource form validation.
- `includes/view.php`: output escaping, redirects, flash messages, and shared rendering helpers.
- `includes/header.php` and `includes/footer.php`: shared page structure.
- `assets/style.css`: the responsive presentation layer.
- `database/schema.sql`: database tables and foreign key.
- `database/seed.php`: idempotently seed `demo@example.com` using the value returned by `password_hash('password', PASSWORD_DEFAULT)`.

## Request and Data Flow

Public listing prepares and executes either a complete resource query or a search query using one bound wildcard term for title, description, and category. Results include the creator email through a join and are rendered with escaped output.

Create and edit forms use POST. PHP normalizes input, runs server-side validation, and redisplays field-specific errors without writing when validation fails. Successful writes use a prepared PDO statement and redirect to `index.php` to prevent form resubmission.

Delete uses POST exclusively. `delete.php` rejects other methods, verifies CSRF before mutation, reloads the resource, compares `(int) $_SESSION['user_id']` with `(int) $resource['created_by']`, and only then runs the prepared delete statement.

## Authentication and Authorization

The seed script stores only a password hash. Local setup uses the demo credentials `demo@example.com` / `password`; the database never stores the plain-text password.

Login looks up the email using a prepared statement and calls `password_verify()`. On success it rotates the session ID and stores the numeric user ID and email in the session. Logout clears the session after a valid POST CSRF check.

Create requires an authenticated session. Edit and delete independently perform server-side ownership checks against `resources.created_by`. Hiding edit/delete controls from non-owners is a presentation convenience, never the authorization mechanism.

## Validation and Security

- Email login input must be a syntactically valid email address and the password must be non-empty.
- Resource title and category are required and constrained to their database lengths.
- Resource URL is required, constrained to 2048 characters, and must use the HTTP or HTTPS scheme.
- Description is optional.
- Every application SQL statement is executed through `PDO::prepare()` and `PDOStatement::execute()`.
- Dynamic HTML output is escaped with `htmlspecialchars()`.
- All state-changing forms use POST and CSRF protection.
- Sessions use HTTP-only cookies and strict mode when available.
- Missing records return HTTP 404; unauthenticated protected access redirects to login; ownership failures return HTTP 403; invalid methods return HTTP 405.

## Interface

The English interface uses a narrow content container, a header with login state, a search form, resource cards, and simple forms. Cards display title, category, description, URL, owner email, and timestamps. Authenticated owners see edit and delete controls. A single responsive breakpoint stacks navigation, search controls, form actions, and cards on small screens.

## Testing

Native PHP tests cover:

- Valid and invalid resource input.
- Valid and invalid login input.
- Authentication state helpers.
- CSRF token creation and verification.
- Owner and non-owner authorization decisions.
- URL scheme restrictions and output escaping.

Verification also includes PHP syntax checks for every PHP file. Manual MySQL-backed checks cover database creation, idempotent demo seeding, login, browsing/searching, create/edit/delete, rejected unauthorized edit/delete, rejected GET deletion, rejected invalid CSRF, and responsive desktop/mobile layouts.
