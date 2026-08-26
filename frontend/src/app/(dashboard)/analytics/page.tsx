'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { 
  BarChart3, 
  TrendingUp, 
  TrendingDown, 
  Sparkles, 
  Download, 
  Info, 
  FileCheck2, 
  Users, 
  Building2, 
  Award,
  Clock,
  AlertCircle,
  CheckCircle2
} from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Spinner } from '@/components/ui/Breadcrumb';
import { AnalyticsService } from '@/services/api/analytics.service';
import { KPICandidate, EchelonDistribution, SubmissionTurnaroundMetric, CabinetCompositionStats } from '@/types/analytics';
import { useAuth } from '@/context/RoleContext';
import { AppError } from '@/services/http/errors';

export default function AnalyticsPage() {
  const { isApiMode } = useAuth();
  const [kpis, setKpis] = useState<KPICandidate[]>([]);
  const [echelonDist, setEchelonDist] = useState<EchelonDistribution[]>([]);
  const [turnaround, setTurnaround] = useState<SubmissionTurnaroundMetric[]>([]);
  const [composition, setComposition] = useState<CabinetCompositionStats[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isExporting, setIsExporting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [exportSuccessMessage, setExportSuccessMessage] = useState<string | null>(null);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      setErrorMessage(null);
      try {
        const [kpiList, echList, turnList, compList] = await Promise.all([
          AnalyticsService.getKPIs(),
          AnalyticsService.getEchelonDistribution(),
          AnalyticsService.getSubmissionTurnaround(),
          AnalyticsService.getCabinetComposition(),
        ]);
        setKpis(kpiList);
        setEchelonDist(echList);
        setTurnaround(turnList);
        setComposition(compList);
      } catch (err) {
        if (err instanceof AppError) {
          setErrorMessage(err.message || 'Gagal memuat analitik data eksekutif.');
        } else {
          setErrorMessage('Gagal terhubung ke layanan analitik.');
        }
      } finally {
        setIsLoading(false);
      }
    }
    load();
  }, []);

  const handleExport = async (type: string = 'submissions') => {
    setIsExporting(true);
    setErrorMessage(null);
    setExportSuccessMessage(null);
    try {
      const result = await AnalyticsService.exportReport(type, 'csv');
      if (result instanceof Blob) {
        const url = window.URL.createObjectURL(result);
        const a = document.createElement('a');
        a.href = url;
        a.download = `report_${type}_${Date.now()}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        setExportSuccessMessage(`Laporan ${type} (.csv) berhasil diunduh.`);
      }
    } catch (err) {
      if (err instanceof AppError) {
        if (err.isForbidden()) {
          setErrorMessage('Akses ditolak: Anda tidak memiliki wewenang untuk mengekspor dataset ini.');
        } else {
          setErrorMessage(err.message || 'Gagal mengekspor laporan dataset.');
        }
      } else {
        setErrorMessage('Gagal menghubungi server untuk mengunduh laporan.');
      }
    } finally {
      setIsExporting(false);
    }
  };

  if (isLoading) return <Spinner />;

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title="Intelijensi Data & Analitik Kelembagaan"
        subtitle="Ruang kerja pemodelan analitik data, visualisasi postur aparatur ASN, dan pemantauan indikator kinerja utama (KPI) eksekutif."
        breadcrumbs={[
          { label: 'Intelijensi & Audit' },
          { label: 'Analitik & Postur ASN' },
        ]}
        actions={
          <div className="flex items-center gap-2.5">
            <Button
              variant="outline"
              size="sm"
              leftIcon={<Download className="w-3.5 h-3.5" />}
              onClick={() => handleExport('submissions')}
              disabled={isExporting}
            >
              {isExporting ? 'Mengekspor...' : 'Unduh Laporan Usulan (.CSV)'}
            </Button>
            <Button
              variant="outline"
              size="sm"
              leftIcon={<Download className="w-3.5 h-3.5" />}
              onClick={() => handleExport('institutions')}
              disabled={isExporting}
            >
              Ekspor Data Instansi (.CSV)
            </Button>
          </div>
        }
      />

      {/* ERROR BANNER */}
      {errorMessage && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3 text-xs text-red-900 shadow-xs">
          <AlertCircle className="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
          <div>
            <span className="font-bold block text-sm">Terjadi Kesalahan Akses Data</span>
            <p className="mt-0.5">{errorMessage}</p>
          </div>
        </div>
      )}

      {/* SUCCESS EXPORT BANNER */}
      {exportSuccessMessage && (
        <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-2 text-xs text-emerald-900">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />
          <span>{exportSuccessMessage}</span>
        </div>
      )}

      {/* DATA ANALYST COLLABORATION BANNER */}
      <Card className="bg-gradient-to-r from-primary-950 to-slate-900 text-white border-primary-800 shadow-md">
        <CardContent className="p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gold-500/20 text-gold-300 flex items-center justify-center border border-gold-400/30 shrink-0">
              <Sparkles className="w-5 h-5" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h4 className="text-sm font-bold text-white">Monitoring Eksekutif & Indikator Kinerja Utama</h4>
                <Badge variant="gold" size="sm">{isApiMode ? 'API Live Verified' : 'Prototype Simulation'}</Badge>
              </div>
              <p className="text-xs text-slate-300 mt-0.5">
                Metrik di bawah ini dihitung secara dinamis dari database master CodeIgniter 4 (`eskld_db`) untuk monitoring postur kelembagaan KemenPANRB.
              </p>
            </div>
          </div>
          <Link href="/structure">
            <Button variant="gold" size="sm">Buka Visualisasi Struktur</Button>
          </Link>
        </CardContent>
      </Card>

      {/* 4 PROPOSED KPI CARDS */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {kpis.map((kpi) => (
          <Card key={kpi.id} className="hover:border-primary-400 transition-all flex flex-col justify-between shadow-xs">
            <CardHeader className="pb-2">
              <div className="flex items-center justify-between w-full">
                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{kpi.category}</span>
                <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200">
                  {kpi.status}
                </span>
              </div>
              <CardTitle className="text-xs font-bold text-slate-900 mt-1 line-clamp-2 min-h-[32px]">
                {kpi.name}
              </CardTitle>
            </CardHeader>

            <CardContent className="p-5 pt-0 space-y-2">
              <div className="flex items-baseline justify-between">
                <span className="text-2xl font-extrabold text-primary-950 font-heading">
                  {kpi.value} <span className="text-sm font-normal text-slate-500">{kpi.unit}</span>
                </span>
                {kpi.trendPercentage && (
                  <span
                    className={`inline-flex items-center text-xs font-bold ${
                      kpi.trend === 'UP' ? 'text-emerald-700' : 'text-emerald-700'
                    }`}
                  >
                    {kpi.trend === 'UP' ? <TrendingUp className="w-3.5 h-3.5 mr-0.5" /> : <TrendingDown className="w-3.5 h-3.5 mr-0.5" />}
                    {kpi.trendPercentage > 0 ? `+${kpi.trendPercentage}%` : `${kpi.trendPercentage}%`}
                  </span>
                )}
              </div>

              <p className="text-[11px] text-slate-500 leading-snug">{kpi.description}</p>

              <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-mono">
                <span>Formula: {kpi.formula}</span>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* 2-COLUMN SECTION: ECHELON DISTRIBUTION & TURNAROUND METRICS */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Echelon Distribution */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="text-sm font-bold flex items-center gap-2">
                  <Users className="w-4 h-4 text-primary-800" />
                  Distribusi Komposisi Jabatan Struktural
                </CardTitle>
                <CardDescription className="text-xs">
                  Piramida kepemimpinan ASN berdasarkan jenjang Eselon
                </CardDescription>
              </div>
              <Badge variant="primary">Eselon I - IV</Badge>
            </div>
          </CardHeader>
          <CardContent className="p-5 space-y-4">
            {echelonDist.map((item) => (
              <div key={item.echelon} className="space-y-1.5">
                <div className="flex items-center justify-between text-xs font-medium">
                  <span className="text-slate-800 font-bold">{item.echelon}</span>
                  <span className="text-slate-500 font-mono">
                    {item.count.toLocaleString('id-ID')} Jabatan ({item.percentage}%)
                  </span>
                </div>
                <div className="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className={`h-full ${item.color} rounded-full transition-all duration-500`}
                    style={{ width: `${item.percentage}%` }}
                  />
                </div>
              </div>
            ))}
          </CardContent>
        </Card>

        {/* Submission Turnaround SLA */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle className="text-sm font-bold flex items-center gap-2">
                  <Clock className="w-4 h-4 text-primary-800" />
                  Kecepatan Layanan & SLA Pengusulan
                </CardTitle>
                <CardDescription className="text-xs">
                  Rata-rata durasi penyelesaian telaah kelembagaan per jenis usulan
                </CardDescription>
              </div>
              <Badge variant="gold">Target: &lt; 3 Hari</Badge>
            </div>
          </CardHeader>
          <CardContent className="p-5 space-y-3">
            {turnaround.map((t) => (
              <div key={t.submissionType} className="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between text-xs">
                <div>
                  <span className="font-bold text-slate-900 block">{t.submissionType}</span>
                  <span className="text-[11px] text-slate-500">Terselesaikan: {t.totalCompleted} Berkas Usulan</span>
                </div>
                <div className="text-right">
                  <span className="text-base font-extrabold text-primary-900 font-heading block">
                    {t.averageDays} Hari
                  </span>
                  <span className="text-[10px] text-emerald-700 font-semibold font-mono">Memenuhi SLA</span>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
