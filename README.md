# CodeIgniter 4 POS — Technical Formative Assessments 1–4

**Course:** IT0049 — Web System Technologies  
**Student:** John Laurence Ventura  
**Current submission:** Technical Formative Assessment 4 — Who's Allowed In? Sessions and Authentication

This project builds a database-backed Point-of-Sale application using CodeIgniter 4. It progresses from MVC pages to database integration, validated forms and uploads, and staff authentication with protected routes.

## Submission Links and Status

- **GitHub repository:** https://github.com/jlventura594-bot/CodeIgniter-POS
- **Local project folder:** `Codeigniter-POS-FA`
- **Live application:** Pending InfinityFree deployment — replace this line with the working website URL before submission.
- **Database export:** [`database/pos_db.sql`](database/pos_db.sql)
- **Local testing:** The maintainer reports that manual testing passed, including testing after sample-data cleanup.
- **Live application:** https://ventura-pos-fa.infinityfreeapp.com/index.php/login

FA4 requires both the raw project files with a database export and a working hosted application. The repository alone does not complete the hosted submission requirement.

## Assessment Coverage

| Assessment | Features demonstrated in the current project |
| --- | --- |
| TFA1 — MVC foundations | Home, About, Customer Accounts, and User Accounts pages; routes, controllers, views, and navigation. |
| TFA2 — Database integration | MySQL/MariaDB persistence, CustomerModel and UserModel, database-backed account lists, and sample records. |
| TFA3 — Forms, validation, and uploads | Customer and staff creation/editing, validation feedback, retained non-password input, unique usernames, and staff avatar uploads. |
| TFA4 — Sessions and authentication | Hashed passwords, password verification, login sessions, authentication filters, protected account routes, and logout. |

The current version also includes product management and sales recording from continued POS development. Those features remain available, while this submission focuses on the formative assessments, especially authentication and sessions.

## FA4 Authentication Workflow

1. A visitor opens the login page and submits a username and password.
2. The controller validates the submitted input and finds the staff account through UserModel.
3. `password_verify()` compares the submitted password with the stored hash. Staff password creation and changes use `password_hash()`.
4. On successful login, the session ID is regenerated. The session stores `user_id`, `username`, `full_name`, and `logged_in`.
5. The user is redirected to the Home page. Invalid credentials return the user to the login page with an error.
6. AuthFilter protects the account pages and their form/action routes. It redirects unauthenticated requests to login and verifies that the signed-in account still exists.
7. Logout is a POST action that destroys the session and redirects to login.

All authenticated staff currently have the same management access; there are no separate administrator and cashier permissions.

## Requirements

- PHP 8.2 or a compatible newer version.
- Composer and the committed `composer.lock` file.
- MySQL or MariaDB.
- PHP extensions: `intl`, `mbstring`, `mysqli`, `fileinfo`, and `gd`.
- Writable runtime and upload directories, including a valid PHP upload temporary directory.

The project uses CodeIgniter 4.7. GD processes uploaded images.

## Local Installation

### 1. Download the project and dependencies

```bash
git clone https://github.com/jlventura594-bot/CodeIgniter-POS.git Codeigniter-POS-FA
cd Codeigniter-POS-FA
composer install
```

If you already cloned the project into `Codeigniter-POS-FA`, open that folder and run `composer install`; do not clone it again. The local folder name does not need to match the GitHub repository name.

### 2. Import the database

Start MySQL in XAMPP or your local database service. Create a database named `pos_db`, then import `database/pos_db.sql` using phpMyAdmin.

Import into an empty database for a fresh installation. The export contains the customers, users, products, and sales tables, including their relationships and sample data. Retain at least five sample customers and five sample staff accounts for the TFA2 requirement.

### 3. Create your local environment file

