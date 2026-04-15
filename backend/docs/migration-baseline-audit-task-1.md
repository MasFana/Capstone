# Migration Baseline Audit and Orphan Detection (Task 1)

Dokumen ini mendefinisikan baseline audit non-destruktif (SELECT-only) untuk integritas `stock_transactions.spk_id` dan deteksi keberadaan tabel `monthly_stock_snapshots` sebelum migrasi lanjutan diterapkan.

## Scope

- Distribusi `NULL` vs non-`NULL` pada `stock_transactions.spk_id`.
- Distribusi orphan saat `spk_id` non-`NULL` tetapi parent tidak ada di `spk_calculations`.
- Pemeriksaan keberadaan tabel `monthly_stock_snapshots`.
- Rekomendasi cleanup strategy per kelas anomali (tanpa mutasi data).

## Audit Artifacts

- SQL audit (SELECT-only): `backend/tests/sql/task-1-migration-baseline-audit.sql`
- Runner script: `backend/tests/scripts/run-task-1-migration-audit.sh`
- Evidence success output: `.sisyphus/evidence/task-1-migration-audit.txt`
- Evidence error output: `.sisyphus/evidence/task-1-migration-audit-error.txt`

## Deterministic Commands

### 1) Jalankan audit baseline

```bash
bash backend/tests/scripts/run-task-1-migration-audit.sh
```

### 2) Jalankan audit dengan parameter DB eksplisit

```bash
DB_HOST=127.0.0.1 DB_PORT=3306 DB_NAME=db DB_USER=root DB_PASS=root \
bash backend/tests/scripts/run-task-1-migration-audit.sh
```

Semua query bersifat SELECT-only; script tidak mengeksekusi `INSERT/UPDATE/DELETE/ALTER/DROP`.

## Anomaly Classes and Cleanup Strategy (No Mutation in Task 1)

### Class A — `spk_id_null`

- **Definisi**: Baris `stock_transactions` dengan `spk_id IS NULL`.
- **Interpretasi default**: Valid untuk transaksi manual non-SPK (sesuai keputusan sementara di plan).
- **Strategi cleanup (di task lanjutan, bukan Task 1)**:
  1. Klasifikasikan transaksi manual vs transaksi yang seharusnya berelasi ke SPK.
  2. Untuk transaksi yang seharusnya SPK-linked, siapkan aturan backfill deterministik berdasarkan referensi domain yang valid.
  3. Pertahankan NULL untuk transaksi manual yang sah.

### Class B — `spk_id_orphan_missing_parent`

- **Definisi**: `stock_transactions.spk_id IS NOT NULL` tetapi tidak ada `spk_calculations.id` yang cocok.
- **Risiko**: Akan memblokir enforcement FK bila tidak dibersihkan.
- **Strategi cleanup (di task lanjutan, bukan Task 1)**:
  1. Bekukan daftar orphan per `spk_id` (sudah disediakan query distribusi).
  2. Untuk tiap orphan, tentukan salah satu aksi deterministik berbasis bukti domain:
     - backfill parent `spk_calculations` yang hilang bila data sumber valid tersedia, atau
     - set `spk_id` ke `NULL` bila transaksi terbukti manual/non-SPK.
  3. Jalankan verifikasi ulang query orphan hingga kelas orphan = 0 sebelum FK migration task.

### Class C — `monthly_stock_snapshots` table status = `missing`

- **Definisi**: Tabel target SRS belum ada di schema aktif.
- **Risiko**: Fitur kontrol snapshot periode belum bisa diberlakukan.
- **Strategi cleanup (Task 2)**:
  1. Tambahkan migration pembuatan tabel.
  2. Enforce unique `(period_month, item_id)` dan FK ke `items.id`.
  3. Verifikasi ulang status tabel melalui query `information_schema`.

## Expected Evidence Shape

Output `task-1-migration-audit.txt` minimal memuat:

- nama database aktif,
- status tabel `stock_transactions`, `spk_calculations`, `monthly_stock_snapshots`,
- count per kelas anomali null/non-null,
- count per kelas orphan vs parent-exists,
- distribusi orphan per `spk_id`.

Jika runtime DB/client tidak tersedia, detail kegagalan harus tercatat pada `task-1-migration-audit-error.txt` tanpa perubahan data.
