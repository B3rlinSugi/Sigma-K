import { KPICandidate, EchelonDistribution, SubmissionTurnaroundMetric, CabinetCompositionStats } from '@/types/analytics';

export const MOCK_KPIS: KPICandidate[] = [
  {
    id: 'kpi-01',
    code: 'KPI-DEL-01',
    name: 'Rasio Perampingan Struktur Jabatan Fungsional',
    category: 'Struktur Kelembagaan',
    value: 68.4,
    unit: '%',
    target: 70.0,
    trend: 'UP',
    trendPercentage: 4.2,
    description: 'Persentase jabatan fungsional berkeahlian dibandingkan total jabatan struktural pasca kebijakan delayering Eselon III & IV.',
    formula: '(Total Jabatan Fungsional / Total Unit & Jabatan) * 100%',
    isProposed: true,
    status: 'PROPOSED KPI',
  },
  {
    id: 'kpi-02',
    code: 'KPI-CAB-01',
    name: 'Indeks Kesiapan Kelembagaan Kabinet Merah Putih',
    category: 'Kabinet & Regulasi',
    value: 87.5,
    unit: '%',
    target: 100.0,
    trend: 'UP',
    trendPercentage: 12.5,
    description: 'Tingkat kelengkapan struktur organisasi, tupoksi, dan regulasi dasar hukum pada 48 Kementerian Kabinet Merah Putih.',
    formula: '(Jumlah K/L dengan Struktur & Tupoksi Lengkap / 48 K/L) * 100%',
    isProposed: true,
    status: 'PROPOSED KPI',
  },
  {
    id: 'kpi-03',
    code: 'KPI-TAT-01',
    name: 'Rata-rata Kecepatan Verifikasi Usulan Perubahan',
    category: 'Efisiensi Layanan',
    value: 1.8,
    unit: 'Hari Kerja',
    target: 3.0,
    trend: 'DOWN', // Turun artinya makin cepat
    trendPercentage: -25.0,
    description: 'Rata-rata durasi hari kerja yang dibutuhkan tim Verifikator KemenPANRB untuk menuntaskan review pengajuan instansi.',
    formula: 'AVG(Tanggal Selesai Verifikasi - Tanggal Pengajuan Masuk)',
    isProposed: true,
    status: 'PROPOSED KPI',
  },
  {
    id: 'kpi-04',
    code: 'KPI-AUD-01',
    name: 'Tingkat Kepatuhan Regulasi Dasar Hukum (PDF)',
    category: 'Kepatuhan & Tata Kelola',
    value: 96.2,
    unit: '%',
    target: 100.0,
    trend: 'STABLE',
    description: 'Persentase pengajuan perubahan yang menyertakan salinan resmi Lembaran Negara / Perpres / Permen yang valid.',
    formula: '(Pengajuan dengan Dokumen PDF Valid / Total Pengajuan) * 100%',
    isProposed: true,
    status: 'PROPOSED KPI',
  },
];

export const MOCK_ECHELON_DISTRIBUTION: EchelonDistribution[] = [
  { echelon: 'Menteri / Kepala Lembaga', count: 48, percentage: 3.5, color: '#0B2A4A' },
  { echelon: 'Eselon I.a (Setjen / Ditjen / Deputi)', count: 215, percentage: 15.6, color: '#1f477e' },
  { echelon: 'Eselon I.b (Staf Ahli)', count: 96, percentage: 7.0, color: '#3974bc' },
  { echelon: 'Eselon II.a (Biro / Direktorat / Asdep)', count: 480, percentage: 34.8, color: '#D4AF37' },
  { echelon: 'Jabatan Fungsional Tertentu (JFT)', count: 540, percentage: 39.1, color: '#10b981' },
];

export const MOCK_CABINET_COMPOSITION: CabinetCompositionStats[] = [
  { category: 'Kementerian Koordinator', count: 7, color: '#0B2A4A' },
  { category: 'Kementerian Teknis Bidang', count: 41, color: '#1f477e' },
  { category: 'Lembaga Pemerintah Non-Kementerian', count: 28, color: '#D4AF37' },
  { category: 'Lembaga Non-Struktural', count: 18, color: '#0284c7' },
];

export const MOCK_SUBMISSION_TURNAROUND: SubmissionTurnaroundMetric[] = [
  { submissionType: 'Struktur Organisasi Baru', averageDays: 2.4, totalCompleted: 42 },
  { submissionType: 'Penyesuaian Tugas & Fungsi', averageDays: 1.6, totalCompleted: 68 },
  { submissionType: 'Pembaruan Profil Instansi', averageDays: 0.8, totalCompleted: 115 },
  { submissionType: 'Pemekaran Lembaga Baru', averageDays: 3.5, totalCompleted: 12 },
];
