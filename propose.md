# Analisis Perubahan Kalender Menu & Generate SPK Basah
# Swarm Report — Verifier · Critic · DeepAnalyzer

## Ringkasan

Dua perubahan terpisah:
1. **Menu rotation** (Revisi 3) — siklus paket dari 11-hari ke 10-hari
2. **SPK basah generate schedule** (Revisi 4) — target tanggal dari `[N, N+1]` ke `[N+1, N+2]` + aturan batas bulan

---
# HASIL SWARM: 2 CRITICAL, 4 HIGH, 7 MEDIUM, 3 INFO
---

## Revisi 3 — Kalender Menu

### Lokus perubahan
`backend/app/Services/MenuCalendarContract.php:21`

### Logika saat ini
```php
$res = (($day - 1) % 11) + 1;     // line 21
// day === 31 → return 11          // lines 13-15
// 02-29 → return 9                // lines 17-19
```

### Logika baru
```php
$res = (($day - 1) % 10) + 1;     // ganti 11 jadi 10
```

### Perubahan minimal — satu baris

### Verifikasi oleh Verifier ✅
- Line 21 benar `(($day - 1) % 11) + 1`
- Special cases 31→11 dan 02-29→9 sesuai
- Tidak ada caller lain `resolvePackageId` selain `resolveEffectiveAssignments` + test

### FLAW F3.1 [MEDIUM] — MenuDishSeeder hardcode 11
Proposal sebelumnya klaim "tidak perlu" untuk MenuDishSeeder. **Salah.**
- `backend/app/Database/Seeds/MenuDishSeeder.php:24`: `$menuCount = 11`
- `line 39`: `$dishIndex = $slotIndex * 11 + ($menuId - 1)`

Formula dish layout ini **hardcode 11**. Di mod-10 rotation Paket 11 masih ada (hari 31), jadi tidak rusak sekarang. Tapi pernyataan "tidak perlu" menyesatkan.

### FLAW F3.2 [HIGH] — Explicit menu_schedules tidak otomatis migrasi
`resolveEffectiveAssignments()` (MenuScheduleManagementService:418-424) cek tabel `menu_schedules` **dulu** sebelum fallback ke contract. Jika ada row eksplisit (misal admin assign day 11 → Paket 11), row itu TIDAK berubah oleh mod-10.

**Akibat**: Hari dengan schedule eksplisit retain paket lama, inkonsisten dengan fallback.

**Butuh**: Migrasi data — update/delete row `menu_schedules` yang conflict dengan rotation baru, ATAU dokumentasi bahwa admin harus mereview dan menghapus manual.

### FLAW F3.3 [LOW] — Test assertion tidak cukup
Proposal hanya sebut update `2026-03-11` dan `2026-03-21`. **Test juga perlu assert values YANG BERUBAH**:
- Day 20: was `9` → now `10` (DeepAnalyzer A1)
- Day 30: was `8` → now `10` (DeepAnalyzer A1)

Tanpa ini, test tetap pass meski mod-10 belum diterapkan.

### FLAW F3.4 [INFO] — Paket 10 vs Paket 11 asimetri
- Paket 1-10: masing-masing 3×/bulan (hari 1-10, 11-20, 21-30)
- Paket 11: hanya 1×/bulan (hari 31 spesial)

Ini bawaan spesifikasi, bukan bug. Catat saja.

### Efek domino — Revisi 3

| File | Perubahan |
|------|-----------|
| `MenuCalendarContract.php:21` | `%11` → `%10` |
| `tests/unit/MenuFoundationsTest.php` | Update L44 (11→1), L45 (10→1), L48 (9→10), L49 (8→10) |
| `MenuScheduleManagementService.php:408-427` | Tidak perlu — delegasi ke contract |
| `MenuDishSeeder.php:24,39` | **Review** — hardcode 11 di dish formula, tapi tidak rusak |
| `MenuScheduleModel` → explicit rows | **Butuh migrasi data** — hapus/update row yang override hari dengan rotation baru |
| `MenuPackageCatalog.php` | Tidak perlu — 11 paket tetap valid |

---
## Revisi 4 — Generate SPK Basah

