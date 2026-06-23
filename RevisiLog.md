# Revisi Log — Audit Log System

## Tanggal: 23 Juni 2026

---

## 1. Latar Belakang

Berdasarkan template dokumen **LAPORAN LOG AKTIVITAS SISTEM INSTALASI GIZI RSD BALUNG**, ditemukan beberapa kesenjangan antara implementasi audit log yang ada dengan persyaratan minimum dokumen.

---

## 2. Gap Analysis

### 2.1 Kolom Response

| Kolom Template | Status Sebelum | Status Sesudah | Perubahan |
|---|---|---|---|
| Tanggal & Waktu | ✅ Ada | ✅ Ada | — |
| Nama Pengguna | ✅ Ada | ✅ Ada | — |
| **Role** | ❌ Tidak ada | ✅ Ada | Field baru `actorInfo.role` dari JOIN `roles` |
| Modul | ✅ Ada | ✅ Ada | — |
| Aktivitas | ✅ Ada | ✅ Ada | — |
| Detail Aktivitas | ✅ Ada | ✅ Ada | — |

### 2.2 Audit Login/Logout

| Aktivitas | Status Sebelum | Status Sesudah | Perubahan |
|---|---|---|---|
| Login Sistem | ❌ Tidak diaudit | ✅ Audit via `AuthService::attemptLogin()` | AuditActionType::Login |
| Logout Sistem | ❌ Tidak diaudit | ✅ Audit via `AuthService::logout()` | AuditActionType::Logout |

### 2.3 Export Laporan

| Aktivitas | Status Sebelum | Status Sesudah | Perubahan |
|---|---|---|---|
| Export Laporan | ❌ Tidak diaudit | ✅ Audit via `ReportingService` | AuditActionType::Create, table_name='reports' |

### 2.4 Filter

| Filter | Status Sebelum | Status Sesudah | Perubahan |
|---|---|---|---|
| Periode (date range) | ❌ Tidak ada | ✅ `start_date` / `end_date` | Query params baru |
| Nama Pengguna | ⚠️ Via `q` search | ✅ `user_id` filter | Query param baru |
| Role | ❌ Tidak ada | ✅ Via `user_id` (indirect) | — |
| Modul | ✅ `table_name` | ✅ | — |
| Jenis Aktivitas | ✅ `action_type` | ✅ | — |

### 2.5 Ringkasan / Summary

| Ringkasan | Status Sebelum | Status Sesudah | Perubahan |
|---|---|---|---|
| Total Aktivitas Tercatat | ❌ Tidak ada | ✅ `summary().total` | Endpoint baru |
| Aktivitas Super Admin | ❌ Tidak ada | ✅ `summary().byRole.admin` | Endpoint baru |
| Aktivitas Petugas Gudang | ❌ Tidak ada | ✅ `summary().byRole.gudang` | Endpoint baru |
| Aktivitas Petugas Gizi | ❌ Tidak ada | ✅ `summary().byRole.dapur` | Endpoint baru |
| Aktivitas Generate SPK | ❌ Tidak ada | ✅ `summary().byModule.SPK` | Endpoint baru |
| Aktivitas Transaksi Barang | ❌ Tidak ada | ✅ `summary().byModule.Transaksi` | Endpoint baru |
| Aktivitas Perubahan Data | ❌ Tidak ada | ✅ Frontend compute dari `byActionType` | Endpoint baru |

### 2.6 Bug Fix: user_id = NULL pada Menu Core

Terdapat 4 service yang sebelumnya tidak mengirim `$actorId` dan `$ipAddress` ke `AuditService::log()`:

| Service | Status Sebelum | Status Sesudah |
|---|---|---|
| DishManagementService | ❌ user_id = null | ✅ Sudah fix |
| DishCompositionManagementService | ❌ user_id = null | ✅ Sudah fix |
| MenuPackageManagementService | ❌ user_id = null | ✅ Sudah fix |
| MenuScheduleManagementService | ❌ user_id = null | ✅ Sudah fix |

### 2.7 Audit SPK

| Service | Status Sebelum | Status Sesudah |
|---|---|---|
| SpkPersistenceService | ✅ Sudah ada audit | ✅ |
| SpkStockPostingService | ✅ Sudah ada audit | ✅ |
| SpkBasahGenerationService | ❌ Tidak ada audit langsung | ✅ Covered via SpkPersistenceService::createVersionedSpk() |
| SpkKeringPengemasGenerationService | ❌ Tidak ada audit langsung | ✅ Covered via SpkPersistenceService::createVersionedSpk() |

