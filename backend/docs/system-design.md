# System Design — Sistem Informasi Manajemen Gudang dan SPK Instalasi Gizi RSD Balung

## 1. Overview

Dokumen ini merangkum desain sistem berdasarkan SRS dan diselaraskan dengan DB diagram terbaru untuk **Sistem Informasi Manajemen Gudang dan Pengambilan Keputusan (SPK) Instalasi Gizi RSD Balung**.

Baseline desain yang dipakai:

- **Backend**: CodeIgniter 4
- **Database**: MySQL 8.0
- **Architecture style**: Modular monolith
- **API style**: RESTful API dengan endpoint workflow untuk proses operasional
- **Primary users**: admin, dapur, gudang

Sistem ini adalah aplikasi internal rumah sakit untuk mengelola inventaris bahan makanan, siklus menu, input pasien harian, transaksi stok, rekomendasi belanja berbasis SPK, audit log, dan pelaporan.

## 2. Goals and Scope

### 2.1 Goals

Sistem dirancang untuk:

1. Menggantikan pencatatan stok manual dan spreadsheet yang terpisah.
2. Menjaga akurasi stok masuk, stok keluar, retur, dan stok tersedia.
3. Mendukung pengelolaan menu harian dan komposisi bahan secara terstruktur.
4. Menghasilkan rekomendasi belanja berdasarkan pasien, menu, histori, dan stok.
5. Menyediakan alur approval revisi dan audit trail untuk akuntabilitas.

### 2.2 In Scope

- Manajemen role dan user
- Lookup table bisnis: kategori barang, tipe transaksi, waktu makan, status approval
- Master data barang dengan satuan dasar dan konversi
- Pencatatan barang masuk, keluar, dan retur
- Snapshot stok bulanan
- Pengelolaan menu, dish, komposisi bahan, dan jadwal menu
- Input pasien harian
- Generate dan penyimpanan hasil SPK
- Audit log dan ekspor laporan

### 2.3 Out of Scope

- Integrasi langsung ke SIMRS pusat pada fase awal
- Microservices
- Vendor purchasing eksternal
- Mobile app native terpisah

## 3. Recommended Architecture

### 3.1 Architecture Style

Arsitektur yang direkomendasikan tetap **modular monolith**, tetapi sifat sistem ini bukan sekadar CRUD biasa. Skema database menunjukkan bahwa ini adalah **workflow-centered operational system** dengan constraint waktu, approval, histori, dan audit.

### 3.2 High-Level Architecture

```text
Client Web / Browser
        |
        v
CodeIgniter 4 HTTP Layer
  - Routes
  - Filters (Auth, RBAC)
  - Controllers
        |
        v
Application Services
  - User Access Service
  - Master Data Service
  - Inventory Transaction Service
  - Menu Planning Service
  - Daily Patient Service
  - SPK Calculation Service
  - Reporting Service
  - Audit Service
        |
        v
Persistence Layer
  - Models / Entities
  - MySQL Database
        |
        +--> PDF Export
        +--> Dashboard Aggregation Queries
```

### 3.3 Core Architectural Principles

1. **Stock changes flow through transactions**, bukan lewat edit langsung terhadap saldo barang.
2. **`items.qty` adalah current operational balance**, tetapi harus dianggap sebagai nilai turunan/terkendali dari histori transaksi dan penyesuaian.
3. **Approval dan revision adalah bagian domain**, bukan sekadar status kosmetik.
4. **SPK adalah persisted decision support**, bukan kalkulasi sementara.
5. **Menu dan pasien bersifat time-based input**, sehingga histori harus tetap dapat direproduksi.
6. **Audit logging adalah kebutuhan inti sistem**.

## 4. Schema-Aligned Logical Modules

### 4.1 Lookup & Reference Module

Tabel utama:

- `item_categories`
- `transaction_types`
- `meal_times`
- `approval_statuses`
- `roles`

Peran modul ini adalah menyediakan vocabulary bisnis yang stabil untuk seluruh sistem. Nilai lookup ini tidak boleh diperlakukan sebagai enum liar di level aplikasi.

