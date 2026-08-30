# Patient Education Resource Portal

A small server-rendered PHP/MySQL portal for browsing, filtering, and searching patient education resources. Visitors can open a public detail page for each resource. The seeded demo user can create resources and can edit or delete only resources they own.

## Requirements

- PHP 8.1 or later, with the PDO MySQL extension enabled for application persistence.
- The PDO SQLite extension, required only to run the in-memory test suite with `php tests/run.php`.
- MySQL 8.0 or later.
- The MySQL command-line client for importing the schema.

## Setup

Copy the values in `.env.example` into your environment. The application reads `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD`; its local defaults match the example values.

In Windows PowerShell, set the variables for the current shell:

```powershell
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '3306'
$env:DB_NAME = 'patient_resources'
$env:DB_USER = 'root'
$env:DB_PASSWORD = ''
```

Create the database first, then import the schema. In PowerShell, redirect the schema through the MySQL client:

```powershell
mysql -u root -p -e "CREATE DATABASE patient_resources CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
Get-Content database/schema.sql | mysql -u root -p patient_resources
```

On POSIX shells, the same schema import is:

```sh
mysql -u root -p -e 'CREATE DATABASE patient_resources CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
mysql -u root -p patient_resources < database/schema.sql
```

Seed the demo user, start the local server, and run the test suite:

```sh
php database/seed.php
php -S localhost:8000
php tests/run.php
```

Open `http://localhost:8000/index.php`. The seed is idempotent, so it is safe to run it again. The demo account is `demo@example.com` with password `password`.

## Local and public access

`http://localhost:8000` is a development address. It works only on the computer running the PHP server and is not a public website address. Keep the PHP terminal and MySQL service running while using the local site.

The GitHub repository stores the source code but does not run the PHP application or its MySQL database. To make the portal available to other people, deploy it to a web host that supports PHP 8.1 or later and MySQL 8.0 or later:

1. Upload the tracked project files to the host's web directory.
2. Create a MySQL database and import `database/schema.sql`.
3. Set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in the hosting environment.
4. Run `php database/seed.php` once to create the demo account.
5. Open the public address supplied by the host, such as `https://resources.example.com`.

After deployment, visitors need only the public address and a browser. They do not need PHP or MySQL installed on their own computers.

## Project structure

```text
assets/style.css                   Responsive, accessible presentation
bootstrap.php                      Session setup and shared-function loading
config/database.php                PDO MySQL connection factory
database/schema.sql                MySQL tables and foreign key
database/seed.php                  Idempotent demo-user seed
includes/                          Authentication, CSRF, validation, repositories, and views
index.php                          Public browsing, search, and category filtering
resource.php                       Public resource detail page
login.php / logout.php             Demo authentication endpoints
create.php / edit.php / delete.php Authenticated resource management endpoints
tests/                             Dependency-free automated tests
```

## Request walkthrough

Public browsing, filtering, and resource details follow this chain:

1. `index.php` reads and normalizes the optional `q` and `category` query parameters.
2. `listResources()` builds the matching conditions, binds every value, and returns resources ordered newest first.
3. `listResourceCategories()` supplies the category choices from existing resources.
4. Each title links to `resource.php`, which validates the numeric ID and loads the selected resource with its creator email.
5. Both pages escape every dynamic value before rendering it.

Authenticated resource management follows this chain:

1. The user submits the login form; the server verifies the CSRF token, validates credentials, loads the user, and calls `password_verify()`.
2. A successful login regenerates the session ID and stores the user ID and email in the session.
3. Create, edit, and delete requests require that session authentication and a valid CSRF token. Edit and delete reload the resource and compare its `created_by` value with the session user ID on the server before changing data.

## Security notes

- Every application database query uses prepared statements and bound parameters through PDO.
- Forms that change data use CSRF tokens; delete accepts POST only.
- The seed generates a password hash with `password_hash()` and stores only that hash. Login uses `password_verify()`.
- Authentication state is held in PHP sessions. Authorization is enforced on the server: UI links alone do not grant edit or delete access.
- Dynamic HTML is escaped before rendering, and resource URLs are validated as HTTP or HTTPS.

## Development commands

The commands below assume PHP and MySQL are available on your `PATH`:

```sh
php database/seed.php
php -S localhost:8000
php tests/run.php
```

Use the first command after the schema import, run the second in a terminal while testing in a browser, and run the third from another terminal.
