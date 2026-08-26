'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { 
  Building2, 
  MapPin, 
  Phone, 
  Mail, 
  Globe, 
  FileText, 
  Network, 
  BookOpen, 
  History, 
  ShieldCheck, 
  FileEdit,
  ExternalLink,
  Users,
  Download,
  Calendar,
  CheckCircle2,
  AlertCircle,
  Lock,
  ArrowLeft
} from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { ActiveStatusBadge } from '@/components/ui/StatusBadge';
import { Tabs } from '@/components/ui/Tabs';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { InstitutionService } from '@/services/api/institution.service';
import { OrganizationService } from '@/services/api/organization.service';
import { Institution } from '@/types/institution';
import { OrganizationUnit, TupoksiItem } from '@/types/organization';
import { useAuth } from '@/context/RoleContext';
import { AppError } from '@/services/http/errors';

export default function InstitutionDetailPage() {
  const params = useParams();
  const instId = (params?.id as string) || '1';
  const { currentRole, isApiMode } = useAuth();

  const [institution, setInstitution] = useState<Institution | null>(null);
  const [orgUnits, setOrgUnits] = useState<OrganizationUnit[]>([]);
  const [tupoksiList, setTupoksiList] = useState<TupoksiItem[]>([]);
  const [activeTab, setActiveTab] = useState('OVERVIEW');
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isForbidden, setIsForbidden] = useState(false);
  const [isNotFound, setIsNotFound] = useState(false);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      setErrorMessage(null);
      setIsForbidden(false);
      setIsNotFound(false);

      try {
        const [inst, units, tupoksis] = await Promise.all([
          InstitutionService.getInstitutionById(instId),
          OrganizationService.getOrgUnitsByInstitutionId(instId),
          OrganizationService.getTupoksiByInstitutionId(instId),
        ]);

        if (!inst) {
          setIsNotFound(true);
          setErrorMessage('Instansi tidak ditemukan.');
        } else {
          setInstitution(inst);
          setOrgUnits(units);
          setTupoksiList(tupoksis);
        }
      } catch (err) {
        if (err instanceof AppError) {
          if (err.isForbidden()) {
            setIsForbidden(true);
            setErrorMessage('Akses Ditolak: Anda tidak memiliki wewenang akses (Zero-Trust Authorization) untuk melihat instansi ini.');
          } else if (err.isNotFound()) {
            setIsNotFound(true);
            setErrorMessage('Instansi dengan ID tersebut tidak ditemukan di database master.');
          } else if (err.isUnauthorized()) {
            setErrorMessage('Sesi telah berakhir. Silakan masuk kembali.');
          } else {
            setErrorMessage(err.message || 'Gagal memuat detail instansi.');
          }
        } else {
          setErrorMessage('Gagal terhubung ke server master data.');
        }
      } finally {
        setIsLoading(false);
      }
    }
    load();
  }, [instId]);

  if (isLoading) return <Spinner />;

  if (isForbidden) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Akses Ditolak (403 Forbidden)"
          subtitle="Kebijakan Otorisasi Zero-Trust KemenPANRB"
          breadcrumbs={[
            { label: 'Master Data', href: '/institutions' },
            { label: 'Akses Ditolak' },
          ]}
        />
        <Card className="border-red-200 bg-red-50/50">
          <CardContent className="p-8 text-center space-y-4">
            <div className="w-12 h-12 rounded-full bg-red-100 text-red-700 flex items-center justify-center mx-auto">
              <Lock className="w-6 h-6" />
            </div>
            <div className="max-w-md mx-auto space-y-1.5">
              <h3 className="text-base font-bold text-slate-900">Pembatasan Hak Akses Instansi</h3>
              <p className="text-xs text-slate-600 leading-relaxed">
                {errorMessage}
              </p>
            </div>
            <div className="pt-2">
              <Link href="/institutions">
                <Button variant="outline" size="sm" leftIcon={<ArrowLeft className="w-3.5 h-3.5" />}>
                  Kembali ke Katalog Instansi
                </Button>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (isNotFound || !institution) {
    return (
      <div className="space-y-6">
        <PageHeader
          title="Instansi Tidak Ditemukan (404)"
          breadcrumbs={[
            { label: 'Master Data', href: '/institutions' },
            { label: 'Tidak Ditemukan' },
          ]}
        />
        <EmptyState 
          title="Data Instansi Tidak Ditemukan" 
          description={errorMessage || "ID instansi yang Anda tuju tidak terdaftar di sistem master data."} 
        />
      </div>
    );
  }

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title={institution.name}
        subtitle={`${institution.shortName} • ${institution.institutionTypeName}`}
        breadcrumbs={[
          { label: 'Master Data', href: '/institutions' },
          { label: 'Katalog Instansi', href: '/institutions' },
          { label: institution.shortName },
        ]}
        badge={<ActiveStatusBadge isActive={institution.status === 'ACTIVE'} />}
        actions={
          <div className="flex items-center gap-2.5">
            <Link href="/structure">
              <Button variant="outline" size="sm" leftIcon={<Network className="w-4 h-4" />}>
                Buka Bagan Canvas
              </Button>
            </Link>
            {(currentRole === 'USER' || currentRole === 'ADMIN') && (
              <Link href="/submissions">
                <Button variant="primary" size="sm" leftIcon={<FileEdit className="w-4 h-4" />}>
                  Ajukan Usulan Perubahan
                </Button>
              </Link>
            )}
          </div>
        }
      />

      {/* Profile Header Hero Card */}
      <Card className="bg-gradient-to-r from-primary-950 via-primary-900 to-slate-900 text-white border-primary-800 shadow-md">
        <CardContent className="p-6">
          <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div className="flex items-center gap-4">
              <div className="w-16 h-16 rounded-2xl bg-white/10 border border-gold-400/40 p-2.5 flex items-center justify-center shadow-lg">
                <Building2 className="w-10 h-10 text-gold-400" />
              </div>
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-mono text-xs font-bold text-gold-300 bg-gold-500/20 px-2.5 py-0.5 rounded border border-gold-400/30">
                    {institution.code}
                  </span>
                  <Badge variant="primary">{institution.institutionTypeName}</Badge>
                </div>
                <h2 className="text-xl font-bold font-heading text-white mt-1">
                  {institution.name}
                </h2>
                <p className="text-xs text-slate-300 mt-0.5">
                  Kabinet: {institution.currentCabinetName || 'Kabinet Merah Putih'}
                </p>
              </div>
            </div>

            <div className="flex flex-wrap gap-4 text-xs text-slate-200 border-t md:border-t-0 md:border-l border-slate-700/80 pt-4 md:pt-0 md:pl-6">
              <div>
                <span className="text-[10px] text-slate-400 block font-bold uppercase">Struktur Unit</span>
                <span className="text-base font-bold text-white font-heading">{orgUnits.length} Unit</span>
              </div>
              <div>
                <span className="text-[10px] text-slate-400 block font-bold uppercase">Butir Tupoksi</span>
                <span className="text-base font-bold text-gold-300 font-heading">{tupoksiList.length} Butir</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* TABS NAVIGATION */}
      <Tabs
        tabs={[
          { id: 'OVERVIEW', label: 'Ringkasan & Profil' },
          { id: 'STRUCTURE', label: `Struktur Unit Kerja (${orgUnits.length})` },
          { id: 'TUPOKSI', label: `Tugas & Fungsi (${tupoksiList.length})` },
        ]}
        activeTab={activeTab}
        onChange={setActiveTab}
      />

      {/* TAB 1: OVERVIEW */}
      {activeTab === 'OVERVIEW' && (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <Card className="md:col-span-2">
            <CardHeader>
              <CardTitle className="text-sm font-bold flex items-center gap-2">
                <FileText className="w-4 h-4 text-primary-800" />
                Dasar Hukum & Visi Misi
              </CardTitle>
            </CardHeader>
            <CardContent className="p-5 space-y-4 text-xs">
              <div className="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
                <span className="text-[10px] font-bold text-slate-500 uppercase">Dasar Hukum Pembentukan</span>
                <p className="font-semibold text-slate-900">
                  {institution.profile?.legalBasisSummary || `SK Pembentukan Instansi Pemerintah Kode ${institution.code}`}
                </p>
              </div>

              <div className="space-y-1">
                <span className="text-[10px] font-bold text-slate-500 uppercase">Visi Instansi</span>
                <p className="text-slate-700 leading-relaxed bg-white p-3 rounded border border-slate-100">
                  {institution.profile?.visionStatement || 'Mewujudkan tata kelola kelembagaan aparatur negara yang profesional, akuntabel, dan berorientasi pelayanan publik.'}
                </p>
              </div>

              <div className="space-y-1">
                <span className="text-[10px] font-bold text-slate-500 uppercase">Misi Instansi</span>
                <p className="text-slate-700 leading-relaxed whitespace-pre-line bg-white p-3 rounded border border-slate-100">
                  {institution.profile?.missionStatement || '1. Mengembangkan postur kelembagaan yang efisien dan adaptif.\n2. Mendorong transformasi digital SPBE dan interoperabilitas data.'}
                </p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-bold flex items-center gap-2">
                <MapPin className="w-4 h-4 text-primary-800" />
                Kontak & Alamat
              </CardTitle>
            </CardHeader>
            <CardContent className="p-5 space-y-3 text-xs">
              <div className="flex items-start gap-2.5">
                <MapPin className="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                <div>
                  <span className="font-bold text-slate-800 block">Alamat Kantor</span>
                  <span className="text-slate-600">{institution.profile?.address || 'Kompleks Perkantoran Pemerintah, Jakarta Pusat'}</span>
                </div>
              </div>

              <div className="flex items-center gap-2.5">
                <Phone className="w-4 h-4 text-slate-400 shrink-0" />
                <div>
                  <span className="font-bold text-slate-800 block">Telepon</span>
                  <span className="text-slate-600">{institution.profile?.phone || '(021) 7398381'}</span>
                </div>
              </div>

              <div className="flex items-center gap-2.5">
                <Mail className="w-4 h-4 text-slate-400 shrink-0" />
                <div>
                  <span className="font-bold text-slate-800 block">Email Resmi</span>
                  <span className="text-slate-600">{institution.profile?.email || 'kontak@instansi.go.id'}</span>
                </div>
              </div>

              <div className="flex items-center gap-2.5">
                <Globe className="w-4 h-4 text-slate-400 shrink-0" />
                <div>
                  <span className="font-bold text-slate-800 block">Situs Web</span>
                  <a href={institution.profile?.websiteUrl || '#'} target="_blank" rel="noreferrer" className="text-primary-800 hover:underline">
                    {institution.profile?.websiteUrl || 'https://www.instansi.go.id'}
                  </a>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* TAB 2: STRUCTURE UNITS */}
      {activeTab === 'STRUCTURE' && (
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-3 border-b border-slate-100">
            <CardTitle className="text-sm font-bold flex items-center gap-2">
              <Network className="w-4 h-4 text-primary-800" />
              Daftar Unit Kerja Struktural Terdaftar
            </CardTitle>
            <Link href="/structure">
              <Button variant="outline" size="sm" rightIcon={<ExternalLink className="w-3.5 h-3.5" />}>
                Buka Bagan Canvas Interaktif
              </Button>
            </Link>
          </CardHeader>
          <CardContent className="p-0">
            {orgUnits.length === 0 ? (
              <div className="p-8 text-center text-xs text-slate-500">
                Belum ada struktur unit kerja yang terdaftar untuk instansi ini.
              </div>
            ) : (
              <div className="divide-y divide-slate-100">
                {orgUnits.map((u) => (
                  <div key={u.id} className="p-4 flex items-center justify-between hover:bg-slate-50 transition text-xs">
                    <div className="flex items-center gap-3">
                      <span className="font-mono font-bold text-[11px] px-2 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200">
                        {u.unitCode}
                      </span>
                      <div>
                        <span className="font-bold text-slate-900 block">{u.unitName}</span>
                        <span className="text-slate-500">Level Hierarki: {u.hierarchyLevel}</span>
                      </div>
                    </div>
                    <Badge variant="success">Aktif</Badge>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* TAB 3: TUPOKSI */}
      {activeTab === 'TUPOKSI' && (
        <Card>
          <CardHeader>
            <CardTitle className="text-sm font-bold flex items-center gap-2">
              <BookOpen className="w-4 h-4 text-primary-800" />
              Rincian Tugas Pokok & Fungsi Organisasi
            </CardTitle>
          </CardHeader>
          <CardContent className="p-5 space-y-3">
            {tupoksiList.length === 0 ? (
              <div className="p-8 text-center text-xs text-slate-500">
                Belum ada rincian tugas dan fungsi tersimpan untuk instansi ini.
              </div>
            ) : (
              tupoksiList.map((t, idx) => (
                <div key={t.id} className="p-3.5 rounded-lg border border-slate-200 bg-slate-50 text-xs space-y-1">
                  <div className="flex items-center justify-between">
                    <span className="font-bold text-primary-900">
                      {t.type === 'DUTY' ? 'Tugas Pokok' : `Fungsi Organisasi #${idx + 1}`}
                    </span>
                    {t.legalArticleReference && (
                      <span className="font-mono text-[10px] text-slate-500 bg-white px-2 py-0.5 rounded border border-slate-200">
                        {t.legalArticleReference}
                      </span>
                    )}
                  </div>
                  <p className="text-slate-700 leading-relaxed">{t.contentText}</p>
                </div>
              ))
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}
