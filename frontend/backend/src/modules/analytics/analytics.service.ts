import { Injectable } from '@nestjs/common';

@Injectable()
export class AnalyticsService {
  async getKPIs() {
    return [
      {
        id: 'kpi-01',
        code: 'KPI-DELAYERING-RATIO',
        name: 'Rasio Delayering Jabatan Fungsional',
        category: 'ORGANIZATION',
        value: '68.4',
        unit: '%',
        target: '70.0',
        trend: 'UP',
        trendPercentage: 3.2,
        description: 'Persentase unit kerja yang telah beralih ke struktur berbasis keahlian/kelompok kerja fungsional.',
        formula: '(Total Unit Jabatan Fungsional / Total Unit Kerja) * 100',
        isProposed: true,
        status: 'PROPOSED KPI',
      },
      {
        id: 'kpi-02',
        code: 'KPI-READINESS-INDEX',
        name: 'Indeks Kesiapan Kelembagaan 48 K/L',
        category: 'CABINET',
        value: '87.5',
        unit: '%',
        target: '100.0',
        trend: 'UP',
        trendPercentage: 5.8,
        description: 'Tingkat kelengkapan dokumen dasar hukum SOTK kementerian pada Kabinet Merah Putih.',
        formula: '(K/L dengan SOTK Terbit / 48 K/L) * 100',
        isProposed: true,
        status: 'PROPOSED KPI',
      },
      {
        id: 'kpi-03',
        code: 'KPI-VERIF-SLA',
        name: 'Rata-rata Durasi Verifikasi Usulan',
        category: 'PROCESS',
        value: '1.8',
        unit: 'Hari',
        target: '2.0',
        trend: 'DOWN',
        trendPercentage: -15.0,
        description: 'Kecepatan penyelesaian telaah kesesuaian regulasi oleh analis kelembagaan KemenPANRB.',
        formula: 'Total Waktu Telaah / Total Berkas Usulan',
        isProposed: true,
        status: 'PROPOSED KPI',
      },
      {
        id: 'kpi-04',
        code: 'KPI-SUBMISSION-RESOLVE',
        name: 'Rasio Penyelesaian Tiket Usulan',
        category: 'PROCESS',
        value: '94.2',
        unit: '%',
        target: '95.0',
        trend: 'STABLE',
        trendPercentage: 0.5,
        description: 'Persentase usulan perubahan yang telah tuntas hingga tahap pengesahan.',
        formula: '(Usulan Disahkan / Total Usulan Masuk) * 100',
        isProposed: true,
        status: 'PROPOSED KPI',
      },
    ];
  }

  async getEchelonDistribution() {
    return [
      { echelon: 'Pimpinan Tinggi Madya (Eselon I)', count: 214, percentage: 8.5, color: '#0B2A4A' },
      { echelon: 'Pimpinan Tinggi Pratama (Eselon II)', count: 680, percentage: 27.2, color: '#1E40AF' },
      { echelon: 'Jabatan Administrator & Pengawas', count: 420, percentage: 16.8, color: '#64748B' },
      { echelon: 'Jabatan Fungsional Keahlian', count: 1186, percentage: 47.5, color: '#D4AF37' },
    ];
  }

  async getCabinetComposition(cabinetId?: string) {
    return [
      { category: 'Kementerian Koordinator (Kemenko)', count: 7, color: '#0B2A4A' },
      { category: 'Kementerian Teknis / Portofolio', count: 41, color: '#1E3A8A' },
      { category: 'Lembaga Pemerintah Non-Kementerian (LPNK)', count: 28, color: '#D4AF37' },
      { category: 'Lembaga Non-Struktural (LNS)', count: 64, color: '#64748B' },
    ];
  }

  async getSubmissionTurnaround() {
    return [
      { submissionType: 'Struktur Organisasi (SOTK)', averageDays: 2.1, totalCompleted: 42 },
      { submissionType: 'Tugas & Fungsi (Tupoksi)', averageDays: 1.4, totalCompleted: 38 },
      { submissionType: 'Profil & Domisili Instansi', averageDays: 0.8, totalCompleted: 15 },
    ];
  }
}