### 4.2 User & Access Module

Tabel utama:

- `users`
- `roles`
- `audit_logs`

Aturan utama:

- `users.deleted_at` dipakai untuk soft delete
- `users.is_active` dipakai untuk aktivasi/nonaktivasi akun
- `role_id` menentukan kewenangan utama pengguna

### 4.3 Master Inventory Module

Tabel utama:

- `items`
- `item_categories`

Tabel `items` memuat:

- nama barang
- kategori barang
- `unit_base` sebagai satuan terkecil/dapur
- `unit_convert` sebagai satuan besar/gudang
- `conversion_base` sebagai faktor konversi
- `qty` sebagai stok berjalan saat ini
- status aktif/nonaktif

Catatan implementasi Milestone 1:

- Milestone 1 backend kini mencakup item master CRUD **dan** inventory operations core.
- `admin` dan `gudang` dapat melihat, membuat, dan memperbarui item master.
- soft delete item master dibatasi ke `admin`.
- transaksi stok normal sudah tersedia melalui `stock_transactions` dan `stock_transaction_details`.
- approval/revision action endpoint serta monthly snapshot endpoint tetap ditunda ke milestone berikutnya.

### 4.4 Inventory Transaction Module

Tabel utama:

- `stock_transactions`
- `stock_transaction_details`
- `monthly_stock_snapshots`
- `approval_statuses`
- `transaction_types`

Sinyal desain dari schema ini:

- `stock_transactions` adalah header transaksi operasional
- `stock_transaction_details` adalah detail item per transaksi
- `monthly_stock_snapshots` menyimpan saldo awal/periode bulanan
- `is_revision`, `parent_transaction_id`, `approval_status_id`, dan `approved_by` menunjukkan bahwa approval flow adalah bagian inti domain

### 4.5 Menu & Nutrition Module

Tabel utama:

- `menus`
- `menu_schedules`
- `dishes`
- `menu_dishes`
- `dish_compositions`
- `meal_times`

Relasi logisnya adalah:

```text
menus -> menu_dishes -> dishes -> dish_compositions -> items
menu_schedules -> menus
menu_dishes -> meal_times
```

Artinya menu bukan konten statis, melainkan template operasional yang dijadwalkan dan diturunkan menjadi kebutuhan bahan.

### 4.6 Daily Patient & SPK Module

Tabel utama:

- `daily_patients`
- `spk_calculations`
- `spk_recommendations`
- `item_categories`

Makna desain:

- `daily_patients` adalah input operasional per hari, bukan master pasien
- `spk_calculations` menyimpan header kalkulasi
- `spk_recommendations` menyimpan hasil item per kalkulasi
- `is_finish` menunjukkan hasil SPK bisa memiliki lifecycle validasi/penyelesaian

### 4.7 Audit Module

Tabel utama:

- `audit_logs`

Audit log mencatat siapa melakukan apa, pada tabel apa, terhadap record mana, nilai lama, nilai baru, alamat IP, dan waktu kejadian.

## 5. Authoritative Data vs Derived Data

### 5.1 Authoritative Data

Data yang harus diperlakukan sebagai sumber kebenaran domain:

- `stock_transactions`
- `stock_transaction_details`
- `monthly_stock_snapshots`
- `menu_schedules`
- `menu_dishes`
- `dish_compositions`
- `daily_patients`
- `spk_calculations`
- `spk_recommendations`
- `audit_logs`

### 5.2 Derived / Operationally Cached Data

- `items.qty` adalah saldo operasional saat ini
- dashboard analytics adalah hasil agregasi
- laporan tertentu adalah hasil query dari histori final

Dokumentasi implementasi harus menegaskan bahwa update terhadap `items.qty` hanya boleh terjadi melalui alur transaksi stok, retur, revisi yang disetujui, atau stock snapshot/reconciliation resmi.

## 6. Key Business Rules

### 6.1 User and Access Rules

