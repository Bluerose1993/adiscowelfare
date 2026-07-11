# Staff Welfare Dues Application

Local Laravel application for managing staff welfare dues, welfare benefits, and staff-submitted benefit requests for the Adisadel College Teaching Staff Welfare Association.

## Requirements

- PHP 8.3+
- Composer
- MySQL 8 or MariaDB
- Node.js 18+ and npm
- Laragon or another local Apache/PHP/MySQL stack

## Installed Stack

- Laravel 13
- Blade templates
- AdminLTE 3, Bootstrap 4, Font Awesome, DataTables, Chart.js bundled locally through Vite
- Spatie Laravel Permission for roles and permissions
- Maatwebsite Laravel Excel for imports and exports

## Local Setup

From this project directory:

```bash
composer install
npm install --cache .npm-cache
copy .env.example .env
php artisan key:generate
```

Create the database:

```sql
CREATE DATABASE welfare_dues CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configure `.env`:

```env
APP_NAME="Adisadel Welfare"
APP_URL=http://localhost/Welfare/public
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=welfare_dues
DB_USERNAME=root
DB_PASSWORD=
```

Run:

```bash
php artisan migrate --seed
npm run build
php artisan storage:link
```

## Access

Laragon path:

```text
http://localhost/Welfare/public
```

Default administrator:

```text
Username: admin
Password: ChangeMe123!
```

Change this password before real use.

## Main Features

- Administrator and staff member login
- Staff records with optional linked login accounts
- Dues rates configurable by effective date
- Transactional dues payments with monthly and annual summaries
- Fast single-payment workflow with staff search
- Bulk dues entry with transaction safety
- Benefit types, benefits, pending benefits, and paid benefits
- Staff benefit request submission and admin review/approval
- Approval creates one linked pending benefit and prevents duplicate conversion
- Admin dashboard with real database totals and charts
- Staff portal dashboard with monthly payment statuses
- Dues, benefits, staff statement, financial summary, and Excel exports
- Historic Excel import with preview and manual review for uncertain matches
- Audit logs for important workflows

## Excel Import

Use `Admin > Import Staff/Dues`.

The importer detects a header row containing staff names and month columns. It matches staff primarily by Staff ID, then by exact normalized name only. If multiple staff share the same normalized name, the row is marked for manual review and is not committed automatically.

## Excel Export

Available exports include:

- Annual Dues Chart
- Dues Transactions
- Staff Statement
- Benefits Report
- Pending Benefits Report
- Dues versus Benefits Summary

The annual dues chart preserves the original January-December structure while adding Staff ID and professional workbook formatting.

## Tests

Create a test database:

```sql
CREATE DATABASE welfare_dues_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run:

```bash
php artisan test
```

The current suite covers login, role restrictions, dues recording, monthly totals, annual totals, staff privacy, benefit request submission, approval, duplicate conversion prevention, export totals, financial deletion authorization, and import matching safety.

## Backups

Before importing historic records or making production changes, back up MySQL:

```bash
mysqldump -u root welfare_dues > welfare_dues_backup.sql
```

Also back up `storage/app` because uploaded support documents are stored there.

## Production Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Use HTTPS and secure session cookie settings.
- Change the default admin password.
- Create named administrator accounts instead of sharing one admin login.
- Schedule regular database and storage backups.
- Restrict filesystem permissions for `.env` and `storage`.
- Review npm audit findings before internet-facing deployment.
- Configure mail if email notifications are added later.
