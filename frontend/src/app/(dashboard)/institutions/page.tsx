'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Building2, Search, Filter, Plus, ExternalLink, Download, Sparkles, AlertCircle } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { ActiveStatusBadge } from '@/components/ui/StatusBadge';
import { Tabs } from '@/components/ui/Tabs';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { InstitutionService } from '@/services/api/institution.service';
import { Institution } from '@/types/institution';
import { useAuth } from '@/context/RoleContext';
import { AppError } from '@/services/http/errors';

export default function InstitutionsCatalogPage() {
  const { currentRole } = useAuth();
  const [institutions, setInstitutions] = useState<Institution[]>([]);
  const [selectedType, setSelectedType] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      setErrorMessage(null);
      try {
        const list = await InstitutionService.getInstitutions();
        setInstitutions(list);
      } catch (err) {
        if (err instanceof AppError) {
          if (err.isUnauthorized()) {
            setErrorMessage('Sesi telah berakhir atau belum terotentikasi. Silakan masuk kembali.');
          } else if (err.isForbidden()) {
            setErrorMessage('Akses ditolak: Anda tidak memiliki wewenang untuk melihat data instansi ini.');
          } else {
            setErrorMessage(err.message || 'Gagal memuat data katalog instansi.');
          }
        } else {
          setErrorMessage('Gagal terhubung ke server master data.');
        }
      } finally {
        setIsLoading(false);
      }
    }
    load();
  }, []);

  const filteredInstitutions = institutions.filter((inst) => {
    const matchesType = selectedType === 'ALL' || inst.institutionTypeCode === selectedType;
    const matchesSearch =
      inst.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      inst.code.toLowerCase().includes(searchQuery.toLowerCase()) ||
      inst.shortName.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesType && matchesSearch;
  });

  return (
    <div className="space-y-6">
      <PageHeader
        title="Katalog Master Instansi Pemerintah"
        subtitle="Daftar master seluruh Kementerian, Lembaga Pemerintah Non-Kementerian, Lembaga Non-Struktural, dan Pemerintah Daerah se-Indonesia."
        breadcrumbs={[
          { label: 'Master Data' },
          { label: 'Katalog Instansi' },
        ]}
        actions={
          <div className="flex items-center gap-2.5">
            <Button variant="outline" size="sm" leftIcon={<Download className="w-3.5 h-3.5" />}>
              Ekspor Dataset
            </Button>
            {currentRole === 'ADMIN' && (
              <Button variant="primary" size="sm" leftIcon={<Plus className="w-3.5 h-3.5" />}>
                Tambah Instansi
              </Button>
            )}
          </div>
        }
      />

      {/* ERROR BANNER */}
      {errorMessage && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3 text-xs text-red-900 shadow-xs">
          <AlertCircle className="w-5 h-5 text-red-600 shrink-0 mt-0.5" />
          <div>
            <span className="font-bold block text-sm">Terjadi Kesalahan Akses</span>
            <p className="mt-0.5">{errorMessage}</p>
          </div>
        </div>
      )}

      {/* TABS FILTER BY TYPE */}
      <Card>
        <div className="px-5 pt-3 border-b border-slate-100">
          <Tabs
            tabs={[
              { id: 'ALL', label: 'Semua Instansi', count: institutions.length },
              { id: 'KEMENKO', label: 'Kementerian Koordinator', count: institutions.filter((i) => i.institutionTypeCode === 'KEMENKO').length },
              { id: 'KEMENTERIAN_TEKNIS', label: 'Kementerian Teknis', count: institutions.filter((i) => i.institutionTypeCode === 'KEMENTERIAN_TEKNIS').length },
              { id: 'PEMDA_PROVINSI', label: 'Pemerintah Daerah Provinsi', count: institutions.filter((i) => i.institutionTypeCode === 'PEMDA_PROVINSI').length },
            ]}
            activeTab={selectedType}
            onChange={setSelectedType}
          />
        </div>

        {/* SEARCH & CONTROLS */}
        <CardContent className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-100">
          <div className="relative w-full sm:w-80">
            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari kode, nama kementerian/pemda..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
            />
          </div>

          <span className="text-xs text-slate-500 font-medium">
            Menampilkan {filteredInstitutions.length} dari {institutions.length} Instansi Terdaftar
          </span>
        </CardContent>

        {/* TABLE CONTENT */}
        {isLoading ? (
          <Spinner />
        ) : filteredInstitutions.length === 0 && !errorMessage ? (
          <EmptyState title="Instansi Tidak Ditemukan" description="Coba ubah kata kunci pencarian atau tab filter jenis instansi." />
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-12 text-center">No</TableHead>
                <TableHead className="w-24">Kode</TableHead>
                <TableHead>Nama Resmi Instansi</TableHead>
                <TableHead>Klasifikasi Jenis</TableHead>
                <TableHead>Kabinet / Wilayah</TableHead>
                <TableHead className="text-center">Unit Kerja</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredInstitutions.map((inst, idx) => (
                <TableRow key={inst.id}>
                  <TableCell className="text-center font-mono text-xs text-slate-500">{idx + 1}</TableCell>
                  <TableCell>
                    <span className="font-mono text-xs font-bold text-primary-900 bg-primary-50 px-2 py-0.5 rounded border border-primary-200">
                      {inst.code}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div>
                      <span className="font-semibold text-slate-900 block">{inst.name}</span>
                      <span className="text-xs text-slate-500">{inst.shortName}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant={inst.institutionTypeCode === 'KEMENKO' ? 'gold' : 'primary'}>
                      {inst.institutionTypeName}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-xs text-slate-600">
                    {inst.currentCabinetName || inst.regionName || '-'}
                  </TableCell>
                  <TableCell className="text-center font-semibold text-xs text-slate-800">
                    {inst.totalOrgUnits || 0} Unit
                  </TableCell>
                  <TableCell>
                    <ActiveStatusBadge isActive={inst.status === 'ACTIVE'} />
                  </TableCell>
                  <TableCell className="text-right">
                    <Link href={`/institutions/${inst.id}`}>
                      <Button variant="outline" size="sm" rightIcon={<ExternalLink className="w-3.5 h-3.5" />}>
                        Profil
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </Card>
    </div>
  );
}
