# Project Flow Alignment and Revised Class Diagram

## Quick Router

- **Canonical for:** compact runtime status index across modules, flow summary, and cross-doc navigation.
- **Read this when:** you need the fastest answer for what is implemented vs planned and which detailed doc to open next.
- **Read next:** `docs/reference/api-contract.md` for endpoint contracts, `docs/reference/schema.md` for schema/constraints.
- **Legacy context (Deprecated):** `docs/api-design.md`, `docs/data-dictionary.md`, `docs/system-design.md`.
- **Not canonical for:** full request/response payload detail or field-by-field schema definitions.

## 1. Purpose

Dokumen ini menyelaraskan class diagram usulan dengan implementasi backend yang benar-benar ada saat ini.

Kesimpulan utamanya:

- project ini **bukan** Laravel MVC dengan Blade views;
- backend saat ini adalah **CodeIgniter 4 REST API**;
- frontend akan menjadi **Next.js client** yang mengonsumsi API;
- arsitektur aktual yang berjalan saat ini adalah **route/filter -> controller -> service -> model -> database**.

Source of truth yang dipakai untuk alignment ini:

- `app/Config/Routes.php`
- `app/Controllers/Api/V1/*`
- `app/Services/*`
- `app/Models/*`
- `app/Filters/RoleFilter.php`
- `app/Libraries/JsonApiExceptionHandler.php`
- `docs/reference/api-contract.md`
- `docs/reference/schema.md`

## 2. Module Matrix (Implemented)