- Hanya user berotoritas tinggi yang boleh mengelola akun user lain.
- Password wajib disimpan dalam bentuk hash.
- Soft delete dilakukan melalui `deleted_at`.
- `is_active=false` berarti user tidak dapat memakai sistem walaupun record masih ada.

### 6.2 Inventory Rules

- Tipe transaksi dikendalikan oleh `transaction_types`.
- Status approval dikendalikan oleh `approval_statuses`.
- Revisi transaksi direpresentasikan oleh `is_revision=true` dan `parent_transaction_id` yang menunjuk transaksi asal.
- Approval revisi direkam melalui `approval_status_id` dan `approved_by`.
- Setiap transaksi memiliki satu atau lebih detail item.
- Satu item tidak boleh muncul ganda di transaksi yang sama.

### 6.3 Stock Rules

- `qty` pada `items` disimpan dalam satuan dasar.
- Konversi gudang ke dapur mengikuti `conversion_base`.
- Snapshot bulanan dipakai untuk kontrol periode dan rekonsiliasi.
- Backdated correction harus diperlakukan hati-hati karena dapat memengaruhi snapshot bulanan.

### 6.4 Menu Rules

- `menus.id` merepresentasikan siklus menu 1 s/d 11.
- `menu_schedules.day_of_month` memetakan tanggal ke menu tertentu.
- `menu_dishes` menentukan dish apa yang disajikan pada waktu makan tertentu.
- `dish_compositions` menentukan item dan jumlah per pasien untuk setiap dish.

### 6.5 SPK Rules

#### Bahan basah

```text
(Jumlah Pasien Terakhir × 105%) × Komposisi − Sisa Stok
```

#### Bahan kering dan pengemas

```text
(Total Penggunaan Bulan Lalu × 110%) − Sisa Stok
```

Aturan desain yang perlu dijaga:

- hasil SPK disimpan, tidak overwrite hasil lama;
- rekomendasi selalu terkait ke satu `spk_calculations` tertentu;
- kategori SPK ditentukan oleh `category_id`;
- `estimated_patients` adalah angka yang dikunci saat generate.

## 7. Domain Invariants

Invariant penting yang harus dijaga walaupun sebagian aturan belum sepenuhnya diwajibkan oleh schema:

1. Setiap `stock_transaction_details` harus terhubung ke satu `stock_transactions` dan satu `items`.
2. Setiap `spk_recommendations` harus terhubung ke satu `spk_calculations` dan satu `items`.
3. Setiap `dish_compositions` harus terhubung ke satu `dishes` dan satu `items`.
4. Setiap `menu_dishes` harus menunjuk satu `menus`, satu `dishes`, dan satu `meal_times`.
5. Perubahan transaksi yang sudah disetujui tidak boleh menghapus histori asal.
6. Audit log harus tersedia untuk aksi penting seperti login, create, update, delete, dan approval.

## 8. Workflow Design

### 8.1 Barang Masuk

1. User gudang membuat `stock_transactions` dengan `type_id=IN`.
2. User mengisi `stock_transaction_details`.
3. Sistem memvalidasi item dan qty.
4. Sistem menaikkan `items.qty` dalam satuan dasar.
5. Sistem mencatat audit log.

### 8.2 Barang Keluar

1. User gudang membuat transaksi `OUT`.
2. Detail item disimpan di `stock_transaction_details`.
3. Sistem mengurangi `items.qty`.
4. Sistem mencatat audit log dan histori transaksi.

### 8.3 Return In

1. User gudang membuat transaksi `RETURN_IN`.
2. Sistem menambah kembali `items.qty` sesuai jumlah retur.
3. Sistem mencatat audit log.

### 8.4 Revision and Approval

1. User membuat transaksi revisi dengan `is_revision=true`.
2. `parent_transaction_id` menunjuk transaksi asal.
3. `approval_status_id` berubah ke `PENDING`.
4. admin meninjau.
5. Selama masih `PENDING`, revisi belum mengubah `items.qty`.
6. Jika disetujui, `approval_status_id` menjadi `APPROVED`, `approved_by` diisi, dan mutasi qty revisi diterapkan.
7. Jika ditolak, status menjadi `REJECTED` tanpa perubahan qty.

