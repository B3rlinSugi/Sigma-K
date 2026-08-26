'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Network, Building2, Layers, Search, Sparkles, Filter, Info, Plus, AlertCircle, Lock } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Select } from '@/components/ui/Input';
import { OrgChartCanvas } from '@/components/features/organization/OrgChartCanvas';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { OrganizationService } from '@/services/api/organization.service';
import { InstitutionService } from '@/services/api/institution.service';
import { OrganizationUnit } from '@/types/organization';
import { Institution } from '@/types/institution';
import { useAuth } from '@/context/RoleContext';
import { AppError } from '@/services/http/errors';

export default function OrganizationStructurePage() {
  const { currentRole, currentUser } = useAuth();
  const [institutions, setInstitutions] = useState<Institution[]>([]);
  const [selectedInstId, setSelectedInstId] = useState<string>('1');
  const [orgUnits, setOrgUnits] = useState<OrganizationUnit[]>([]);
  const [isLoadingInst, setIsLoadingInst] = useState(true);
  const [isLoadingUnits, setIsLoadingUnits] = useState(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [isForbidden, setIsForbidden] = useState(false);

  // 1. Load accessible institutions dynamically
  useEffect(() => {
    async function loadInstitutions() {
      setIsLoadingInst(true);
      try {
        const list = await InstitutionService.getInstitutions();
        setInstitutions(list);
        if (list.length > 0) {
          // If user has home institution, prioritize it
          const defaultInst = currentUser.institutionId
            ? list.find((i) => i.id === currentUser.institutionId) || list[0]
            : list[0];
          setSelectedInstId(defaultInst.id);
        }
      } catch (err) {
        console.warn('Failed to load institutions:', err);
      } finally {
        setIsLoadingInst(false);
      }
    }
    loadInstitutions();
  }, [currentUser.institutionId]);

  // 2. Load units hierarchy when selected institution changes
  useEffect(() => {
    async function loadUnits() {
      if (!selectedInstId) return;
      setIsLoadingUnits(true);
      setErrorMessage(null);
      setIsForbidden(false);

      try {
        const units = await OrganizationService.getOrgUnitsByInstitutionId(selectedInstId);
        setOrgUnits(units);
      } catch (err) {
        if (err instanceof AppError) {
          if (err.isForbidden()) {
            setIsForbidden(true);
            setErrorMessage('Akses Ditolak: Anda tidak memiliki wewenang untuk melihat struktur organisasi instansi ini.');
          } else if (err.isNotFound()) {
            setErrorMessage('Instansi atau bagan struktur tidak ditemukan.');
          } else {
            setErrorMessage(err.message || 'Gagal memuat pohon hierarki struktur.');
          }
        } else {
          setErrorMessage('Gagal terhubung ke server struktur organisasi.');
        }
        setOrgUnits([]);
      } finally {
        setIsLoadingUnits(false);
      }
    }
    loadUnits();
  }, [selectedInstId]);

  const currentInstitution = institutions.find((i) => i.id === selectedInstId);

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title="Bagan Struktur Organisasi Interaktif"
        subtitle="Visualisasi pohon hierarki unit kerja struktural berbasis Adjacency List dan kanvas graf interaktif React Flow."
        breadcrumbs={[
          { label: 'Kelembagaan' },
          { label: 'Struktur Organisasi' },
        ]}
        actions={
          (currentRole === 'USER' || currentRole === 'ADMIN') && (
            <Link href="/submissions">
              <Button variant="primary" size="sm" leftIcon={<Plus className="w-3.5 h-3.5" />}>
                Usulkan Penataan Unit
              </Button>
            </Link>
          )
        }
      />

      {/* INSTITUTION SELECTOR & CONTROL BAR */}
      <Card>
        <CardContent className="p-4 flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="w-full md:w-96">
            <Select
              label="Pilih Kementerian / Lembaga untuk Ditampilkan:"
              value={selectedInstId}
              onChange={(e) => setSelectedInstId(e.target.value)}
              disabled={isLoadingInst || institutions.length === 0}
              options={
                institutions.length > 0
                  ? institutions.map((i) => ({ value: i.id, label: `${i.code} - ${i.name}` }))
                  : [{ value: '1', label: 'Memuat data instansi...' }]
              }
            />
          </div>

          <div className="flex flex-wrap items-center gap-2 self-start md:self-end text-xs">
            <span className="text-slate-500 font-medium">Petunjuk Navigasi Kanvas:</span>
            <span className="bg-slate-100 text-slate-700 px-2.5 py-1 rounded border font-mono">Scroll = Zoom</span>
            <span className="bg-slate-100 text-slate-700 px-2.5 py-1 rounded border font-mono">Drag = Pan</span>
            <span className="bg-slate-100 text-slate-700 px-2.5 py-1 rounded border font-mono">Klik Node = Rincian</span>
          </div>
        </CardContent>
      </Card>

      {/* ERROR / FORBIDDEN BANNER */}
      {isForbidden && (
        <div className="p-6 bg-red-50 border border-red-200 rounded-xl flex items-start gap-4 text-red-900 shadow-xs">
          <Lock className="w-6 h-6 text-red-600 shrink-0 mt-0.5" />
          <div className="space-y-1 text-xs">
            <span className="text-sm font-bold block">Akses Struktur Kelembagaan Dibatasi</span>
            <p>{errorMessage}</p>
          </div>
        </div>
      )}

      {/* REACT FLOW CANVAS COMPONENT */}
      {isLoadingUnits ? (
        <Spinner />
      ) : orgUnits.length === 0 && !isForbidden ? (
        <EmptyState 
          title="Struktur Unit Belum Tersedia" 
          description="Instansi yang dipilih belum memiliki data hierarki unit kerja aktif di sistem master." 
        />
      ) : !isForbidden ? (
        <OrgChartCanvas
          units={orgUnits}
          institutionName={currentInstitution?.name || 'Kementerian Koordinator Bidang Pangan'}
        />
      ) : null}

      {/* TECHNICAL HIGHLIGHT CARD */}
      <Card className="bg-slate-50/80 border-slate-200">
        <CardContent className="p-4 flex items-start gap-3 text-xs text-slate-600">
          <Info className="w-4 h-4 text-primary-800 shrink-0 mt-0.5" />
          <div className="space-y-1">
            <p className="font-semibold text-slate-800">
              Fondasi Arsitektur Pohon Organisasi (Phase 2 & Phase 3 Baseline)
            </p>
            <p className="leading-relaxed">
              Bagan di atas dihasilkan secara dinamis dari model data <strong>Adjacency List (`parent_id`)</strong> yang mendukung kueri pohon berjenjang dan divalidasi oleh <strong>Anti-Circular Dependency DFS Guard</strong> pada sisi backend CodeIgniter untuk mencegah siklus atasan-bawahan melingkar.
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
