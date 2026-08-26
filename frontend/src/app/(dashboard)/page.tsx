'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import {
  Building2,
  Landmark,
  Inbox,
  FileCheck2,
  TrendingUp,
  ArrowRight,
  Sparkles,
  GitCompare,
  Network,
  Users,
  ShieldCheck,
  CheckCircle2,
  Clock,
  ExternalLink,
  ChevronRight,
  Info,
  AlertCircle,
  Briefcase
} from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { WorkflowStatusBadge, ActiveStatusBadge } from '@/components/ui/StatusBadge';
import { useAuth } from '@/context/RoleContext';
import { InstitutionService } from '@/services/api/institution.service';
import { CabinetService } from '@/services/api/cabinet.service';
import { SubmissionService } from '@/services/api/submission.service';
import { AnalyticsService } from '@/services/api/analytics.service';
import { Institution } from '@/types/institution';
import { Cabinet } from '@/types/cabinet';
import { SubmissionTicket } from '@/types/submission';
import { KPICandidate, CabinetCompositionStats, ExecutiveDashboardSummary } from '@/types/analytics';
import { formatNumber, formatDateTimeIndonesian } from '@/lib/utils';
import { Spinner } from '@/components/ui/Breadcrumb';
import { AppError } from '@/services/http/errors';

