# Backend Setup Guide

This backend uses **CodeIgniter 4**, **MySQL/MariaDB**, and **CodeIgniter Shield**.

Use this guide to set up the backend locally, start the database, run migrations and seeders, and start the API server.

## Docs Router

- **Canonical for:** local backend setup and daily run commands.
- **Not canonical for:** runtime API behavior, schema rules, or planned-module design.
- **Read next for runtime/API work:** `docs/project-flow-alignment.md`, `docs/api-design.md`, `docs/data-dictionary.md`, `docs/system-design.md`.

For current API/module status after setup, see:

- `docs/api-design.md` for the active API contract
- `docs/project-flow-alignment.md` for the compact module/API/flow/access alignment matrix

## Requirements

- PHP **8.2+**
- Composer
- Docker + Docker Compose
- PHP extensions:
  - `intl`
  - `mbstring`
  - `json`
  - `mysqlnd`
  - `libcurl`

## 1. Start the database

From the **project root**:

```bash
docker compose up -d db adminer
```

This uses the root-level `docker-compose.yaml` and starts:

- **MariaDB** on `127.0.0.1:3306`
- **Adminer** on `http://127.0.0.1:9000`

Default database values from the compose file:

- Database: `db`
- Root username: `root`
- Root password: `root`

## 2. Install backend dependencies

From the `backend` directory:

```bash
composer install
```

## 3. Configure environment

The backend expects a `.env` file in the `backend` folder.

If `.env` does not exist yet, create it from the default template:

```bash
cp env .env
```

Make sure these values are correct in `backend/.env`:

```ini
app.baseURL = 'http://127.0.0.1:8080/'
database.default.hostname = 127.0.0.1
database.default.database = db
database.default.username = root
database.default.password = root
database.default.DBDriver = MySQLi
database.default.port = 3306
```

These values already match the provided Docker database setup.

## 4. Run database migrations

From the `backend` directory:

```bash
php spark migrate
```

## 5. Seed initial data

The project currently provides these seeders:

- `RoleSeeder`
- `UserSeeder`
- `ItemCategorySeeder`
- `TransactionTypeSeeder`
- `ApprovalStatusSeeder`
- `MealTimeSeeder`
- `MenuSeeder`
- `ItemUnitSeeder`
- `ItemSeeder`
- `DishSeeder`
- `DishCompositionSeeder`
- `MenuDishSeeder`
- `TestSeeder`

To seed the full development baseline in the correct order, run:

```bash
php spark db:seed TestSeeder
```

`TestSeeder` calls:

1. `RoleSeeder`
2. `UserSeeder`
3. `ItemCategorySeeder`
4. `TransactionTypeSeeder`
5. `ApprovalStatusSeeder`
6. `MealTimeSeeder`
7. `MenuSeeder`
8. `ItemUnitSeeder`
9. `ItemSeeder`
10. `DishSeeder`
11. `DishCompositionSeeder`
12. `MenuDishSeeder`

## 6. Run the backend server

From the `backend` directory:

```bash
php spark serve
```

The backend will be available at:

```text
http://127.0.0.1:8080
```

## Default seeded accounts

After running `TestSeeder`, these users are created:

| Role | Username | Email |
|---|---|---|
| admin | `admin` | `admin@example.com` |
| admin (inactive) | `inactiveadmin` | `inactiveadmin@example.com` |
| dapur | `spkgizi` | `spkgizi@example.com` |
| gudang | `gudang` | `gudang@example.com` |

Default password for all seeded users:

```text
password123
```

Seeded lookup/master baseline also includes:

- item categories: `BASAH`, `KERING`, `PENGEMAS`
- transaction types: `IN`, `OUT`, `RETURN_IN`
- approval statuses: `APPROVED`, `PENDING`, `REJECTED`
- meal times: `SIANG`, `SORE`, `PAGI`
- item units: `gram`, `kg`, `ml`, `liter`, `butir`, `pack`
- items: `Beras`, `Ayam`, `Minyak Goreng`, `Telur`
- menus: `Paket 1` through `Paket 11`
- dishes: 33 named dishes covering all 11 menus × 3 meal-time slots
- dish compositions: one composition per dish, cycling through the seeded items
- menu dish slots: every menu (1–11) has all three meal times (Pagi, Siang, Sore) assigned

The default development seeder intentionally does **not** seed `menu_schedules`, `daily_patients`, `stock_transactions`, `stock_transaction_details`, `spk_calculations`, `spk_recommendations`, or `audit_logs`. Those tables stay empty by default so calendar fallback logic, patient intake workflows, stock movement, SPK calculations, and audit trails are not confused by fake historical data.

## Full quick start

### From project root

```bash
docker compose up -d db adminer
```

### From backend directory

```bash
composer install
php spark migrate
php spark db:seed TestSeeder
php spark serve
```

## Useful commands

```bash
php spark list
php spark migrate:status
php spark migrate:refresh
composer test
```

## Notes

- Database container data is stored in the root `db/` folder via Docker volume mapping.
- If port `3306` or `9000` is already in use, stop the conflicting service first.
- If the app cannot connect to the database, verify the database container is running and check `backend/.env`.
