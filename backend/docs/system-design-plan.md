# System Design Plan — Sistem Informasi Manajemen Gudang dan SPK Instalasi Gizi RSD Balung

## 1. Executive Summary

Dokumen ini adalah ringkasan rencana desain sistem berdasarkan SRS dan DB diagram terbaru. Backend yang dipakai adalah **CodeIgniter 4 + MySQL** dengan pendekatan **workflow-centered modular monolith**.

Sistem difokuskan pada:

- kontrol inventaris bahan makanan;
- pengelolaan menu dan komposisi bahan;
- input pasien harian;
- perhitungan SPK dan rekomendasi belanja;
- approval revisi transaksi;
- audit dan pelaporan.

## 2. Planning Baseline

- **Framework**: CodeIgniter 4
- **DBMS**: MySQL 8.0
- **Architecture**: modular monolith
- **API Prefix**: `/api/v1`
- **Primary roles**: Super Admin, SPK/Gizi, Gudang

## 3. Core Planning Decisions

1. `items.qty` diperlakukan sebagai saldo operasional terkini, bukan angka bebas edit.
2. Perubahan stok normal dilakukan melalui `stock_transactions` dan `stock_transaction_details`.
3. Snapshot stok bulanan dipakai untuk kontrol periode dan rekonsiliasi.
4. Menu dihitung melalui relasi `menus -> menu_dishes -> dish_compositions -> items`.
5. SPK disimpan di `spk_calculations` dan `spk_recommendations`, bukan dihitung sementara saja.
6. Revisi transaksi mengikuti approval workflow menggunakan `approval_statuses`, `is_revision`, dan `parent_transaction_id`.
7. Audit log adalah bagian wajib dari desain.

## 4. Planned Modules

### 4.1 Lookup & Reference

- `item_categories`
- `transaction_types`
- `meal_times`
- `approval_statuses`
- `roles`

### 4.2 Users & Access

- `users`
- `roles`
- `audit_logs`

### 4.3 Inventory Master

- `items`
- `item_categories`

### 4.4 Inventory Operations

- `stock_transactions`
- `stock_transaction_details`
- `monthly_stock_snapshots`

### 4.5 Menu & Nutrition

- `menus`
- `menu_schedules`
- `dishes`
- `menu_dishes`
- `dish_compositions`

### 4.6 Daily Patient & SPK

- `daily_patients`
- `spk_calculations`
- `spk_recommendations`

## 5. Planned Workflow Coverage

### 5.1 User Management

- create user
- update user
- deactivate user
- soft delete user

### 5.2 Stock Operations

- create stock in transaction
- create stock out transaction
- create return in transaction
- submit revision transaction
- approve or reject revision
- create monthly stock snapshot

### 5.3 Menu Operations

- define dishes
- define dish compositions
- assign dishes to menu by meal time
- assign menu to day of month

### 5.4 SPK Operations

- record daily patient data
- generate SPK by category
- persist recommendation results
- mark SPK as finished/validated

## 6. Planning Risks

1. `spk_id` di `stock_transactions` terlihat wajib, sehingga perlu dikonfirmasi untuk transaksi manual.
2. Koreksi transaksi setelah snapshot bulanan dapat mengganggu konsistensi histori.
3. Perubahan dish composition dapat memengaruhi histori jika tidak dibekukan secara operasional.
4. Hasil SPK perlu aturan pembulatan dan penanganan nilai negatif.

## 7. Planned API Shape

Desain API mengikuti prinsip berikut:

- resource plural;
- endpoint command untuk workflow;
- response JSON konsisten;
- soft delete untuk tabel yang memiliki `deleted_at`;
- auth dan role check via filter.

## 8. Planning Conclusion

Rencana desain ini menempatkan sistem sebagai aplikasi operasional berbasis histori dan approval. Dengan demikian, fokus implementasi harus menjaga integritas stok, keterlacakan revisi, reprodusibilitas SPK, dan audit log yang konsisten.