### Lokus perubahan
`backend/app/Services/SpkBasahGenerationService.php:157-167` — `resolveBasahTargetDates()`

### Logika saat ini ✅ (Verified)
```php
$dates = [$requestedDate->format('Y-m-d')];        // service_date
$next  = $requestedDate->modify('+1 day');
if ($requestedDate->format('Y-m') === $next->format('Y-m')) {
    $dates[] = $next->format('Y-m-d');
}
```

### ❌ KRITIK SWARM: Proposal sebelumnya memiliki 2 CRITICAL, 4 HIGH, 5 MEDIUM issues

---

## FLAW F4.1 [CRITICAL] — Semua tanggal ganjil → empty array → crash
Pseudocode tidak handle **satupun** tanggal ganjil (1,3,5,...,27,29). Semua fall through.

Akibat: `$targetDates` = `[]`. `generate()` akses `$targetDates[0]` → **undefined index error**.

| Kasus bulan | % tanggal error |
|-------------|----------------|
| 31 hari | 15/31 = **48.4%** |
| 30 hari | 16/30 = **53.3%** |
| Feb non-kabisat | 15/28 = **53.6%** |
| Feb kabisat | 14/29 = **48.3%** |

**Butuh**: Semua tanggal ganjil harus direject dengan validasi eksplisit (400 error) ATAU ada handler.

---

## FLAW F4.2 [CRITICAL] — Day 30 di bulan 30 hari (Apr, Jun, Sep, Nov) → crash
Pseudocode: `day == 30 && daysInMonth == 31` → false (30≠31). `day <= daysInMonth - 2` → 30 <= 28 → false. Tidak ada kondisi lain. Fall through → crash.

**Butuh**: Handler `day == 30 && daysInMonth == 30` → `[next-month-01, next-month-02]`.

---

## FLAW F4.3 [CRITICAL] — Day 28 Feb non-kabisat → crash
Proposal punya catatan open question "non-leap February" rekomendasi opsi (a), tapi pseudocode **tidak implement** handler. Day 28 Feb non-leap: `28 <= 26` → false, `28 == 30` → false, `28 == 28 && month == 2 && isLeapYear(year)` → false.

Fall through → crash. **Ini produksi case nyata setiap tahun.**

---

## FLAW F4.4 [HIGH] — Urutan kondisi penting
Jika implement opsi (a) untuk Feb non-leap, urutan kondisi:
```php
if (day == 28 && month == 2 && isLeapYear(year))  // leap: return [29]
else if (day == 28 && month == 2)                  // non-leap: return [next-01, next-02]
```
Atau restruktur. Pseudocode tidak bahas urutan.

---

## FLAW F4.5 [HIGH] — Return type: integer vs Y-m-d string
Branch normal: `return [day+1, day+2]` → `[3, 4]` (integer array).
Method return type `@return array<int, string>`, caller expects `'2026-03-03'` format.

Special cases pakai placeholder `[year-month-31]`, `[next-month-01]` — tidak tunjukkin format konkret. Tahun rollover (Dec→Jan) juga tidak dibahas.

---

## FLAW F4.6 [HIGH] — Tahun rollover tidak dibahas
`next-month-01` di Dec → perlu `(year+1)-01-01`. Pseudocode tidak sebut mekanisme.

---

## FLAW F4.7 [MEDIUM] — Day 31 tidak bisa generate untuk dirinya sendiri
Skema baru: day 30 generate untuk day 31. Day 31 generate untuk next-month 1,2.
**Tidak ada cara generate SPK untuk day 31 dari day 31 sendiri.**
Regresi operasional jika day 30 gagal generate (no daily patient, system crash).

---

## FLAW F4.8 [MEDIUM] — Validasi parity vague
"service_date harus genap" tidak cukup presisi:
- Day 29 Feb (ganjil) → **valid** (special case)
- Day 31 (ganjil) → **valid** (special case)
- Day 1 (ganjil) → **invalid**
- Day 15 (ganjil) → **invalid**

Validasi harus: "reject odd days, except day 29 in Feb (leap) and day 31 in 31-day months."
HTTP status: 400 dengan error message seperti validasi existing.