| Module | Status | Routes/Endpoints | Key Logic / Constraints | UI/Query Features | Auth | Docs |
|---|---|---|---|---|---|---|
| Auth | Implemented | `/auth/login`, `/auth/me`, `/auth/logout`, `/auth/password` | Login berbasis token Shield; self-service password change revoke semua token user | Login pakai `username` + `password`; password change butuh current password | `login` public; sisanya authenticated user | `docs/reference/api-contract.md`, `docs/architecture/runtime-status.md` |
| Roles | Implemented | `GET /roles` | Read-only lookup pada implemented baseline | List mendukung query lookup standar | `admin` only | `docs/reference/api-contract.md`, `docs/architecture/runtime-status.md`, `AGENTS.md` |
| Users | Implemented | `/users`, `/users/{id}`, `/users/{id}/activate`, `/deactivate`, `/password`, `/users/{id}/restore` | Soft delete revoke token; activate/deactivate mengontrol login; role resolution bisa by id or by name; restore bersifat idempotent, blok jika ada active-username duplikat; `username` unik secara global bahkan setelah soft delete | List mendukung `q/search`, `role_id`, `is_active`, sort, created/updated date range | `admin` only | `docs/reference/api-contract.md`, `docs/reference/schema.md` |
| Lookup APIs | Implemented | `/item-categories`, `/item-units`, `/transaction-types`, `/approval-statuses`, `/roles`, `/meal-times` | Lookup soft delete tidak muncul di list/show; `item-categories` dan `item-units` pakai explicit restore; delete lookup tertentu diblok jika masih direferensikan | Semua lookup list mendukung `paginate=false`, `q/search`, sort, created/updated date range; envelope tetap `data/meta/links` | Read: `admin`, `gudang`; write untuk `item-categories` and `item-units`: `admin` only; `roles` list: `admin` only | `docs/reference/api-contract.md`, `docs/reference/schema.md` |
| Items | Implemented | `/items`, `/items/{id}`, `/items/{id}/restore` | `qty` tidak boleh diedit langsung; unit write pakai nama lalu di-resolve ke FK `item_unit_*`; delete bersifat soft delete; restore bersifat idempotent, blok jika ada active-name duplikat; `name` unik secara global bahkan setelah soft delete; create/update mengembalikan `restore_id` jika nama milik deleted item | List mendukung `item_category_id`, `is_active`, `q/search`, sort, created/updated date range; create/update menerima `item_category_id` atau `item_category_name` | Read/write: `admin`, `gudang`; delete/restore: `admin` only | `docs/reference/api-contract.md`, `docs/reference/schema.md` |
| Stock Transactions | Implemented | `/stock-transactions`, `/stock-transactions/{id}`, `/details`, `/submit-revision`, `/approve`, `/reject`, `/stock-transactions/direct-corrections` | Transaksi stok adalah satu-satunya jalur mutasi stok; revision workflow submit/approve/reject adalah domain flow inti; direct stock correction tersedia untuk admin; audit logging aktif; **tidak ada DELETE route** — transaksi adalah audit record permanen | List mendukung `type_id`, `status_id`, `transaction_date_from/to`, `q/search`, sort, created/updated date range; create bisa `type_id` atau `type_name`; direct correction butuh `item_id`, `expected_current_qty`, `target_qty`, `reason` | Read/write dasar: `admin`, `gudang`; approve/reject & direct correction: `admin` only | `docs/reference/api-contract.md`, `docs/architecture/runtime-status.md` |
| Dashboard | Implemented (Minimum) | `/dashboard` | Agregasi minimum role-based summary untuk `admin`, `dapur`, `gudang` | Query belum dipublikasikan penuh; payload mengikuti kontrak role-based minimum | `admin`, `dapur`, `gudang` | `docs/reference/api-contract.md`, `app/Controllers/Api/V1/Dashboard.php` |
| Menu & Nutrition | Implemented | `/menus`, `/menu-dishes`, `/menu-schedules`, `/menu-calendar`, `/dishes`, `/dish-compositions` | Siklus menu 1-11; package header immutable (no menu create/delete route); calendar resolver otomatis (day 31 -> Pkt 11, Feb 29 -> Pkt 9, fallback % 10); slot menu per meal time (Pagi, Siang, Sore) | List menu `1..11`; calendar butuh `date`, `month`, atau `start_date`+`end_date` | Read: `admin,gudang`; write: `admin,dapur` | `docs/reference/api-contract.md`, `docs/architecture/runtime-status.md`, `docs/reference/schema.md` |
| Daily Patients | Implemented | `/daily-patients`, `/daily-patients/{id}` | Input pasien harian per service date; divalidasi agar tidak duplikat per tanggal layanan; immutable audit record (no edit/delete route) | Create butuh `service_date`, `total_patients`, `notes` | Read: `admin,gudang`; write: `admin,dapur` | `docs/reference/api-contract.md`, `docs/architecture/runtime-status.md`, `docs/reference/schema.md` |
| SPK Calculations | Implemented | `/spk/basah/*`, `/spk/kering-pengemas/*`, `/spk/stock-in-prefill/{id}` | Basah: input-day basis, 5% ceil; Kering: monthly basis, 110% uplift; generation membuat versi histori baru tanpa overwrite; stock posting adalah langkah eksplisit | Basah butuh `target_date`, `daily_patient_id`, `category_id`; Kering butuh `target_month`, `category_id` | Read: `admin,gudang`; write/generate: `admin,dapur`; override: `admin,dapur`; post-stock: `admin` only | `docs/reference/api-contract.md`, `docs/architecture/runtime-status.md`, `docs/use-case-diagram.md` |
| Audit Reporting / Export | Implemented (JSON Dataset) | `/reports/stocks`, `/reports/transactions`, `/reports/spk-history`, `/reports/evaluation` | Dataset JSON siap ekspor tersedia runtime; endpoint export file audit/report belum tersedia | `period_start` + `period_end` mandatory dengan validasi date-range | `admin,dapur,gudang` | `docs/reference/api-contract.md`, `docs/architecture/runtime-status.md` |
- endpoint baru sebaiknya didokumentasikan di `docs/reference/api-contract.md` sebagai `Implemented` atau `Planned`;
- dokumen desain sistem harus tetap membedakan antara target domain dan route yang sudah aktif;
- audit logging perlu diperluas ke write flow penting lain, bukan hanya transaksi stok;
- `items.qty` harus tetap dianggap sebagai controlled operational balance, bukan field bebas edit.

## 8. Final Alignment Summary

Revisi paling penting dari diagram awal adalah:

1. hapus layer `Views` dari diagram backend utama;
2. ganti dengan `NextjsFrontend` sebagai external client;
3. tambahkan **service layer** sebagai pusat business logic;
4. tampilkan filter/auth/error handling sebagai komponen lintas-layer;
5. pisahkan dengan tegas antara:
   - **implemented architecture**; dan
   - **future planned architecture**.

Dengan revisi ini, dokumentasi akan selaras dengan codebase sekarang dan tetap berguna sebagai blueprint pengembangan berikutnya.
