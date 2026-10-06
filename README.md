# Transit Desk

Municipal Tricycle and Public Transport Permitting, Franchise Management, and Violation Tracking System built with Laravel, Blade, Eloquent, Bootstrap 5, and MySQL/SQLite.

## Requirements

- PHP 8.2+ (PHP 8.3 recommended)
- Composer 2+
- MySQL/MariaDB through XAMPP

## Installation

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Configure `DB_CONNECTION=mysql`, `DB_DATABASE=mtmptv`, `DB_USERNAME=root`, and the local MySQL password in `.env`. The project is configured for XAMPP MariaDB on `127.0.0.1:3306`.

Open `http://127.0.0.1:8000`.

## Working modules

Role-based authentication, database-backed permissions, public account registration, username/email login, profile editing, admin user management, database-backed dashboard statistics, operator CRUD, vehicle CRUD, application submission and review workflow, filters, search, pagination, audit logging for key actions, and relationship-backed detail views. Dashboard counts and record lists are queried from the SQL database; empty queries render empty states.

## Roles and access

The application roles are `admin` (displayed as Administrator), `staff`, `viewer` (Report User), `operator` (Operator / Driver), and `vehicle_owner`. `admin` is the existing canonical stored role; no separate `administrator` value is used. A shared role-permission matrix defines each non-admin role's maximum capabilities; actual grants are stored in `role_permissions` and checked server-side on every protected route/API request. Administrators have full access while active. Role permission management and user-role/status changes are restricted to administrators, with protections against demoting or deactivating the last active administrator.

| Role | Allowed functionality | Restricted functionality |
| --- | --- | --- |
| Administrator | All dashboards, operational records, CRUD/status actions, users, and permissions | Cannot change their own role or account status |
| Staff | Dashboard; manage operators, vehicles, and applications; read transport records, reports, and own notifications | Cannot manage users/permissions or use operator/vehicle-owner portals |
| Report User (`viewer`) | Dashboard; read public transport/report records; submit and view their own reports; view own notifications/profile | Cannot manage operational records, application status, users, or permissions |
| Operator | Own dashboard/profile/password/location; assigned vehicles, applications, permits, franchises, renewals, violations, and notifications | Cannot access other operators' records or global CRUD/admin modules |
| Vehicle Owner | Own dashboard/profile; owned vehicles, applications, permits, franchises, renewals, violations, and notifications | Cannot access other owners' records or global CRUD/admin modules |

Owner/operator API records are filtered by their authenticated operator profile and checked again on detail requests. Report-user API reports are scoped to the authenticated account. Public read permissions remain read-only. Run `php artisan db:seed` to insert missing role permissions. Seeding does not create user accounts or sample operational records; provision privileged accounts through a trusted deployment process.

Administrators have a dedicated dashboard with counts and recent activity read from SQL, plus existing user, role/permission, transport application, report, operational-record, notification, and audit-log features. System settings are not exposed because the current project has no settings module.

Report Users have a dedicated view-only workspace covering the dashboard, reports, my reports, operators, vehicles, franchises, permits, renewals, violations, notifications, and profile. Management routes return 403 for this role, and public operator details omit private contact and identity data.

Operator / Driver accounts have an ownership-scoped portal at `/operator/*` for their dashboard, operator profile, assigned vehicles, applications, permits, franchises, renewals, violations, notifications, and password changes. Operator applications are linked to the authenticated operator server-side, duplicate pending submissions are rejected, and cross-operator IDs return 404.

The operator ownership migration adds `operators.user_id` with a unique foreign key. New public Operator / Driver registrations automatically receive an operator profile. Document uploads and violation appeals are not enabled because the existing project has no document or appeal tables; adding those workflows requires a separate schema decision.

Public registration permits Report User, Operator/Driver, and Vehicle Owner accounts only. Administrator and Staff access is managed by an administrator through User Management or the administrator-application approval workflow. Active non-administrator accounts may apply for Administrator access; application review and role promotion are transactional, self-review is forbidden, and approved/rejected decisions are notified and audited. Rejected applicants may reapply once no pending application exists.

To provision the initial `benjadmin` account, set `INITIAL_ADMIN_PASSWORD` through a trusted local/deployment secret source before running `php artisan migrate --seed`. The seeder hashes the configured password, creates the account only if that username does not already exist, and never resets an existing account's credentials or role. Do not commit the secret or put it in frontend code. When the secret is unset, the rest of database seeding proceeds and the initial account is skipped with a console warning.

The schema also includes franchises, permits, renewals, violations, notifications, and audit logs for the next resource screens.

## Useful commands

```bash
# For a disposable local database only; migrate:fresh drops all tables and data.
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
```

Authorization checks are covered by `tests/Feature/RoleAccessTest.php` and `tests/Feature/AdministratorApplicationTest.php`.
Report User coverage is in `tests/Feature/ReportUserTest.php`.
Operator coverage is in `tests/Feature/OperatorPortalTest.php`.

Set `APP_DEBUG=false` and use a dedicated MySQL account before deployment. Do not use `migrate:fresh` against a database containing data you need to keep.
