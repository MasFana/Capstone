# Use Case Diagram — Sistem Informasi Manajemen Gudang dan SPK Instalasi Gizi RSD Balung

## 1. Overview

Dokumen ini memisahkan artefak use case dari dokumen desain utama dan sudah diselaraskan dengan DB diagram terbaru.

## 2. Actors

### 2.1 admin

Memiliki otoritas tertinggi. Aktor ini mengelola user, memantau audit, dan menyetujui atau menolak revisi transaksi.

### 2.2 dapur

Mengelola dish, komposisi bahan, menu, jadwal menu, input pasien, dan proses generate SPK.

### 2.3 gudang

Mengelola item operasional, transaksi stok masuk/keluar/retur, dan pengajuan revisi.

## 3. Main Use Cases

- Login
- Kelola User
- Kelola Item
- Kelola Dish dan Komposisi
- Kelola Menu dan Jadwal Menu
- Input Pasien Harian
- Buat Transaksi Stok
- Ajukan Revisi Transaksi
- Approve / Reject Revisi
- Generate SPK
- Finalisasi SPK
- Lihat Dashboard
- Lihat Audit Log
- Export Laporan PDF

## 4. Use Case Diagram (PlantUML)

```plantuml
@startuml
left to right direction

actor "admin" as SA
actor "dapur" as SG
actor "gudang" as G

rectangle "Sistem Informasi Manajemen Gudang & SPK Instalasi Gizi" {
  usecase "Login" as UC1
  usecase "Kelola User" as UC2
  usecase "Kelola Item" as UC3
  usecase "Kelola Dish & Komposisi" as UC4
  usecase "Kelola Menu & Jadwal" as UC5
  usecase "Input Pasien Harian" as UC6
  usecase "Buat Transaksi Stok" as UC7
  usecase "Ajukan Revisi Transaksi" as UC8
  usecase "Approve / Reject Revisi" as UC9
  usecase "Generate SPK" as UC10
  usecase "Finalisasi SPK" as UC11
  usecase "Lihat Dashboard" as UC12
  usecase "Lihat Audit Log" as UC13
  usecase "Export PDF Laporan" as UC14
}

SA --> UC1
SA --> UC2
SA --> UC3
SA --> UC9
SA --> UC11
SA --> UC12
SA --> UC13
SA --> UC14

SG --> UC1
SG --> UC4
SG --> UC5
SG --> UC6
SG --> UC10
SG --> UC11
SG --> UC12
SG --> UC14

G --> UC1
G --> UC3
G --> UC7
G --> UC8
G --> UC12
G --> UC14

UC8 .> UC9 : <<extend>>
UC10 .> UC11 : <<include>>
@enduml
```

## 5. Use Case Notes

### 5.1 Inventory Workflow

- Transaksi stok dibentuk dari header `stock_transactions` dan detail `stock_transaction_details`.
- Revisi transaksi tidak menghapus transaksi asal, tetapi merujuk ke `parent_transaction_id`.
- Status approval dikendalikan oleh `approval_statuses`.

### 5.2 Menu and SPK Workflow

- Kebutuhan bahan diturunkan dari `menu_schedules`, `menu_dishes`, dan `dish_compositions`.
- Input pasien harian berasal dari `daily_patients`.
- Hasil SPK disimpan ke `spk_calculations` dan `spk_recommendations`.

### 5.3 Reporting and Audit

- Audit log merekam aktivitas penting seperti login, create, update, delete, dan approval.
- Dashboard dan laporan membaca data final dari transaksi, stok, menu, dan SPK.
