# Data Dictionary — Sistem Informasi Manajemen Gudang dan SPK Instalasi Gizi RSD Balung

## 1. Overview

Dokumen ini mendefinisikan data dictionary berdasarkan DB diagram terbaru untuk backend **CodeIgniter 4 + MySQL**.

Tujuan dokumen ini adalah:

- mendokumentasikan setiap tabel dan kolom penting;
- menjelaskan fungsi bisnis tiap tabel;
- mendokumentasikan foreign key dan constraint utama;
- menjadi referensi untuk implementasi model, migration, API, dan validasi.

## 2. Lookup Tables

### 2.1 `item_categories`

Fungsi: Menyimpan kategori barang sebagai lookup tetap.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID kategori barang |
| `name` | varchar(50) | not null, unique | Nama kategori: BASAH, KERING, PENGEMAS |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

### 2.2 `transaction_types`

Fungsi: Menyimpan tipe transaksi stok.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID tipe transaksi |
| `name` | varchar(50) | not null, unique | Nilai bisnis: IN, OUT, RETURN_IN |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

### 2.3 `meal_times`

Fungsi: Menyimpan waktu makan standar.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID waktu makan |
| `name` | varchar(50) | not null, unique | SIANG, SORE, PAGI |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

### 2.4 `approval_statuses`

Fungsi: Menyimpan status approval transaksi.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID status approval |
| `name` | varchar(50) | not null, unique | APPROVED, PENDING, REJECTED |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

## 3. Master Data & Users

### 3.1 `roles`

Fungsi: Menyimpan role utama pengguna.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | tinyint | PK | ID role |
| `name` | varchar(50) | not null, unique | admin, dapur, gudang |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

Supported roles:
- **admin**: Full system access, can manage users, roles, and all resources
- **dapur**: Kitchen and nutrition planning access
- **gudang**: Warehouse and inventory management access

### 3.2 `users`

Fungsi: Menyimpan akun pengguna sistem.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID user |
| `role_id` | tinyint | not null, FK | Relasi ke `roles.id` |
| `name` | varchar(255) | not null | Nama user |
| `username` | varchar(100) | not null, unique | Username login |
| `password` | varchar(255) | nullable | Password hash legacy/app field |
| `email` | varchar(255) | nullable | Email opsional untuk profil/internal use |
| `is_active` | boolean | default true | Status aktif user - controls login access |
| `last_active` | timestamp | nullable | Waktu akses terakhir |
| `status` | varchar(255) | nullable | Status auth tambahan kompatibel dengan Shield |
| `status_message` | varchar(255) | nullable | Pesan status auth |
| `active` | boolean | default false | Flag aktivasi yang dipakai provider auth - synced with is_active |
| `force_pass_reset` | boolean | default false | Penanda paksa reset password |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |
| `deleted_at` | timestamp | nullable | Soft delete marker |

**User Management Behavior:**

- **Deactivation**: Setting `is_active` and `active` to `false` blocks user from logging in. Existing tokens remain valid until revoked separately.
- **Password Change**: Changing a user's password automatically revokes ALL their access tokens via `auth_identities` table. User must log in again with the new password, and the password update must go through the Shield user entity/save flow.
- **Soft Delete**: Soft-deleting a user (`deleted_at` set) automatically revokes ALL their access tokens. Deleted users do not appear in user listings and cannot log in.
- **Deleted User Mutations**: Soft-deleted users are treated as absent resources for update, activate, deactivate, password-change, and delete operations.
- **Token Revocation**: Tokens are revoked by removing entries from `auth_identities` table where `type = 'access_token'` and `user_id` matches the target user.
- **Role Assignment**: Users can only be assigned one of the three supported roles: admin, dapur, or gudang. Role changes are tracked via `updated_at`.
- **Email Field**: Email is optional but recommended for user identification and recovery workflows.
- **Active Flag Sync**: Both `is_active` (application-level) and `active` (Shield-level) must be kept in sync during user creation, update, activation, and deactivation operations.

### 3.3 `auth_identities`

Fungsi: Menyimpan identitas autentikasi dan personal access token untuk Shield-compatible API auth.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | int | PK, increment | ID identity |
| `user_id` | bigint | not null, FK | Relasi ke `users.id` |
| `type` | varchar(255) | not null | Tipe identity, mis. `email_password`, `access_token`, `username` |
| `name` | varchar(255) | nullable | Nama token/identity |
| `secret` | varchar(255) | not null | Secret atau hash token |
| `secret2` | varchar(255) | nullable | Hash password / secret tambahan |
| `expires` | timestamp | nullable | Waktu kedaluwarsa token |
| `extra` | text | nullable | Scope atau metadata tambahan |
| `force_reset` | boolean | default false | Penanda paksa reset |
| `last_used_at` | timestamp | nullable | Waktu terakhir dipakai |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

