'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { 
  GitCompare, 
  Split, 
  Plus, 
  RefreshCw, 
  CheckCircle2, 
  Building2, 
  ArrowRight,
  Search,
  Filter,
  Sparkles,
  Info
} from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Tabs } from '@/components/ui/Tabs';
import { DiffViewer } from '@/components/ui/DiffViewer';
import { Spinner } from '@/components/ui/Breadcrumb';
import { CabinetService } from '@/services/api/cabinet.service';
import { CabinetComparisonSummary, LineageTransitionType } from '@/types/cabinet';

export default function CabinetComparePage() {
  const [comparison, setComparison] = useState<CabinetComparisonSummary | null>(null);
  const [selectedFilter, setSelectedFilter] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const data = await CabinetService.getCabinetComparison('cab-indonesia-maju', 'cab-merah-putih');
      setComparison(data);
      setIsLoading(false);
    }
    load();
  }, []);

  if (isLoading || !comparison) return <Spinner />;

  const filteredItems = comparison.items.filter((item) => {
    const matchesFilter = selectedFilter === 'ALL' || item.transitionType === selectedFilter;
    const matchesSearch =
      item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.shortName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.code.toLowerCase().includes(searchQuery.toLowerCase()) ||
      item.details.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesFilter && matchesSearch;
  });

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title="Komparasi & Silsilah Antar-Kabinet"
        subtitle="Analisis perbandingan perubahan nomenklatur, pemecahan kementerian, dan instansi baru antar-era pemerintahan."
        breadcrumbs={[
          { label: 'Master Data', href: '/cabinets' },
          { label: 'Kabinet', href: '/cabinets' },
          { label: 'Komparasi Antar-Kabinet' },
        ]}
      />

      {/* DUAL CABINET SELECTOR BANNER */}
      <Card className="bg-gradient-to-r from-primary-950 via-primary-900 to-slate-900 text-white border-primary-800 shadow-xl overflow-hidden">
        <CardContent className="p-6">
          <div className="flex flex-col md:flex-row items-center justify-between gap-6">
            {/* Cabinet A (Base) */}
            <div className="flex-1 text-center md:text-left space-y-1">
              <span className="text-[10px] text-gold-300 font-bold uppercase tracking-wider">KABINET BASIS (SEBELUMNYA)</span>
              <h3 className="text-xl font-bold text-white font-heading">{comparison.baseCabinet.name}</h3>
              <p className="text-xs text-slate-300">Periode: {comparison.baseCabinet.period} • 34 Kementerian</p>
            </div>

            {/* VS Badge */}
            <div className="shrink-0 flex flex-col items-center">
              <div className="w-12 h-12 rounded-full bg-gold-500 text-slate-950 font-black flex items-center justify-center shadow-lg border-2 border-white">
                VS
              </div>
              <span className="text-[10px] text-gold-300 font-semibold mt-1">Delta Diff</span>
            </div>

            {/* Cabinet B (Target) */}
            <div className="flex-1 text-center md:text-right space-y-1">
              <span className="text-[10px] text-gold-300 font-bold uppercase tracking-wider">KABINET TARGET (AKTIF)</span>
              <h3 className="text-xl font-bold text-white font-heading">{comparison.targetCabinet.name}</h3>
              <p className="text-xs text-slate-300">Periode: {comparison.targetCabinet.period} • 48 Kementerian</p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* SUMMARY DELTA STATS CARDS */}
      <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div className="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs text-center">
          <span className="text-[10px] text-slate-500 font-bold uppercase block">Kementerian Baru</span>
          <span className="text-2xl font-extrabold text-emerald-600 font-heading">+{comparison.addedCount}</span>
          <span className="text-[10px] text-slate-400 block mt-0.5">Entitas Terbentuk</span>
        </div>

        <div className="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs text-center">
          <span className="text-[10px] text-slate-500 font-bold uppercase block">Pemecahan</span>
          <span className="text-2xl font-extrabold text-amber-600 font-heading">{comparison.splitCount}</span>
          <span className="text-[10px] text-slate-400 block mt-0.5">Klaster Kementerian</span>
        </div>

        <div className="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs text-center">
          <span className="text-[10px] text-slate-500 font-bold uppercase block">Ubah Nomenklatur</span>
          <span className="text-2xl font-extrabold text-primary-800 font-heading">{comparison.renamedCount}</span>
          <span className="text-[10px] text-slate-400 block mt-0.5">Penyesuaian Nama</span>
        </div>

        <div className="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs text-center">
          <span className="text-[10px] text-slate-500 font-bold uppercase block">Penggabungan</span>
          <span className="text-2xl font-extrabold text-sky-600 font-heading">{comparison.mergedCount}</span>
          <span className="text-[10px] text-slate-400 block mt-0.5">Merger Instansi</span>
        </div>

        <div className="p-4 rounded-xl bg-white border border-slate-200 shadow-2xs text-center col-span-2 sm:col-span-1">
          <span className="text-[10px] text-slate-500 font-bold uppercase block">Struktur Tetap</span>
          <span className="text-2xl font-extrabold text-slate-700 font-heading">{comparison.unchangedCount}</span>
          <span className="text-[10px] text-slate-400 block mt-0.5">Konsisten</span>
        </div>
      </div>

      {/* FILTER & SEARCH */}
      <Card>
        <div className="px-5 pt-3 border-b border-slate-100">
          <Tabs
            tabs={[
              { id: 'ALL', label: 'Semua Perubahan', count: comparison.items.length },
              { id: 'SPLIT', label: 'Pemecahan (Split)', count: comparison.splitCount },
              { id: 'NEW', label: 'Instansi Baru', count: comparison.addedCount },
              { id: 'RENAME', label: 'Ubah Nomenklatur', count: comparison.renamedCount },
              { id: 'UNCHANGED', label: 'Tetap', count: comparison.unchangedCount },
            ]}
            activeTab={selectedFilter}
            onChange={setSelectedFilter}
          />
        </div>

        <CardContent className="p-4 flex items-center justify-between">
          <div className="relative w-80">
            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari nama kementerian, kode, atau kata kunci..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
            />
          </div>
          <span className="text-xs text-slate-500">
            Menampilkan {filteredItems.length} Catatan Perbandingan
          </span>
        </CardContent>
      </Card>

      {/* DETAILED DIFF VIEWERS LIST */}
      <div className="space-y-4">
        {filteredItems.map((item) => {
          let beforeContent = null;
          let afterContent = null;

          if (item.transitionType === 'SPLIT') {
            beforeContent = (
              <div>
                <p className="font-bold text-slate-800">{item.predecessors?.[0] || 'Kementerian Pendahulu'}</p>
                <p className="text-xs text-slate-500 mt-0.5">Menampung seluruh portofolio sebelum pemisahan.</p>
              </div>
            );
            afterContent = (
              <div>
                <p className="font-bold text-primary-950">{item.name} ({item.shortName})</p>
                <p className="text-xs text-slate-600 mt-0.5">Kode: {item.code} • Kategori: {item.category}</p>
              </div>
            );
          } else if (item.transitionType === 'NEW') {
            beforeContent = null;
            afterContent = (
              <div>
                <p className="font-bold text-emerald-950">{item.name} ({item.shortName})</p>
                <p className="text-xs text-slate-600 mt-0.5">Kode: {item.code} • Kategori: {item.category}</p>
              </div>
            );
          } else if (item.transitionType === 'RENAME') {
            beforeContent = (
              <div>
                <p className="font-bold text-slate-800">{item.predecessors?.[0] || 'Nama Lama'}</p>
              </div>
            );
            afterContent = (
              <div>
                <p className="font-bold text-primary-950">{item.name} ({item.shortName})</p>
              </div>
            );
          } else {
            beforeContent = <p className="text-slate-800 font-semibold">{item.name}</p>;
            afterContent = <p className="text-slate-800 font-semibold">{item.name}</p>;
          }

          return (
            <DiffViewer
              key={item.institutionId}
              title={`${item.code} — ${item.name}`}
              transitionType={item.transitionType}
              beforeLabel={`Kabinet Indonesia Maju (2019)`}
              afterLabel={`Kabinet Merah Putih (2024)`}
              beforeContent={beforeContent}
              afterContent={afterContent}
              details={item.details}
            />
          );
        })}
      </div>
    </div>
  );
}
