# Panduan Screenshot Bukti CPMK

## Backend RSUD Balung

Proyek: Sistem Manajemen Inventaris & Gizi Dapur Rumah Sakit
Stack: CodeIgniter 4 + Shield (PHP 8.2), MariaDB, TypeScript SDK, OpenAPI 3.1 + Scalar

---

## KMU1018 — Pemrograman Back-End

### CPMK-1: Arsitektur server-side & database

| # | Screenshot | Isi | Lokasi File / URL |
|---|---|---|---|
| 1 | **ERD / Schema Database** | Tampilkan struktur 13+ tabel: `items`, `item_categories`, `stock_transactions`, `stock_transaction_details`, `spk_calculations`, `spk_recommendations`, `daily_patients`, `menu_schedules`, `menu_dishes`, `dish_compositions`, `stock_opnames`, `users`, `audit_logs` | Buka Adminer `http://127.0.0.1:9000` — pilih database `db`, screenshot daftar tabel |
| 2 | **File Migration** | Isi method `up()` — tunjukkan schema builder dengan foreign key & unique constraint | `backend/app/Database/Migrations/` — pilih salah satu, misal `2026-04-14-120000_CreateSpkPersistenceTables.php` atau `2026-04-04-091940_CreateAuditLogs.php` |
| 3 | **Query multi-JOIN** | Method yang melakukan JOIN 6 tabel (daily_patients → menu_schedules → menu_dishes → dish_compositions → items → item_categories) | `backend/app/Services/SpkBasahGenerationService.php` — method `generate()` |
| 4 | **Row locking (FOR UPDATE)** | Query `FOR UPDATE` atau conditional decrement `WHERE qty >= {qty}` untuk cegah double-submit | `backend/app/Services/StockTransactionService.php` — method `submitDraft()` |

### CPMK-2: Secure coding & validasi

| # | Screenshot | Isi | Lokasi File |
|---|---|---|---|
| 5 | **Auth filter / middleware** | Konfigurasi Shield atau filter yang mewajibkan JWT token di setiap route `/api/v1/*` kecuali login | `backend/app/Config/Auth.php` atau `backend/app/Filters/` |
| 6 | **RBAC — deklarasi role di controller** | Tunjukkan `$roles = ['admin', 'dapur', 'gudang']` — setiap endpoint punya guard role eksplisit | `backend/app/Controllers/Api/V1/SpkBasah.php` atau `StockTransactions.php` |
| 7 | **Input validation** | Validasi format data di controller (tanggal, numerik, string length) | `backend/app/Controllers/Api/V1/DailyPatients.php` — method `create()` |
| 8 | **Atomic conditional update** | `UPDATE items SET qty = qty - {qty} WHERE id = {id} AND qty >= {qty}` — mitigasi race condition | `backend/app/Services/StockTransactionService.php` — method `applyItemDelta()` atau `submitDraft()` |
| 9 | **Optimistic locking** | `WHERE id = {id} AND qty = {expected_current_qty}` — cegah overwrite data konkuren | `StockTransactionService::createDirectCorrection()` atau proses `StockOpname` post |
| 10 | **Audit log write** | Method yang menulis ke tabel `audit_logs` — tunjukkan data: `user_id`, `action`, `target_type`, `target_id`, `old_value/new_value` | `backend/app/Services/AuditLogService.php` atau panggilannya di `StockTransactionService` |

---

## MU1007 — Pemrograman Framework Lanjut

### CPMK-1: Routing, DI, state management

| # | Screenshot | Isi | Lokasi File / URL |
|---|---|---|---|
| 11 | **Scalar API Docs UI** | Buka `http://127.0.0.1:8080/api/docs` — scroll daftar endpoint per tag. **Bukti dokumentasi API otomatis dari OpenAPI 3.1 annotation.** | URL browser: `http://127.0.0.1:8080/api/docs` |
| 12 | **Route group** | `$routes->group("api/v1", ...)` — routing terstruktur per modul bisnis | `backend/app/Config/Routes.php` |
| 13 | **Dependency Injection — service layer** | Constructor controller: `$this->spkService = new SpkBasahGenerationService()` — controller tipis, logika bisnis di service | `backend/app/Controllers/Api/V1/SpkBasah.php` atau `StockTransactions.php` |
| 14 | **State management workflow** | Status transisi: `PENDING` (draft) → `APPROVED` (submit) / `REJECTED` (cancel) — tunjukkan logika if/switch status | `backend/app/Services/StockTransactionService.php` — method `createTransaction()` & `submitDraft()` |
| 15 | **TypeScript SDK — resource structure** | Daftar file di `frontend/src/sdk/resources/` — `auth.ts`, `users.ts`, `stockTransactions.ts`, `menuSchedules.ts`, `dailyPatients.ts`, dll. | `frontend/src/sdk/resources/` |
| 16 | **TypeScript SDK — unit test** | Test per resource — tunjukkan test coverage SDK | `frontend/src/sdk/tests/auth.test.ts` atau `stockTransactions.test.ts` |

### CPMK-2: Clean code & optimasi

| # | Screenshot | Isi | Lokasi File |
|---|---|---|---|
| 17 | **Single Responsibility — struktur folder** | `backend/app/` expanded: Controllers/, Services/, Models/, Database/Migrations/, OpenApi/, Enums/, Config/ | `backend/app/` |
| 18 | **Batch insert SPK** | `insertBatch()` — N baris spk_recommendations dalam satu query, bukan per baris | `backend/app/Services/SpkPersistenceService.php` atau `SpkBasahGenerationService.php` |
| 19 | **Transaction wrapping** | `$this->db->transactionStart()` + `transactionComplete()` — wrap multi-tabel: INSERT stock_tx + detail + UPDATE items.qty + UPDATE spk.is_finish | `backend/app/Services/SpkStockPostingService.php` method `post()` atau `StockTransactionService::submitDraft()` |
| 20 | **Idempotent snapshot / duplicate scope guard** | Cek duplikat sebelum insert snapshot (`ensureOpeningSnapshot`) atau scope_key + versioning untuk regenerasi SPK | `backend/app/Services/StockSnapshotService.php` atau `SpkPersistenceService.php` — method `createVersionedSpk()` |

---

## Ringkasan

| CPMK | Jumlah Screenshot |
|---|---|
| KMU1018 CPMK-1 (arsitektur & DB) | 4 |
| KMU1018 CPMK-2 (secure coding) | 6 |
| MU1007 CPMK-1 (routing, DI, state) | 6 |
| MU1007 CPMK-2 (clean code, optimasi) | 4 |
| **Total** | **20** |

> Tips: Bisa gabung beberapa poin dalam 1 screenshot. Prioritaskan yang paling visual — Scalar UI (#11), ERD Adminer (#1), migration (#2), atomic update query (#8), dan route group (#12).