### 3.4 `auth_logins`

Fungsi: Audit trail untuk percobaan login berbasis credential.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | int | PK, increment | ID login attempt |
| `ip_address` | varchar(255) | not null | IP address yang melakukan attempt |
| `user_agent` | varchar(255) | nullable | Browser/client user agent |
| `id_type` | varchar(255) | not null | Tipe identifier (email, username) |
| `identifier` | varchar(255) | not null | Nilai identifier yang digunakan |
| `user_id` | bigint | nullable | ID user jika berhasil login |
| `date` | timestamp | not null | Waktu attempt |
| `success` | boolean | not null | Berhasil atau gagal |

### 3.5 `auth_token_logins`

Fungsi: Audit trail untuk penggunaan Bearer token.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | int | PK, increment | ID token login attempt |
| `ip_address` | varchar(255) | not null | IP address yang menggunakan token |
| `user_agent` | varchar(255) | nullable | Browser/client user agent |
| `id_type` | varchar(255) | not null | Tipe token |
| `identifier` | varchar(255) | not null | Token identifier |
| `user_id` | bigint | nullable | ID user jika token valid |
| `date` | timestamp | not null | Waktu penggunaan |
| `success` | boolean | not null | Valid atau invalid |

### 3.6 `auth_remember_tokens`

Fungsi: Menyimpan remember-me token untuk Shield session authenticator.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | int | PK, increment | ID remember token |
| `selector` | varchar(255) | not null, unique | Token selector publik |
| `hashedValidator` | varchar(255) | not null | Hash validator untuk validasi |
| `user_id` | bigint | not null, FK | Relasi ke `users.id` |
| `expires` | timestamp | not null | Waktu kedaluwarsa token |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

**Note:** Foreign key `user_id` CASCADE on delete — menghapus user otomatis menghapus remember token mereka.

### 3.7 `auth_groups_users`

Fungsi: Menyimpan relasi user ke Shield authorization groups untuk kompatibilitas internal Shield.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | int | PK, increment | ID group assignment |
| `user_id` | bigint | not null, FK | Relasi ke `users.id` |
| `group` | varchar(255) | not null | Nama group Shield |
| `created_at` | timestamp | nullable | Waktu dibuat |

**Important:** Tabel ini untuk kompatibilitas Shield internal saja. **Business authorization menggunakan tabel `roles`**, bukan Shield groups. Tabel ini TIDAK digunakan untuk authorization flow aplikasi utama.

**Note:** Foreign key `user_id` CASCADE on delete — menghapus user otomatis menghapus group assignments mereka.

### 3.8 `auth_permissions_users`

Fungsi: Menyimpan relasi user ke Shield authorization permissions untuk kompatibilitas internal Shield.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | int | PK, increment | ID permission assignment |
| `user_id` | bigint | not null, FK | Relasi ke `users.id` |
| `permission` | varchar(255) | not null | Nama permission Shield |
| `created_at` | timestamp | nullable | Waktu dibuat |

**Important:** Tabel ini untuk kompatibilitas Shield internal saja. **Business authorization menggunakan tabel `roles`**, bukan Shield permissions. Tabel ini TIDAK digunakan untuk authorization flow aplikasi utama.

**Note:** Foreign key `user_id` CASCADE on delete — menghapus user otomatis menghapus permission assignments mereka.

### 3.9 `settings`

Fungsi: Menyimpan konfigurasi setting yang dibutuhkan oleh package Settings/Shield.

### 3.10 `items`

Fungsi: Menyimpan master barang dan saldo stok berjalan.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID item |
| `name` | varchar(100) | not null | Nama barang |
| `item_category_id` | bigint | not null, FK | Relasi ke `item_categories.id` |
| `unit_base` | varchar(20) | not null | Satuan terkecil / dapur |
| `unit_convert` | varchar(20) | not null | Satuan besar / gudang |
| `conversion_base` | int | not null | Nilai konversi dari satuan gudang ke satuan dasar |
| `is_active` | boolean | default true | Status aktif item |
| `qty` | decimal(12,2) | default 0 | Stok berjalan saat ini dalam satuan dasar |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |
| `deleted_at` | timestamp | nullable | Soft delete marker |

Perilaku Phase 1 item master API:

- `name` diperlakukan unik secara global pada item master.
- `qty` wajib dianggap read-only pada endpoint item master.
- `item_category_id` harus merujuk ke kategori barang yang sudah ada.

## 4. Inventory & Stock Logic

### 4.1 `monthly_stock_snapshots`