### 8.5 Menu Scheduling and Consumption Base

1. Tim gizi mendefinisikan dish dan komposisinya.
2. Tim gizi menyusun dish ke menu pada `menu_dishes`.
3. Sistem menjadwalkan menu per tanggal melalui `menu_schedules`.
4. Kebutuhan item dihitung dari `dish_compositions × total_patient`.

### 8.6 Generate SPK

1. User dapur membuat `spk_calculations`.
2. Sistem mengunci parameter utama seperti `category_id`, `estimated_patients`, tanggal target, dan referensi `daily_patient_id`.
3. Sistem menghitung rekomendasi per item.
4. Sistem menyimpan hasil ke `spk_recommendations`.
5. Jika hasil telah divalidasi, `is_finish` ditandai selesai.

## 9. Role-Based Access Matrix

| Feature / Action | admin | dapur | gudang |
|---|---|---|---|
| Login | Yes | Yes | Yes |
| Manage users | Yes | No | No |
| Manage roles | Yes | No | No |
| Manage lookup data | Yes | Limited | No |
| Manage items | Yes | No | Yes |
| Manage dishes and compositions | Yes | Yes | No |
| Manage menu schedules | Yes | Yes | No |
| Input daily patients | Yes | Yes | Limited |
| Create stock in/out/return | Yes | No | Yes |
| Submit revision | Yes | No | Yes |
| Approve revision | Yes | No | No |
| Generate SPK | Yes | Yes | No |
| Finalize SPK | Yes | Yes | No |
| View dashboard | Yes | Yes | Yes |
| View audit logs | Yes | No | No |
| Export reports | Yes | Yes | Yes |

## 10. Non-Functional Design Notes

### 10.1 Security

- gunakan password hashing standar;
- lindungi endpoint sensitif dengan auth filter dan role check;
- log semua aksi penting;
- audit log hanya dapat diakses pihak berwenang.

### 10.2 Reliability

- perubahan stok dan detail transaksi harus berada pada database transaction yang sama;
- perubahan status approval harus tercatat;
- snapshot bulanan harus dilindungi dari koreksi yang tidak terdokumentasi.

### 10.3 Performance

- query dashboard menggunakan agregasi dan index sesuai kebutuhan;
- gunakan index gabungan pada data periodik dan historis;
- ekspor PDF untuk data besar dapat dipisahkan dari request sinkron jika dibutuhkan.

### 10.4 Temporal Integrity

- perubahan dish setelah jadwal berjalan harus dikontrol agar histori menu tidak ambigu;
- transaksi backdated setelah snapshot bulanan perlu aturan reopening/reconciliation;
- hasil SPK historis tidak boleh tertimpa oleh generate ulang.

## 11. CodeIgniter 4 Notes

- gunakan route prefix `/api/v1`;
- gunakan plural resource names;
- pakai soft delete pada model yang memiliki `deleted_at`;
- gunakan endpoint command untuk alur seperti approve/reject revision dan generate SPK;
- pisahkan business logic ke service layer agar controller tetap tipis.

## 12. Open Issues to Confirm During Implementation

1. Apakah `stock_transactions.spk_id` benar-benar wajib untuk semua transaksi, termasuk transaksi manual non-SPK.
2. Aturan koreksi transaksi setelah snapshot bulanan terbentuk.
3. Apakah perubahan `dish_compositions` harus membekukan histori jadwal lama.
4. Aturan pembulatan final hasil SPK.
5. Aturan default saat hasil rekomendasi bernilai negatif.
6. SLA waktu respon dashboard dan generate SPK.

## 13. Conclusion

Desain yang tepat untuk sistem ini adalah **workflow-centered modular monolith berbasis CodeIgniter 4 dan MySQL**. Fokus utama desain bukan hanya CRUD, tetapi konsistensi transaksi stok, histori approval/revisi, basis menu terjadwal, input pasien harian, hasil SPK yang persisten, dan audit trail yang kuat.