Create `.env` in the project root, beside `spark` and `composer.json`:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = pos_db
database.default.username = root
database.default.password = ''
database.default.DBDriver = MySQLi
database.default.port = 3306
```

These database settings are an example for local XAMPP. Adjust them to your installation. Keep the actual `.env` file out of Git.

### 4. Check folders and start the application

Ensure PHP can write to `writable/`, `public/uploads/avatars/`, and `public/uploads/products/`. Create missing upload directories. Include the image files referenced by the imported database.

```bash
php spark serve
```

Open http://localhost:8080/index.php/login and sign in with a staff account from the imported database. Obtain the demo username and password from the maintainer through the agreed submission channel. The database stores password hashes, not readable passwords.

## Application Routes

Paths below are application routes. Depending on URL rewriting, the full URL may include `index.php` before the route.

| Method and route | Purpose | Access |
| --- | --- | --- |
| GET `/login` | Login form | Public; signed-in users redirect to Home |
| POST `/login` | Validate credentials and create the login session | Public with CSRF protection |
| GET `/` | Home | Signed-in staff |
| GET `/about` | About | Signed-in staff |
| GET `/customers` | Customer Accounts | Signed-in staff |
| GET `/customers/new`, `/customers/edit/{id}` | Customer forms | Signed-in staff |
| GET `/users` | User Accounts | Signed-in staff |
| GET `/users/new`, `/users/edit/{id}` | Staff forms | Signed-in staff |
| GET `/products` | Product management | Signed-in staff |
| GET `/sales`, `/sales/new` | Sales history and sales form | Signed-in staff |
| POST `/logout` | Destroy the session and return to login | Signed-in staff |

Account creation, updates, deletion, and uploads also use protected POST routes. Forms include CSRF tokens.

## Validation and Uploads

- Customers require a full name and valid email; phone is optional.
- Staff require a full name and unique username. New accounts require a password; leaving the password blank during editing retains the existing hash.
- Password fields are not repopulated after validation errors.
- Staff avatars accept JPG/PNG files up to 2 MB and produce 160 × 160 PNG thumbnails.
- Product images accept JPG/PNG files up to 2 MB and produce 320 × 320 PNG images.
- Uploads receive generated filenames; database records store the filenames.
- Placeholder images appear when no image is available.
- Customers, staff, and products with recorded sales cannot be deleted. A staff member cannot delete their own signed-in account.

## Code Organization

| Location | Responsibility |
| --- | --- |
| `app/Config/Routes.php` | Routes and the authentication filter group |
| `app/Config/Filters.php` | Filter registration and CSRF configuration |
| `app/Controllers/Auth.php` | Login validation, password verification, session creation, and logout |
| `app/Filters/AuthFilter.php` | Authentication checks before protected requests |
| `app/Controllers/` | Account, product, and sales request handling |
| `app/Models/` | Database access and sales transaction logic |
| `app/Views/` | Pages, forms, validation feedback, and image partials |
| `public/` | Web entry point, public assets, and prepared upload images |
| `database/pos_db.sql` | Database schema and sample data |
| `writable/` | Runtime cache, logs, and sessions; generated contents are excluded from Git |

## FA4 Manual Verification

Local checks were performed during development. Repeat the following on the hosted site before submission; this table describes expected results and is not a claim of completed hosted testing.

| Check | Expected result |
| --- | --- |
| Open `/customers` or `/users` while logged out | Redirect to login |
| Open an account new/edit form while logged out | Redirect to login |
| Submit an incorrect password | Show an error without granting access |
| Submit valid staff credentials | Redirect to Home and allow protected pages |
| Open login while already signed in | Redirect to Home |
| Log out, then request a protected page again | Redirect to login |
| Submit a form without a valid CSRF token | Reject the request |
| Create a staff account and inspect its stored password | Store a password hash, not plaintext |
| Upload a valid avatar | Save and display the prepared thumbnail |

## InfinityFree Deployment Plan

Hosting will be completed by the maintainer. The live URL and hosted verification status above must be updated after deployment.

1. Verify the hosting account's PHP version and required extensions against the project requirements.
2. Prepare production dependencies in a separate deployment copy using `composer install --no-dev --optimize-autoloader`. Include `vendor/` in the deployed files even though it is excluded from Git.
3. Create the hosting database and import `database/pos_db.sql` through the hosting database tools.
4. Use the database hostname, database name, username, and password supplied by the hosting account. Do not reuse the local XAMPP credentials.
5. Configure `CI_ENVIRONMENT = production` and set `app.baseURL` to the actual hosted URL, including the trailing slash.
6. Adapt CodeIgniter's public entry point and application paths to the hosting directory layout. Keep configuration, SQL exports, and runtime data inaccessible from the web.
7. Ensure sessions and image uploads can be written, then perform the hosted verification checks above.
8. Add the working website link to this README and submit both the GitHub and hosted links.

`php spark serve` is only used for local development. Detailed hosting paths will be finalized during deployment. Application timestamps currently use UTC.

## References

- [CodeIgniter Sessions](https://codeigniter.com/user_guide/libraries/sessions.html)
- [CodeIgniter Controller Filters](https://codeigniter.com/user_guide/incoming/filters.html)
- [CodeIgniter Running Your App](https://codeigniter.com/user_guide/installation/running.html)
- [CodeIgniter Deployment](https://codeigniter.com/user_guide/installation/deployment.html)

## License

See [LICENSE](LICENSE) for the license included with this repository.