Fungsi: Menyimpan snapshot stok awal bulanan per item untuk kontrol periode.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID snapshot |
| `period_month` | date | not null | Periode bulan dengan format YYYY-MM |
| `item_id` | bigint | not null, FK | Relasi ke `items.id` |
| `opening_qty` | decimal(12,2) | not null | Stok awal item pada awal bulan |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

Constraint utama:

- unique `(period_month, item_id)` untuk mencegah snapshot ganda pada item dan bulan yang sama.

### 4.2 `stock_transactions`

Fungsi: Header transaksi stok, termasuk transaksi normal dan revisi.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID transaksi |
| `type_id` | bigint | not null, FK | Relasi ke `transaction_types.id` |
| `transaction_date` | date | not null | Tanggal transaksi |
| `is_revision` | boolean | default false | Penanda transaksi revisi |
| `parent_transaction_id` | bigint | nullable, self FK | Referensi transaksi asal jika ini revisi |
| `approval_status_id` | bigint | default 1, FK | Relasi ke `approval_statuses.id` |
| `approved_by` | bigint | nullable, FK | User Admin yang menyetujui |
| `user_id` | bigint | not null, FK | User pembuat transaksi |
| `spk_id` | bigint | nullable | Relasi opsional ke `spk_calculations.id` pada fase integrasi SPK |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |
| `deleted_at` | timestamp | nullable | Soft delete marker |

Catatan desain:

- Pada Milestone 1, `spk_id` dibuat nullable agar transaksi manual gudang tidak terblokir oleh modul SPK yang belum selesai.
- Revisi transaksi tidak menghapus transaksi asal, tetapi menaut ke `parent_transaction_id`.

### 4.3 `stock_transaction_details`

Fungsi: Detail item untuk setiap transaksi stok.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID detail transaksi |
| `transaction_id` | bigint | not null, FK | Relasi ke `stock_transactions.id` |
| `item_id` | bigint | not null, FK | Relasi ke `items.id` |
| `qty` | decimal(12,2) | not null | Jumlah item pada transaksi |

Constraint utama:

- unique `(transaction_id, item_id)` untuk mencegah item yang sama muncul dua kali dalam satu transaksi.

## 5. Daily Patients & Menu

### 5.1 `daily_patients`

Fungsi: Menyimpan jumlah pasien harian sebagai basis operasional dan input SPK.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID log pasien harian |
| `total_patient` | int | not null | Jumlah total pasien pada hari tersebut |
| `created_at` | timestamp | nullable | Tanggal/waktu pencatatan |

Catatan desain:

- Tabel ini bersifat log operasional harian, bukan master pasien.

### 5.2 `menus`

Fungsi: Menyimpan menu siklus utama, misalnya menu 1 s/d 11.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | tinyint | PK | ID menu siklus |
| `name` | varchar(100) | not null | Nama menu |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

### 5.3 `menu_schedules`

Fungsi: Menyimpan pemetaan tanggal ke menu.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID jadwal menu |
| `day_of_month` | int | not null | Tanggal 1 s/d 31 |
| `menu_id` | tinyint | not null, FK | Relasi ke `menus.id` |

Constraint utama:

- unique `day_of_month` untuk memastikan satu tanggal hanya memiliki satu jadwal menu.

### 5.4 `dishes`

Fungsi: Menyimpan daftar dish/hidangan.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID dish |
| `name` | varchar(100) | not null | Nama dish |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

### 5.5 `menu_dishes`

Fungsi: Menghubungkan menu dengan dish pada waktu makan tertentu.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID relasi menu-dish |
| `menu_id` | tinyint | not null, FK | Relasi ke `menus.id` |
| `meal_time_id` | tinyint | not null, FK | Relasi ke `meal_times.id` |
| `dish_id` | bigint | not null, FK | Relasi ke `dishes.id` |

### 5.6 `dish_compositions`

Fungsi: Menyimpan komposisi item per dish.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID komposisi |
| `item_id` | bigint | not null, FK | Relasi ke `items.id` |
| `dish_id` | bigint | not null, FK | Relasi ke `dishes.id` |
| `qty_per_patient` | decimal(10,2) | not null | Kebutuhan item per pasien untuk dish tersebut |

Constraint utama:

- unique `(dish_id, item_id)` untuk mencegah komposisi item ganda pada dish yang sama.

## 6. SPK & Recommendations

### 6.1 `spk_calculations`

