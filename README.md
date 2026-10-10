# Employee Management & Attendance System

A Laravel 11 web app with Blade and Bootstrap 5 for the front end, MySQL for the
database, role-based access (Admin / Employee), and a token-protected REST API.

Included features only: authentication, employee management, attendance
(check-in / check-out), an admin dashboard, and the REST API.

---

## What you need (Windows)

| Item | Version | Notes |
|---|---|---|
| Laragon (Full) or XAMPP | recent | Laragon bundles PHP, MySQL, Composer, and a terminal |
| PHP | **8.2 or newer** | Laragon 6 ships PHP 8.3 |
| PHP extensions | `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo` | All enabled by default in Laragon |
| MySQL / MariaDB | MySQL 8.0+ or MariaDB 10.4+ | Included in Laragon / XAMPP |
| Composer | 2.x | Included in Laragon. XAMPP users: install from getcomposer.org |
| Laravel | 11.x | Installed by Composer from `composer.json` |

> **Steps marked `TERMINAL`** need a command prompt. In Laragon, click
> **Terminal** (or right-click the project folder → *Terminal here*). In XAMPP,
> open *Command Prompt* and `cd` into the project folder.

---

## 1. Extract the project

1. Extract `Employee_Management_Attendance_System.zip`.
2. Move the folder `employee-management` into Laragon's `www` folder:
   `C:\laragon\www\employee-management`
   (XAMPP: `C:\xampp\htdocs\employee-management`, and see the note in step 5.)

## 2. Install the PHP packages — `TERMINAL`

```bash
cd C:\laragon\www\employee-management
composer install
```

This downloads Laravel and Sanctum into a `vendor` folder. It takes a few
minutes the first time. Nothing else is needed.

## 3. Create the `.env` file and the application key — `TERMINAL`

Copy `.env.example` to a new file named `.env` in the same folder.
(Windows Explorer may hide the extension; name the copy exactly `.env`.)

Then run:

```bash
php artisan key:generate
```

Open `.env` and check these values:

```
APP_URL=http://employee-management.test   (Laragon; see step 5)
DB_DATABASE=employee_management
DB_USERNAME=root
DB_PASSWORD=                 (empty by default in Laragon/XAMPP)
```

## 4. Create the database and import the SQL file

1. Start **Laragon** (or XAMPP) and click **Start All**.
2. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
   (Laragon: *Menu → Database → HeidiSQL* also works.)
3. Click **New**, enter `employee_management`, choose collation
   `utf8mb4_unicode_ci`, and click **Create**.
4. With `employee_management` selected, click **Import**.
5. Choose the file `database/employee_management.sql` and click **Go**.

You should now see 6 tables: `users`, `personal_access_tokens`, `departments`,
`employees`, `attendance`, and `migrations`, with 5 departments in `departments`.

> The SQL file only creates the structure and departments. It does **not**
> contain any admin account or password. You create the admin in step 6.

## 5. Open the site

**Laragon:** click **Reload** in Laragon. Laragon creates the address
automatically. Visit **http://employee-management.test**.
If it does not open, use *Menu → Nginx/Apache → Auto Virtual Hosts* and
restart, or see the Laragon docs.

**XAMPP:** the address is `http://localhost/employee-management/public`.
Set `APP_URL` in `.env` to that same address.

## 6. Create the first admin account — `TERMINAL`

```bash
php artisan admin:create
```

Enter a name, an email, and a password (at least 8 characters). The password
is typed hidden and is never written to any file. Run this command again to add
more admins.

## 7. Log in

Go to the site and sign in with the admin email and password. Admins land on
the **Dashboard**. Create employees under **Employees → Add employee**. Each
employee gets a login with the email and password you set. Employees sign in
and land on **Attendance**, where they can check in and check out.

Every employee needs a login to check in. Deactivated employees and deactivated
logins cannot sign in, and their active session is ended on their next request.

---

## Automated tests — `TERMINAL`

The tests use a separate in-memory SQLite database, so your MySQL data is not
touched. Make sure `pdo_sqlite` is enabled in your PHP (Laragon: *Menu → PHP →
php.ini*, uncomment `extension=pdo_sqlite`, then restart).

```bash
php artisan test
```

Covered: login, invalid login, deactivated accounts, employee creation,
authorization (employees cannot reach admin pages), check-in, check-out,
duplicate and out-of-order actions, attendance retrieval and filters, and the
API (token login, token required, validation, 401 / 403 / 404 / 409 / 422
responses).

---

## REST API

Full reference: **docs/API.md**. A ready-made Postman collection is in
**postman/Employee-Management-API.postman_collection.json**.

Quick test with curl (`TERMINAL`, replace the email and password):

```bash
curl -X POST http://employee-management.test/api/login ^
  -H "Accept: application/json" -H "Content-Type: application/json" ^
  -d "{\"email\":\"admin@example.com\",\"password\":\"your-password\"}"
```

Copy the `token` from the response and use it on later calls:

```bash
curl http://employee-management.test/api/employees ^
  -H "Accept: application/json" -H "Authorization: Bearer PASTE_TOKEN_HERE"
```

**Postman:** *Import* → choose the collection file → set the `baseUrl`
variable to your site's address. Run **Login** first; it saves the token for
the other requests.

---

## Project layout

```
app/
  Console/Commands/CreateAdminCommand.php   php artisan admin:create
  Exceptions/AttendanceException.php        attendance rule violations
  Http/Controllers/                         web + Api/ controllers
  Http/Middleware/                          role check, active-account check
  Http/Requests/                            form validation
  Http/Resources/                           API JSON shape
  Models/                                   User, Employee, Department, Attendance
  Policies/EmployeePolicy.php               who may view or edit an employee
  Services/AttendanceService.php            check-in / check-out rules
  Support/ApiResponse.php                   { success, message, data, errors }
database/
  migrations/                               table definitions
  factories/, seeders/                      test data and reference data
  employee_management.sql                   import this in phpMyAdmin
resources/views/                            Blade templates
routes/web.php, routes/api.php              all routes
tests/Feature/                              automated tests
```

## Troubleshooting

- **"Dependencies are missing"** → run `composer install` (step 2).
- **"No application encryption key"** → run `php artisan key:generate` (step 3).
- **Blank page or 500 error** → set `APP_DEBUG=true` in `.env` temporarily to
  see the error, then set it back to `false`. Check `storage/logs/laravel.log`.
- **"Access denied for user"** → check `DB_USERNAME` / `DB_PASSWORD` in `.env`.
- **Styles look plain** → the site loads Bootstrap from a CDN, so an internet
  connection is required.
- **Changed `.env` but nothing changed** → run `php artisan config:clear`.
