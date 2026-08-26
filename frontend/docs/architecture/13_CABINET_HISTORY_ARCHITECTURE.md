# 13. CABINET & HISTORICAL LINEAGE ARCHITECTURE: SIGMA-K

> **Status:** ARCHITECTURE BLUEPRINT  
> **Versi Dokumen:** 1.0.0  
> **Tanggal:** 2026-08-25  
> **Author:** Senior Database Architect & Solutions Architect  
> **Kebutuhan Terkait:** [REQ-001](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-003](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [REQ-004](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/REQUIREMENT_REGISTER.md), [DEC-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/discovery/DISCOVERY_DECISIONS.md)  

Dokumen ini mendefinisikan arsitektur relasional pengelolaan kabinet kepresidenan (*Cabinet Management*), periodesasi pemerintahan, dan pelacakan silsilah transisi kelembagaan (*Historical Institutional Lineage*) antar-era pemerintahan di Indonesia.

---

## 1. Masalah Fundamental Model Legacy vs Solusi SIGMA-K

```
MODEL LEGACY (Anti-Pattern):
  Tabel data_kl: [id, nama="Kabinet Indonesia Maju", tahun="2019", list_id_kl="1,4,12,30,45,78..."]
  - Masalah: String delimit teks tidak ternormalisasi (melanggar 1NF), tidak bisa di-join foreign key,
             tidak bisa melacak kementerian baru/pemecahan kementerian, rawan korupsi data.

TARGET MODEL SIGMA-K (Relational & Historical Graph):
  [Cabinet] 1 ─── * [CabinetPeriod] 1 ─── * [CabinetMembership] * ─── 1 [Institution]
                                                   │
                                                   ▼
                                       [InstitutionLineage]
                           (Pelacak Pemecahan, Merger, Transformasi Nomenklatur)
```

---

## 2. Struktur Entitas Relasional Ternormalisasi

1. **`Cabinet` (Master Kabinet):**
   Menyimpan identitas entitas kabinet (misal: "Kabinet Indonesia Maju", "Kabinet Merah Putih").
2. **`CabinetPeriod` (Periode Masa Jabatan):**
   Menyimpan rentang waktu formal (`start_date`, `end_date`), nomor Keppres/Perpres penetapan, dan flag `is_active`.
3. **`CabinetMembership` (Keanggotaan Instansi):**
   Tabel asosiasi relasional yang memetakan kementerian/lembaga anggota kabinet pada periode tertentu:
   - Atribut: `cabinet_period_id`, `institution_id`, `category` (`KEMENKO`/`TEKNIS`/`LPNK`), `joined_date`, `ended_date`, `is_active_in_cabinet`.
   - Konstrain: `UNIQUE(cabinet_period_id, institution_id)`.
4. **`InstitutionLineage` (Graf Silsilah Transisi Kelembagaan):**
   Merekam hubungan asal-usul kementerian saat terjadi restrukturisasi kabinet:
   - Atribut: `predecessor_institution_id`, `successor_institution_id`, `cabinet_period_id`, `transition_type`, `effective_date`, `legal_decree_reference`.

---

## 3. Penanganan Skenario Transisi Kelembagaan (Use Case Kabinet Merah Putih)

### Skenario A: Pemecahan Kementerian (*Institution Split*)
- **Kasus Nyata:** Kemendikbudristek pada Kabinet Indonesia Maju dipecah menjadi 3 kementerian pada Kabinet Merah Putih:
  1. Kementerian Pendidikan Dasar dan Menengah
  2. Kementerian Pendidikan Tinggi, Sains, dan Teknologi
  3. Kementerian Kebudayaan
- **Pencatatan Database:**
  - `InstitutionLineage` mencatat 3 baris:
    - `(Predecessor: Kemendikbudristek, Successor: Kemendikdasmen, Type: SPLIT)`
    - `(Predecessor: Kemendikbudristek, Successor: Kemendiktisaintek, Type: SPLIT)`
    - `(Predecessor: Kemendikbudristek, Successor: Kemenbud, Type: SPLIT)`

### Skenario B: Pembentukan Kementerian Baru (*New Creation*)
- **Kasus Nyata:** Pembentukan Kementerian Koordinator Bidang Pangan atau Kementerian HAM.
- **Pencatatan Database:**
  - `InstitutionLineage` mencatat: `(Predecessor: NULL, Successor: Kemenko Pangan, Type: NEW)`

### Skenario C: Perubahan Nomenklatur (*Nomenclature Rename*)
- **Kasus Nyata:** Kementerian Pariwisata dan Ekonomi Kreatif $\rightarrow$ Kementerian Pariwisata.
- **Pencatatan Database:**
  - `InstitutionLineage` mencatat: `(Predecessor: Kemenparekraf, Successor: Kemenpar, Type: RENAME)`

---

## 4. Query Komparasi Antar-Kabinet (Delta Comparison Query)

Untuk menghasilkan matriks perbandingan instansi antara Kabinet A dan Kabinet B pada halaman [PAGE-007](file:///c:/Users/Berlin%20Sugiyanto/SIGMA-K/docs/requirements/PROTOTYPE_HANDOFF.md), database mengeksekusi *Set Difference (FULL OUTER JOIN)*:

```sql
-- Blueprint SQL: Mendeteksi instansi yang baru, tetap, atau keluar antar kabinet
SELECT 
    COALESCE(curr_inst.name, prev_inst.name) AS institution_name,
    CASE 
        WHEN prev_mem.id IS NULL THEN 'INSTITUSI_BARU_DI_KABINET_B'
        WHEN curr_mem.id IS NULL THEN 'TIDAK_TERMASUK_DI_KABINET_B'
        ELSE 'TETAP_EKSIS'
    END AS status_transisi
FROM (
    SELECT institution_id, id FROM cabinet_memberships WHERE cabinet_period_id = :periodIdKabinetB AND is_active_in_cabinet = TRUE
) curr_mem
FULL OUTER JOIN (
    SELECT institution_id, id FROM cabinet_memberships WHERE cabinet_period_id = :periodIdKabinetA AND is_active_in_cabinet = TRUE
) prev_mem ON curr_mem.institution_id = prev_mem.institution_id
LEFT JOIN institutions curr_inst ON curr_mem.institution_id = curr_inst.id
LEFT JOIN institutions prev_inst ON prev_mem.institution_id = prev_inst.id;
```