---

## FLAW F4.9 [MEDIUM] — scope_key berubah → orphan records
Scope_key di SpkPersistenceService dibangun dari `target_date_start|target_date_end`.
Contoh: `service_date=2026-03-02`:
- Current: scope `2026-03-02|2026-03-03`
- New: scope `2026-03-03|2026-03-04`

**Regenerate dengan service_date sama menghasilkan scope_key BERBEDA** → membuat record baru, bukan mengganti yang lama. Old SPK jadi orphan.

---

## FLAW F4.10 [INFO] — Day 31 guard aman
`day == 31` check tidak pernah match di bulan <31 hari. No-op guard, aman.

---

## FLAW F4.11 [INFO] — API behavioral change besar
~50% tanggal yang sebelumnya valid (odd) sekarang error. Semua test yang pake `service_date=odd` harus diubah:
- `SpkBasahTest::testGenerateIncludesRequestedDateAndNextDateWhenStillSameMonth` — pakai `2026-03-01` (ODD → reject)
- `SpkBasahTest::testGenerateAllowsGudangRole` — pakai `2026-03-01`
- `SpkBasahTest::testGenerateReturnsConflictForDuplicateScopeUnlessRegenerateIsTrue` — pakai `2026-03-01`
- `SpkBasahTest::testDeactivatedDishNoLongerContributesToSpkBasahGeneration` — pakai `2026-03-01`
- `SpkBasahTest::testGenerateOnMonthEndIncludesOnlyRequestedDate` — pakai `2026-03-31` (valid tapi target dates berubah)
- `AdditiveMultiMenuTest` — pakai odd `service_date=15`
- `SpkMultiMenuBugTest` — hardcode `[$serviceDate, $serviceDate+1]`
- `SpkRoundingFixTest` — dynamic date, tergantung hari ini parity

**~15 test methods affected** (DeepAnalyzer A2).

---

## Pseudocode final — Revisi 4 (setelah koreksi swarm)

```php
function resolveBasahTargetDates(DateTimeImmutable $requestedDate): array
{
    $day = (int) $requestedDate->format('j');
    $month = (int) $requestedDate->format('n');
    $year = (int) $requestedDate->format('Y');
    $daysInMonth = (int) $requestedDate->format('t');
    $ymd = fn(int $y, int $m, int $d) => sprintf('%04d-%02d-%02d', $y, $m, $d);

    // === SPECIAL: Last day of month with 31 days → only target 31
    if ($day === 30 && $daysInMonth === 31) {
        return [$ymd($year, $month, 31)];
    }

    // === SPECIAL: Day 31 → target next month 1,2
    if ($day === 31) {
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear  = $month === 12 ? $year + 1 : $year;
        return [$ymd($nextYear, $nextMonth, 1), $ymd($nextYear, $nextMonth, 2)];
    }

    // === SPECIAL: Feb 28 leap → only target 29
    if ($day === 28 && $month === 2 && checkdate(2, 29, $year)) {
        return [$ymd($year, 2, 29)];
    }

    // === SPECIAL: Feb 29 → target March 1,2
    if ($day === 29 && $month === 2) {
        return [$ymd($year, 3, 1), $ymd($year, 3, 2)];
    }

    // === SPECIAL: End of 30-day month (day 30) → target next month 1,2
    if ($day === 30 && $daysInMonth === 30) {
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear  = $month === 12 ? $year + 1 : $year;
        return [$ymd($nextYear, $nextMonth, 1), $ymd($nextYear, $nextMonth, 2)];
    }

    // === SPECIAL: Last even day of Feb non-leap (day 28) → target next month 1,2
    if ($day === 28 && $month === 2 && !checkdate(2, 29, $year)) {
        return [$ymd($year, 3, 1), $ymd($year, 3, 2)];
    }

    // === NORMAL: Even day with room for N+1, N+2 within same month
    if ($day % 2 === 0 && $day <= $daysInMonth - 2) {
        return [$ymd($year, $month, $day + 1), $ymd($year, $month, $day + 2)];
    }

    // === REJECT: All other dates (odd days, unhandled specials)
    throw new \InvalidArgumentException(sprintf(
        'service_date day %d is not a valid generation date for month with %d days.',
        $day,
        $daysInMonth
    ));
}
```

