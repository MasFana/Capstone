# Functional Requirement (FR) Traceability Matrix

## 1. Overview

Dokumen ini memetakan Functional Requirements (FR-01 s/d FR-26) dari SRS ke implementasi teknis (route, service, test) dan bukti eksekusi (evidence). Matriks ini berfungsi sebagai instrumen tata kelola untuk memastikan setiap kebutuhan bisnis telah terpenuhi, diuji, dan memiliki bukti artefak yang valid.

## 2. Evidence Contract

Semua bukti eksekusi (evidence) harus mengikuti konvensi penamaan dan lokasi berikut:

- **Path:** `.sisyphus/evidence/task-{N}-{slug}.{ext}`
- **Format:** `.txt` untuk output CLI/Log, `.png` untuk screenshot UI/Playwright.
- **Requirement:** Setiap baris FR yang berstatus `implemented` atau `verified` WAJIB memiliki link evidence yang valid.

## 3. Status Vocabulary

| Status | Description |
|---|---|
| `planned` | Fitur telah didefinisikan dalam desain namun belum mulai diimplementasikan. |
| `in_progress` | Fitur sedang dalam tahap pengembangan aktif. |
| `implemented` | Kode backend (route/service) telah selesai namun verifikasi QA penuh belum tuntas. |
| `verified` | Fitur telah lulus pengujian automated (unit/feature/E2E) dan memiliki evidence valid. |
| `blocked` | Pengembangan fitur terhenti karena dependensi atau masalah teknis tertentu. |

## 4. Traceability Matrix (FR-01 .. FR-26)

| fr_id | status | owner | sprint | route | service | test | evidence | risk | notes |
|---|---|---|---|---|---|---|---|---|---|
| FR-01 | verified | backend | 1 | `/api/v1/users` | `UserManagementService` | `UsersTest.php` | `.sisyphus/evidence/task-0-auth-baseline.txt` | low | CRUD akun user (Admin) |
| FR-02 | verified | backend | 1 | (Multiple) | (Multiple Models) | `UsersTest.php` | `.sisyphus/evidence/task-0-soft-delete.txt` | low | Soft delete integrity |
| FR-03 | verified | backend | 1 | `/api/v1/auth/login` | `AuthService` | `AuthTest.php` | `.sisyphus/evidence/task-0-encryption.txt` | low | Password Bcrypt encryption |
| FR-04 | verified | backend | 1 | `/api/v1/items` | `ItemManagementService` | `ItemsTest.php` | `.sisyphus/evidence/task-0-master-crud.txt` | low | CRUD kategori, satuan, barang |
| FR-05 | verified | backend | 1 | `/api/v1/items` | `ItemModel` | `ItemsTest.php` | `.sisyphus/evidence/task-0-min-stock.txt` | medium | Atribut stok minimal |
| FR-06 | verified | backend | 1 | `/api/v1/items` | `ItemModel` | `ItemsTest.php` | `.sisyphus/evidence/task-0-unique-name.txt` | medium | Validasi keunikan barang |
| FR-07 | verified | backend | 1 | `/api/v1/dishes` | `MenuPlanningService` | `DishesTest.php` | `.sisyphus/evidence/task-0-dishes.txt` | low | Menu tunggal & komposisi |
| FR-08 | verified | backend | 1 | `/api/v1/menus` | `MenuPlanningService` | `MenusTest.php` | `.sisyphus/evidence/task-0-packages.txt` | low | Paket Menu 1-11 |
| FR-09 | verified | backend | 1 | `/api/v1/menu-calendar` | `MenuSchedules` | `MenuCalendarTest.php` | `.sisyphus/evidence/task-0-calendar-rules.txt` | high | Otomasi kalender menu |
| FR-10 | planned | frontend | 3 | `/api/v1/menu-calendar` | `n/a` | `n/a` | (Pending) | medium | Tampilan kalender interaktif |
| FR-11 | verified | backend | 1 | (Multiple) | `SpkCalculationService` | `SpkCalculationTest.php` | `.sisyphus/evidence/task-0-spk-sync.txt` | high | Sinkronisasi komposisi ke SPK |
| FR-12 | planned | backend | 3 | `/api/v1/reports/export-pdf` | `ReportingService` | `n/a` | (Pending) | low | Ekspor laporan Paket Menu |
| FR-13 | verified | backend | 2 | `/api/v1/stock-transactions/{id}/submit-revision` | `StockTransactionService` | `StockTransactionsTest.php` | `.sisyphus/evidence/task-4-stock-opname.txt` | high | Kunci data & revisi approval |
| FR-14 | verified | backend | 2 | `/api/v1/spk/*/post-stock` | `SpkCalculationService` | `SpkCalculationTest.php` | `.sisyphus/evidence/task-0-stock-auto-dec.txt` | high | Hitung pengurangan stok otomatis |
| FR-15 | planned | backend | 3 | `/api/v1/reports/export-pdf` | `ReportingService` | `n/a` | (Pending) | low | Ekspor riwayat transaksi & revisi |
| FR-16 | planned | frontend | 2 | `/api/v1/dashboard` | `DashboardQueryService` | `n/a` | (Pending) | medium | Indikator visual stok kritis |
| FR-17 | planned | backend | 3 | `/api/v1/reports/export-pdf` | `ReportingService` | `n/a` | (Pending) | low | Ekspor data stok ke PDF |
| FR-18 | verified | backend | 1 | `/api/v1/spk/*/generate` | `SpkCalculationService` | `SpkCalculationTest.php` | `.sisyphus/evidence/task-0-spk-calc.txt` | high | Kalkulasi rekomendasi belanja otomatis |
| FR-19 | verified | backend | 1 | (Multiple) | `SpkCalculationService` | `SpkHistoryPersistenceTest.php` | `.sisyphus/evidence/task-0-spk-persistence.txt` | medium | Simpan hasil kalkulasi SPK permanen |
| FR-20 | planned | backend | 3 | `/api/v1/reports/export-pdf` | `ReportingService` | `n/a` | (Pending) | low | Ekspor rekomendasi belanja aktif |
| FR-21 | planned | backend | 3 | `/api/v1/reports/export-pdf` | `ReportingService` | `n/a` | (Pending) | low | Ekspor riwayat SPK |
| FR-22 | planned | backend | 2 | `/api/v1/dashboard` | `DashboardQueryService` | `n/a` | (Pending) | medium | Dashboard analitik role-based |
| FR-23 | verified | backend | 1 | `n/a` | `AuditService` | `AuditLogTest.php` | `.sisyphus/evidence/task-0-audit-logs.txt` | high | Log aktivitas permanen (Immutable) |
| FR-24 | planned | frontend | 3 | `/api/v1/dashboard` | `n/a` | `n/a` | (Pending) | medium | Visualisasi grafik tren & populasi |
| FR-25 | planned | backend | 3 | `/api/v1/reports/export-pdf` | `ReportingService` | `n/a` | (Pending) | high | Laporan Evaluasi (SPK vs Realisasi) |
| FR-26 | verified | backend | 1 | `/api/v1/spk/*/history` | `SpkCalculationService` | `SpkHistoryPersistenceTest.php` | `.sisyphus/evidence/task-0-spk-history-access.txt` | low | Akses riwayat terakhir SPK |