export default function ExecutiveDashboardPage() {
  const { currentRole, currentUser, isApiMode } = useAuth();
  const [institutions, setInstitutions] = useState<Institution[]>([]);
  const [cabinets, setCabinets] = useState<Cabinet[]>([]);
  const [submissions, setSubmissions] = useState<SubmissionTicket[]>([]);
  const [kpis, setKpis] = useState<KPICandidate[]>([]);
  const [composition, setComposition] = useState<CabinetCompositionStats[]>([]);
  const [reportSummary, setReportSummary] = useState<ExecutiveDashboardSummary | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    async function loadData() {
      setIsLoading(true);
      setErrorMessage(null);
      try {
        const [instList, cabList, subList, kpiList, compList, summary] = await Promise.all([
          InstitutionService.getInstitutions().catch(() => []),
          CabinetService.getCabinets().catch(() => []),
          SubmissionService.getSubmissions().catch(() => []),
          AnalyticsService.getKPIs().catch(() => []),
          AnalyticsService.getCabinetComposition().catch(() => []),
          AnalyticsService.getReportSummary().catch((err) => {
            console.warn('Failed to load report summary:', err);
            return null;
          }),
        ]);
        setInstitutions(instList);
        setCabinets(cabList);
        setSubmissions(subList);
        setKpis(kpiList);
        setComposition(compList);
        setReportSummary(summary);
      } catch (err) {
        if (err instanceof AppError) {
          setErrorMessage(err.message || 'Gagal memuat ringkasan dashboard eksekutif.');
        } else {
          setErrorMessage('Gagal terhubung ke layanan pelaporan eksekutif.');
        }
      } finally {
        setIsLoading(false);
      }
    }
    loadData();
  }, []);

  const activeCabinet = cabinets.find((c) => c.isActive) || cabinets[0];
  const pendingSubmissions = submissions.filter((s) => s.status === 'SUBMITTED' || s.status === 'IN_REVIEW');
  const verifiedSubmissions = submissions.filter((s) => s.status === 'VERIFIED');

  // Authoritative overview metrics from API report summary
  const totalInsts = reportSummary?.overview?.totalInstitutions ?? institutions.length;
  const totalActiveUnits = reportSummary?.overview?.totalActiveUnits ?? 342;
  const totalFormations = reportSummary?.overview?.totalFormations ?? 6420;
  const inReviewFunnel = reportSummary ? (reportSummary.funnel.screening + reportSummary.funnel.verification) : pendingSubmissions.length;
  const completedFunnel = reportSummary ? (reportSummary.funnel.approved + reportSummary.funnel.promoted) : verifiedSubmissions.length;

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* Top Welcome Banner */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-950 via-primary-900 to-slate-900 text-white p-6 sm:p-8 shadow-xl border border-primary-800/80">
        <div className="absolute right-0 top-0 bottom-0 w-1/3 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-gold-500/20 via-transparent to-transparent pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div className="space-y-2 max-w-2xl">
            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gold-500/20 text-gold-300 border border-gold-400/30 text-xs font-semibold">
              <Sparkles className="w-3.5 h-3.5" />
              Sistem Informasi Kelembagaan Nasional — SIGMA-K
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight font-heading text-white">
              Dashboard Eksekutif Kelembagaan
            </h1>
            <p className="text-xs sm:text-sm text-slate-300 leading-relaxed">
              Monitoring terpadu status restrukturisasi Kementerian/Lembaga Kabinet Merah Putih, bagan struktur organisasi, dan tata kelola pengajuan KemenPANRB.
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3 shrink-0">
            <Link href="/cabinets/compare">
              <Button variant="gold" size="md" leftIcon={<GitCompare className="w-4 h-4" />}>
                Komparasi Kabinet
              </Button>
            </Link>
            <Link href="/structure">
              <Button variant="outline" size="md" className="bg-white/10 text-white border-white/20 hover:bg-white/20" leftIcon={<Network className="w-4 h-4" />}>
                Bagan Organisasi
              </Button>
            </Link>
          </div>
        </div>
      </div>

      {/* ERROR BANNER */}
      {errorMessage && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3 text-xs text-red-900">
          <AlertCircle className="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
          <div>
            <span className="font-bold block text-sm">Terjadi Kesalahan Sinkronisasi</span>
            <p className="mt-0.5">{errorMessage}</p>
          </div>
        </div>
      )}

      {/* PROTOTYPE / API MODE DATA NOTICE */}
      <div className={`p-3 rounded-xl border text-xs flex items-center justify-between gap-3 ${
        isApiMode 
          ? 'bg-emerald-500/10 border-emerald-300/80 text-emerald-900' 
          : 'bg-amber-500/10 border-amber-300/80 text-amber-900'
      }`}>
        <div className="flex items-center gap-2">
          <Info className={`w-4 h-4 shrink-0 ${isApiMode ? 'text-emerald-700' : 'text-amber-700'}`} />
          <span>
            {isApiMode ? (
              <><strong>Mode Produksi API Aktif:</strong> Data ringkasan dashboard, formasi jabatan, dan antrean telaah bersumber langsung dari REST API Backend CodeIgniter 4 (`eskld_db`).</>
            ) : (
              <><strong>Pemberitahuan Data Prototipe:</strong> Angka agregat dan metrik pada prototipe ini disajikan sebagai simulasi demonstrasi alur interaksi dan bukan merupakan rilis data resmi final pemerintah.</>
            )}
          </span>
        </div>
        <Badge variant={isApiMode ? 'success' : 'warning'} size="sm">
          {isApiMode ? 'API Live Data' : 'Prototype Simulation'}
        </Badge>
      </div>

      {/* KPI METRIC CARDS GRID */}
      {isLoading ? (
        <Spinner />
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {/* Metric 1 */}
          <Card className="hover:border-primary-300 transition-all shadow-xs">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kementerian / Lembaga</p>
                <h3 className="text-2xl font-bold text-slate-900 mt-1 font-heading">
                  {totalInsts} K/L
                </h3>
                <p className="text-[11px] text-emerald-700 font-medium flex items-center gap-1 mt-1">
                  <TrendingUp className="w-3 h-3" /> Dalam Lingkup Wewenang
                </p>
              </div>
              <div className="w-12 h-12 rounded-xl bg-primary-50 text-primary-900 flex items-center justify-center border border-primary-100">
                <Building2 className="w-6 h-6" />
              </div>
            </CardContent>
          </Card>

          {/* Metric 2 */}
          <Card className="hover:border-primary-300 transition-all shadow-xs">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unit Kerja & Formasi</p>
                <h3 className="text-2xl font-bold text-slate-900 mt-1 font-heading">
                  {totalActiveUnits} Unit
                </h3>
                <p className="text-[11px] text-slate-500 mt-1">
                  {totalFormations} Total Formasi ASN
                </p>
              </div>
              <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center border border-amber-200">
                <Briefcase className="w-6 h-6" />
              </div>
            </CardContent>
          </Card>

          {/* Metric 3 */}
          <Card className="hover:border-primary-300 transition-all shadow-xs">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Antrean Penelaahan</p>
                <h3 className="text-2xl font-bold text-amber-600 mt-1 font-heading">
                  {inReviewFunnel} Berkas
                </h3>
                <p className="text-[11px] text-slate-500 mt-1">Penapisan Admin & Telaah Verifikator</p>
              </div>
              <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center border border-amber-200">
                <Inbox className="w-6 h-6" />
              </div>
            </CardContent>
          </Card>

          {/* Metric 4 */}
          <Card className="hover:border-primary-300 transition-all shadow-xs">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Disahkan / Diterapkan</p>
                <h3 className="text-2xl font-bold text-emerald-700 mt-1 font-heading">
                  {completedFunnel} Berkas
                </h3>
                <p className="text-[11px] text-slate-500 mt-1">Lolos pengesahan substantif</p>
              </div>
              <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-200">
                <FileCheck2 className="w-6 h-6" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* 2-COLUMN MAIN CONTENT: SPOTLIGHT & RECENT APPROVALS / ACTIVITY */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Cols: Cabinet Spotlight */}
        <div className="lg:col-span-2 space-y-6">
          <Card className="border-primary-200/80 shadow-sm">
            <CardHeader className="bg-gradient-to-r from-primary-900 to-slate-900 text-white flex items-center justify-between">
              <div>
                <span className="text-[10px] uppercase font-bold text-gold-300 tracking-wider">
                  KABINET AKTIF PEMERINTAHAN
                </span>
                <CardTitle className="text-lg text-white mt-0.5">
                  {activeCabinet?.name || 'Kabinet Merah Putih'}
                </CardTitle>
                <CardDescription className="text-slate-300">
                  Periode 2024–2029 • Presiden {activeCabinet?.presidentName}
                </CardDescription>
              </div>
              <ActiveStatusBadge isActive={true} />
            </CardHeader>

            <CardContent className="p-5 space-y-4">
              <p className="text-xs text-slate-600 leading-relaxed">
                {activeCabinet?.description}
              </p>

              {/* Highlights 3 Key Transformation Ministries */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                  <span className="text-[10px] font-bold text-primary-900 uppercase">Kemenko Baru</span>
                  <h4 className="text-xs font-bold text-slate-900 mt-1 line-clamp-1">Kemenko Bidang Pangan</h4>
                  <p className="text-[11px] text-slate-500 mt-0.5">Perpres No. 147/2024</p>
                  <Link href="/institutions/1" className="text-[11px] text-primary-800 font-semibold mt-2 inline-flex items-center gap-1 hover:underline">
                    Lihat Profil &rarr;
                  </Link>
                </div>

                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                  <span className="text-[10px] font-bold text-amber-700 uppercase">Pecah Instansi</span>
                  <h4 className="text-xs font-bold text-slate-900 mt-1 line-clamp-1">Kemendikdasmen</h4>
                  <p className="text-[11px] text-slate-500 mt-0.5">Perpres No. 188/2024</p>
                  <Link href="/institutions/3" className="text-[11px] text-primary-800 font-semibold mt-2 inline-flex items-center gap-1 hover:underline">
                    Lihat Profil &rarr;
                  </Link>
                </div>

                <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200">
                  <span className="text-[10px] font-bold text-emerald-700 uppercase">Kementerian Pembina</span>
                  <h4 className="text-xs font-bold text-slate-900 mt-1 line-clamp-1">Kementerian PANRB</h4>
                  <p className="text-[11px] text-slate-500 mt-0.5">Perpres No. 47/2021</p>
                  <Link href="/institutions/2" className="text-[11px] text-primary-800 font-semibold mt-2 inline-flex items-center gap-1 hover:underline">
                    Lihat Profil &rarr;
                  </Link>
                </div>
              </div>
            </CardContent>

            <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
              <Link href="/cabinets/cab-merah-putih" className="text-xs text-primary-900 font-bold hover:underline flex items-center gap-1">
                Daftar Lengkap Kabinet Merah Putih <ArrowRight className="w-3.5 h-3.5" />
              </Link>
              <span className="text-[11px] text-slate-400">Dasar Hukum: Keppres 133/P/2024</span>
            </div>
          </Card>

          {/* Recent Submissions Table */}
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3 border-b border-slate-100">
              <div>
                <CardTitle className="text-base">Pengajuan Perubahan Kelembagaan Terkini</CardTitle>
                <CardDescription>Daftar usulan struktur organisasi dan tupoksi yang masuk ke KemenPANRB</CardDescription>
              </div>
              <Link href="/submissions">
                <Button variant="outline" size="sm">Lihat Semua</Button>
              </Link>
            </CardHeader>
            <CardContent className="p-0">
              {submissions.length === 0 ? (
                <div className="p-6 text-center text-xs text-slate-500">Belum ada pengajuan terkini.</div>
              ) : (
                <div className="divide-y divide-slate-100">
                  {submissions.slice(0, 5).map((sub) => (
                    <div key={sub.id} className="p-4 flex items-center justify-between hover:bg-slate-50 transition text-xs">
                      <div>
                        <span className="font-bold text-slate-900 block">{sub.title}</span>
                        <span className="text-slate-500 font-mono text-[11px]">{sub.ticketNumber} • {sub.institutionName}</span>
                      </div>
                      <WorkflowStatusBadge status={sub.status} />
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Right 1 Col: Recent Approvals & Quick Links */}
        <div className="space-y-6">
          {/* RECENT APPROVALS WIDGET */}
          <Card>
            <CardHeader className="pb-3 border-b border-slate-100">
              <CardTitle className="text-sm font-bold flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-primary-800" />
                Pengesahan Substantif Terkini
              </CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              {(!reportSummary?.recentApprovals || reportSummary.recentApprovals.length === 0) ? (
                <div className="p-5 text-center text-xs text-slate-500">
                  Belum ada catatan pengesahan SK baru.
                </div>
              ) : (
                <div className="divide-y divide-slate-100">
                  {reportSummary.recentApprovals.map((ra) => (
                    <div key={ra.id} className="p-3.5 hover:bg-slate-50 transition text-xs space-y-1">
                      <div className="flex items-center justify-between">
                        <span className="font-mono text-[10px] font-bold bg-emerald-50 text-emerald-800 px-2 py-0.5 rounded border border-emerald-200">
                          {ra.approvalNumber}
                        </span>
                        <span className="text-[10px] text-slate-400">
                          {ra.approvedAt ? ra.approvedAt.split(' ')[0] : '-'}
                        </span>
                      </div>
                      <p className="font-bold text-slate-900 line-clamp-1">{ra.submissionTitle}</p>
                      <p className="text-slate-500 text-[11px]">{ra.institutionName}</p>
                      <p className="text-[10px] text-primary-800 font-medium">Verifikator: {ra.approverName}</p>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>

          {/* QUICK SHORTCUTS */}
          <Card>
            <CardHeader className="pb-3 border-b border-slate-100">
              <CardTitle className="text-sm font-bold">Akses Cepat Modul</CardTitle>
            </CardHeader>
            <CardContent className="p-3.5 space-y-2 text-xs">
              <Link href="/analytics" className="p-2.5 rounded-lg border border-slate-200 hover:border-primary-300 hover:bg-slate-50 flex items-center justify-between block transition">
                <span className="font-semibold text-slate-800">Intelijensi & Analitik ASN</span>
                <ChevronRight className="w-4 h-4 text-slate-400" />
              </Link>
              <Link href="/audit-logs" className="p-2.5 rounded-lg border border-slate-200 hover:border-primary-300 hover:bg-slate-50 flex items-center justify-between block transition">
                <span className="font-semibold text-slate-800">Audit Trail Kelembagaan</span>
                <ChevronRight className="w-4 h-4 text-slate-400" />
              </Link>
              <Link href="/institutions" className="p-2.5 rounded-lg border border-slate-200 hover:border-primary-300 hover:bg-slate-50 flex items-center justify-between block transition">
                <span className="font-semibold text-slate-800">Katalog Master K/L/D</span>
                <ChevronRight className="w-4 h-4 text-slate-400" />
              </Link>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