### Test validasi pseudocode — semua month type ✅

**31-day month (e.g., March):**
| service_date | Target SPK | Rule |
|-------------|------------|------|
| 1 (odd) | REJECT | fall-through |
| 2 (even) | 3, 4 | normal |
| ... | ... | ... |
| 28 (even) | 29, 30 | normal |
| 29 (odd) | REJECT | fall-through |
| 30 (special) | 31 | `day==30 && 31day` |
| 31 (special) | next-01, next-02 | `day==31` |

**30-day month (e.g., April):**
| service_date | Target SPK | Rule |
|-------------|------------|------|
| 2 | 3, 4 | normal |
| ... | ... | ... |
| 28 | 29, 30 | normal |
| 29 (odd) | REJECT | fall-through |
| 30 (special) | next-01, next-02 | `day==30 && 30day` |

**Feb non-leap (28 days):**
| service_date | Target SPK | Rule |
|-------------|------------|------|
| 2 | 3, 4 | normal |
| ... | ... | ... |
| 26 | 27, 28 | normal |
| 27 (odd) | REJECT | |
| 28 (special) | Mar-01, Mar-02 | `day==28 && non-leap` |

**Feb leap (29 days):**
| service_date | Target SPK | Rule |
|-------------|------------|------|
| 2 | 3, 4 | normal |
| ... | ... | ... |
| 26 | 27, 28 | normal |
| 27 (odd) | REJECT | |
| 28 (special) | 29 | `day==28 && leap` |
| 29 (special) | Mar-01, Mar-02 | `day==29` |

---

### Efek domino — Revisi 4

| File | Perubahan |
|------|-----------|
| `SpkBasahGenerationService.php:157-167` | `resolveBasahTargetDates()` ganti total (pseudocode final di atas) |
| `SpkBasahGenerationService.php:109-152` | Tambah validasi parity: reject odd kecuali 29 Feb atau 31 |
| `SpkBasahGenerationService.php:53` | Handle error dari `resolveBasahTargetDates` (saat ini langsung pakai return, perlu catch exception/error) |
| `tests/feature/Api/V1/SpkBasahTest.php` | Update ~15 methods — ganti service_date ke even date, update target date assertions |
| `tests/feature/Api/V1/AdditiveMultiMenuTest.php` | Ganti service_date dari odd (15) ke even |
| `tests/unit/SpkMultiMenuBugTest.php` | Update seed helper `[$serviceDate+1, $serviceDate+2]` |
| `tests/feature/Api/V1/SpkBasahReadTest.php` | Review — mungkin pakai odd dates di test helper |
| `tests/unit/SpkRoundingFixTest.php` | Review — dynamic date |

### TIDAK berubah
- **SPK kering/pengemas** — pake target_month, tidak ada logika harian ✅ (Verified)
- **MenuScheduleManagementService** — consumer contract, tidak berubah ✅ (Verified)
- **Database migrations** — schema support ✅ (DeepAnalyzer verified)
- **Routes** — tidak ada endpoint baru ✅ (Verified)
- **MenuPackageCatalog** — 11 paket tetap ✅
- **SpkPersistenceService** — scope_key logic aman ✅ (tapi NILAI scope_key berubah — lihat F4.9)
- **buildPerDateRequirements** — generic loop, tidak dependen jadwal ✅
- **buildRecommendations** — generic loop ✅
- **SpkContractTest, SpkRouteBoundaryTest** — route-boundary tests ✅

### Operasional notes
1. **Migrasi explicit menu_schedules**: Admin harus hapus manual row yang override hari dengan rotation 10-hari, ATAU buat migration script.
2. **Scope_key orphan**: Implementasi regenerate harus handle scope_key yang berubah.
3. **Day 31 coverage**: Pastikan day 30 generate sukses — tidak ada fallback untuk day 31.
4. **API docs update**: Generate endpoint behavioral change signifikan — ~50% dates jadi invalid.
