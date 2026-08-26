'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Landmark, Plus, Search, GitCompare, Calendar, Users, ArrowRight, CheckCircle2 } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { ActiveStatusBadge } from '@/components/ui/StatusBadge';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { CabinetService } from '@/services/api/cabinet.service';
import { Cabinet } from '@/types/cabinet';
import { useRole } from '@/context/RoleContext';
import { formatDateIndonesian } from '@/lib/utils';

export default function CabinetsPage() {
  const { currentRole } = useRole();
  const [cabinets, setCabinets] = useState<Cabinet[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const list = await CabinetService.getCabinets();
      setCabinets(list);
      setIsLoading(false);
    }
    load();
  }, []);

  const filteredCabinets = cabinets.filter((c) =>
    c.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    c.presidentName.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <PageHeader
        title="Manajemen Kabinet Kepresidenan"
        subtitle="Kelola data kabinet pemerintahan Republik Indonesia, rentang periode jabatan, dan keanggotaan kementerian."
        breadcrumbs={[
          { label: 'Master Data' },
          { label: 'Kabinet Kepresidenan' },
        ]}
        actions={
          <div className="flex items-center gap-2.5">
            <Link href="/cabinets/compare">
              <Button variant="outline" size="sm" leftIcon={<GitCompare className="w-4 h-4" />}>
                Komparasi Antar-Kabinet
              </Button>
            </Link>
            {(currentRole === 'ADMIN' || currentRole === 'SESDEP') && (
              <Link href="/cabinets/new">
                <Button variant="primary" size="sm" leftIcon={<Plus className="w-4 h-4" />}>
                  Tambah Kabinet
                </Button>
              </Link>
            )}
          </div>
        }
      />

      {/* Search & Filter Bar */}
      <Card>
        <CardContent className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="relative w-full sm:w-96">
            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari nama kabinet atau presiden..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
            />
          </div>

          <span className="text-xs text-slate-500 font-medium self-end sm:self-center">
            Menampilkan {filteredCabinets.length} Era Kabinet
          </span>
        </CardContent>
      </Card>

      {/* Cabinets Grid */}
      {isLoading ? (
        <Spinner />
      ) : filteredCabinets.length === 0 ? (
        <EmptyState title="Kabinet Tidak Ditemukan" description="Tidak ada kabinet yang sesuai dengan kata kunci pencarian." />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredCabinets.map((cabinet) => (
            <Card
              key={cabinet.id}
              className={`hover:border-primary-400 transition-all flex flex-col justify-between ${
                cabinet.isActive ? 'ring-2 ring-primary-900 border-primary-900 shadow-md' : 'shadow-xs'
              }`}
            >
              <div>
                <CardHeader className="bg-slate-50/70">
                  <div className="space-y-1">
                    <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                      Era Pemerintahan
                    </span>
                    <CardTitle className="text-base font-bold text-slate-900">
                      {cabinet.name}
                    </CardTitle>
                  </div>
                  <ActiveStatusBadge isActive={cabinet.isActive} />
                </CardHeader>

                <CardContent className="p-5 space-y-4 text-xs">
                  <div className="space-y-2">
                    <div className="flex justify-between">
                      <span className="text-slate-500">Presiden RI:</span>
                      <span className="font-bold text-slate-800 text-right">{cabinet.presidentName}</span>
                    </div>
                    {cabinet.vicePresidentName && (
                      <div className="flex justify-between">
                        <span className="text-slate-500">Wakil Presiden:</span>
                        <span className="font-bold text-slate-800 text-right">{cabinet.vicePresidentName}</span>
                      </div>
                    )}
                    <div className="flex justify-between">
                      <span className="text-slate-500">Total Kementerian/Lembaga:</span>
                      <Badge variant="primary">{cabinet.totalMembers || 34} K/L</Badge>
                    </div>
                  </div>

                  <p className="text-slate-600 line-clamp-3 leading-relaxed pt-2 border-t border-slate-100">
                    {cabinet.description}
                  </p>
                </CardContent>
              </div>

              <div className="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <Link
                  href={`/cabinets/${cabinet.id}`}
                  className="w-full"
                >
                  <Button variant="outline" size="sm" className="w-full font-semibold" rightIcon={<ArrowRight className="w-3.5 h-3.5" />}>
                    Lihat Komposisi {cabinet.totalMembers || 34} K/L
                  </Button>
                </Link>
              </div>
            </Card>
          ))}
        </div>
      )}
    </div>
  );
}