Fungsi: Menyimpan header perhitungan SPK.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID kalkulasi SPK |
| `calculation_date` | date | not null | Tanggal kalkulasi dibuat |
| `target_date_start` | date | not null | Tanggal awal target belanja |
| `target_date_end` | date | not null | Tanggal akhir target belanja |
| `daily_patient_id` | bigint | nullable, FK | Relasi ke `daily_patients.id` |
| `user_id` | bigint | not null, FK | User pembuat SPK |
| `category_id` | tinyint | not null, FK | Relasi ke `item_categories.id` |
| `estimated_patients` | int | not null | Jumlah pasien terkunci saat generate |
| `is_finish` | bool | default false | Status finalisasi / validasi SPK |
| `created_at` | timestamp | nullable | Waktu dibuat |
| `updated_at` | timestamp | nullable | Waktu diperbarui |

### 6.2 `spk_recommendations`

Fungsi: Menyimpan hasil rekomendasi item untuk satu kalkulasi SPK.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID rekomendasi |
| `spk_id` | bigint | not null, FK | Relasi ke `spk_calculations.id` |
| `item_id` | bigint | not null, FK | Relasi ke `items.id` |
| `qty` | decimal(12,2) | not null | Jumlah rekomendasi belanja |

Constraint utama:

- unique `(spk_id, item_id)` untuk mencegah item ganda pada satu hasil SPK.

## 7. Audit Logging

### 7.1 `audit_logs`

Fungsi: Menyimpan histori aktivitas penting pengguna dan sistem.

| Column | Type | Constraint | Description |
|---|---|---|---|
| `id` | bigint | PK, increment | ID log |
| `user_id` | bigint | nullable, FK | User pelaku, null jika oleh sistem |
| `action_type` | varchar(50) | not null | Jenis aksi yang dicatat, mis. `stock_transaction_create` |
| `table_name` | varchar(100) | not null | Nama tabel target perubahan |
| `record_id` | bigint | not null | ID record target |
| `message` | text | nullable | Pesan ringkas log |
| `old_values` | json | nullable | Nilai lama sebelum perubahan |
| `new_values` | json | nullable | Nilai baru setelah perubahan |
| `ip_address` | varchar(45) | nullable | IP address pelaku |
| `created_at` | timestamp | nullable | Waktu kejadian |

Index utama:

- `(table_name, record_id)` untuk pencarian histori per record
- `user_id` untuk pencarian histori per user

## 8. Relationships Summary

### 8.1 Lookup Relations

- `items.item_category_id -> item_categories.id`
- `stock_transactions.type_id -> transaction_types.id`
- `stock_transactions.approval_status_id -> approval_statuses.id`
- `menu_dishes.meal_time_id -> meal_times.id`
- `spk_calculations.category_id -> item_categories.id`

### 8.2 User Relations

- `users.role_id -> roles.id`
- `audit_logs.user_id -> users.id`
- `stock_transactions.user_id -> users.id`
- `stock_transactions.approved_by -> users.id`
- `spk_calculations.user_id -> users.id`

### 8.3 Inventory Relations

- `stock_transactions.parent_transaction_id -> stock_transactions.id`
- `stock_transaction_details.transaction_id -> stock_transactions.id`
- `stock_transaction_details.item_id -> items.id`
- `monthly_stock_snapshots.item_id -> items.id`

### 8.4 Menu Relations

- `menu_schedules.menu_id -> menus.id`
- `menu_dishes.menu_id -> menus.id`
- `menu_dishes.dish_id -> dishes.id`
- `dish_compositions.dish_id -> dishes.id`
- `dish_compositions.item_id -> items.id`

### 8.5 SPK Relations

- `spk_calculations.daily_patient_id -> daily_patients.id`
- `spk_recommendations.spk_id -> spk_calculations.id`
- `spk_recommendations.item_id -> items.id`
- `stock_transactions.spk_id -> spk_calculations.id`

## 9. Data Integrity Notes

1. `items.qty` harus dijaga melalui alur transaksi stok, bukan update manual sembarangan.
2. Revisi transaksi harus tetap menjaga histori transaksi asal melalui `parent_transaction_id`.
3. `monthly_stock_snapshots` perlu aturan tambahan jika ada koreksi backdated.
4. Perubahan `dish_compositions` setelah menu aktif perlu kebijakan histori agar SPK lama tetap konsisten.
5. `spk_recommendations` tidak boleh berdiri sendiri tanpa `spk_calculations`.
6. Audit log harus dicatat untuk aksi penting dan approval workflow.

## 10. Open Questions

1. Apakah `stock_transactions.spk_id` memang wajib untuk semua transaksi, termasuk transaksi manual.
2. Apakah perlu penanda tanggal eksplisit pada `daily_patients` selain `created_at`.
3. Apakah menu historis perlu dibekukan saat dish composition berubah.
4. Bagaimana aturan koreksi transaksi setelah snapshot bulanan terbentuk.