---

## 3. Perubahan File

### 3.1 Backend — PHP

| File | Perubahan |
|---|---|
| `backend/app/Enums/AuditActionType.php` | Added `case Login = 'login'` dan `case Logout = 'logout'` |
| `backend/app/Services/AuthService.php` | `attemptLogin()` — tambah `$ipAddress` param, audit Login. `logout()` — tambah `$ipAddress` param, audit Logout |
| `backend/app/Controllers/Api/V1/Auth.php` | Extract `$ipAddress` dari request, forward ke service |
| `backend/app/Services/ReportingService.php` | Tambah AuditService dependency, audit 5 method export |
| `backend/app/Controllers/Api/V1/AuditLogs.php` | Tambah JOIN `roles`, field `role`, filter `start_date`/`end_date`/`user_id`, method `summary()` |
| `backend/app/Config/Routes.php` | Tambah route `audit-logs/summary` + OPTIONS preflight |

### 3.2 Backend — OpenAPI

| File | Perubahan |
|---|---|
| `backend/app/OpenApi/AuditLogSchemas.php` | Tambah field `role` di actorInfo, schema `AuditLogSummaryResponse` |

### 3.3 Frontend — SDK

| File | Perubahan |
|---|---|
| `frontend/src/sdk/types/auditLogs.ts` | Tambah `role` di actorInfo, `start_date`/`end_date`/`user_id` di query, interface `AuditLogSummary` + `AuditLogSummaryResponse` |
| `frontend/src/sdk/resources/auditLogs.ts` | Tambah method `summary()`, query params baru di `buildAuditLogQuery` |

### 3.4 Tests

| File | Perubahan |
|---|---|
| `backend/tests/feature/Api/V1/AuditLogsTest.php` | Tambah 6 test methods (role field, date range, user_id, summary, login audit, pagination+filters) |

---

## 4. API Specification

### 4.1 GET /api/v1/audit-logs — Daftar Audit Log

**Query params baru:**
```
start_date  : string (Y-m-d) — filter earliest date
end_date    : string (Y-m-d) — filter latest date
user_id     : int — filter by user
```

**Response field baru:**
```json
{
  "data": [
    {
      "actorInfo": {
        "role": "admin"  // string | null
      }
    }
  ]
}
```

### 4.2 GET /api/v1/audit-logs/summary — Ringkasan

**Response:**
```json
{
  "data": {
    "total": 150,
    "byRole": {
      "admin": 45,
      "dapur": 60,
      "gudang": 45
    },
    "byActionType": {
      "create": 30,
      "update": 50,
      "delete": 10,
      "approval": 20,
      "rejection": 5,
      "login": 20,
      "logout": 15
    },
    "byModule": {
      "Transaksi": 40,
      "Menu": 30,
      "Stok": 35,
      "SPK": 25,
      "Pengguna": 10
    }
  }
}
```

---

## 5. Sub-Agent Execution

Pekerjaan dipecah menjadi 8 sub-agent paralel:

| Wave | Agent | Tugas | Durasi |
|---|---|---|---|
| 1 | AuditActionTypeEnum | Tambah enum Login/Logout | 17s |
| 1 | AuthServiceAudit | Audit login/logout di AuthService | 30s |
| 1 | ReportingServiceAudit | Audit export laporan | — |
| 1 | AuditLogsController | Role, filter, summary endpoint | — |
| 1 | RoutesUpdate | Route summary + OPTIONS | — |
| 1 | AuditLogSchemas | OpenAPI schema baru | — |
| 1 | SDKTypes | TypeScript types | — |
| 1 | SDKResource | SDK resource method | — |
| 2 | AuditLogTests | 6 test methods baru | — |

---

## 6. Verification

**AuditLogsTest:** 8/8 passed, 97 assertions, 7.1s

| Test | Assertions | Status |
|---|---|---|
| testAuditLogsEndpointIsAdminOnly | 2 | ✅ |
| testAuditLogsEndpointReturnsCollectionForAdmin | 12 | ✅ |
| testAuditLogResponseIncludesRole | 6 | ✅ |
| testAuditLogFiltersByDateRange | 12 | ✅ |
| testAuditLogFiltersByUserId | 8 | ✅ |
| testAuditLogSummaryEndpoint | 12 | ✅ |
| testLoginCreatesAuditLog | 10 | ✅ |
| testAuditLogEndpointPaginationWithFilters | 35 | ✅ |

**OpenAPI cache:** Regenerated successfully.

---
