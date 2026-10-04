# Data Verification Dashboard (Demo)

A small, self-contained demo of an **internal data-verification portal**: staff bulk-import
citizen records from CSV, the system **prevents duplicates**, verifiers approve or reject
each record, and every action is written to an **audit log**. Records can be exported back
to CSV.

> ⚠️ **All data in this project is fictitious.** Names such as *"Warga Contoh 001"*,
> the fake NIK pattern *9999999999999001*, and the category labels are invented for
> demonstration only. No real person or identifier is represented.

## Features

- **Role-based access control (RBAC)**: `admin`, `verifikator`, `staff`.
- **CSV bulk import** with a clear per-file summary (rows read / inserted / duplicates / invalid).
- **Duplicate prevention** on three levels:
  1. a `UNIQUE` index on `nik` in the database,
  2. in-file de-duplication (same NIK twice in one upload),
  3. a race-safe `INSERT` guarded by the unique constraint.
- **Row validation**: NIK must be 16 digits; name is required; bad rows are reported, not dropped silently.
- **Verification dashboard**: live counts by status (pending / verified / rejected) and recent activity.
- **Record browser** with status filter, search and pagination.
- **Verify / reject workflow** with an optional note.
- **Audit log**: who imported what, and who verified/rejected which record, with timestamps.
- **CSV export** (optionally filtered by status).

## Security basics

- Passwords hashed with `password_hash()` (bcrypt), verified with `password_verify()`.
- **Prepared statements** (PDO) for every query — no string-built SQL.
- **CSRF protection** on all state-changing POST requests.
- **Secure sessions**: `HttpOnly`, `SameSite=Lax`, `Secure` over HTTPS, and
  `session_regenerate_id()` on login.
- Uploads restricted to `.csv`; output escaped with `htmlspecialchars()`.

## Tech stack

PHP 8.1+ · MySQL/MariaDB (PDO) · Composer · vlucas/phpdotenv · plain CSS. No framework.

## Getting started

```bash
composer install
cp .env.example .env            # then set DB_USER / DB_PASS

mysql -u root -p -e "CREATE DATABASE dvd_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p dvd_demo < schema.sql
mysql -u root -p dvd_demo < seed.sql

php -S localhost:8001 -t public public/index.php
```

Open <http://localhost:8001>.

### Demo accounts

| Username      | Role        | Password       |
|---------------|-------------|----------------|
| `admin`       | admin       | `Password123!` |
| `verifikator` | verifikator | `Password123!` |
| `staff`       | staff       | `Password123!` |

### Try it

1. Sign in as **staff** → **Import CSV** → upload `sample_data/residents_sample.csv`.
   The result page shows **3 inserted, 2 duplicates, 2 invalid** (the sample deliberately
   contains a duplicate NIK and bad rows).
2. Sign in as **verifikator** → open a pending record → **Verify** or **Reject**.
3. Check the **Dashboard** for updated counts and the recent audit activity.
4. **Export CSV** from the records page.

## Project structure

```
data-verification-dashboard-demo/
├── public/
│   ├── index.php            # front controller + router + secure session
│   └── assets/style.css
├── src/
│   ├── Auth.php  Csrf.php  Database.php  Rbac.php  Audit.php
│   ├── Records.php          # query, counts, verify/reject, CSV import & export
│   ├── helpers.php
│   └── Controllers/         # Auth, Dashboard, Record, Import
├── views/                   # PHP templates
├── sample_data/residents_sample.csv
├── schema.sql  seed.sql
├── .env.example  composer.json  LICENSE
```

## License

[MIT](LICENSE) © 2026 Noken13
