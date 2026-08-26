'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { BookOpen, Search, Filter, Plus, FileText, Building2, CheckCircle2 } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Tabs } from '@/components/ui/Tabs';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { Modal } from '@/components/ui/Modal';
import { Input, Select } from '@/components/ui/Input';
import { OrganizationService } from '@/services/api/organization.service';
import { TupoksiItem } from '@/types/organization';
import { useRole } from '@/context/RoleContext';

export default function TupoksiMasterPage() {
  const { currentRole } = useRole();
  const [tupoksiList, setTupoksiList] = useState<TupoksiItem[]>([]);
  const [selectedType, setSelectedType] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const list = await OrganizationService.getAllTupoksi();
      setTupoksiList(list);
      setIsLoading(false);
    }
    load();
  }, []);

  const filteredList = tupoksiList.filter((t) => {
    const matchesType = selectedType === 'ALL' || t.type === selectedType;
    const matchesSearch =
      t.contentText.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (t.legalArticleReference && t.legalArticleReference.toLowerCase().includes(searchQuery.toLowerCase())) ||
      (t.organizationUnitName && t.organizationUnitName.toLowerCase().includes(searchQuery.toLowerCase()));
    return matchesType && matchesSearch;
  });

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title="Katalog Tugas dan Fungsi Kelembagaan"
        subtitle="Daftar butir mandat tugas pokok dan rincian fungsi seluruh unit kerja instansi pemerintah berdasar hukum regulasi."
        breadcrumbs={[
          { label: 'Kelembagaan' },
          { label: 'Tugas dan Fungsi' },
        ]}
        actions={
          (currentRole === 'USER' || currentRole === 'ADMIN') && (
            <Button variant="primary" size="sm" leftIcon={<Plus className="w-3.5 h-3.5" />} onClick={() => setIsModalOpen(true)}>
              Usulkan Butir Tupoksi
            </Button>
          )
        }
      />

      <Card>
        <div className="px-5 pt-3 border-b border-slate-100">
          <Tabs
            tabs={[
              { id: 'ALL', label: 'Semua Butir Mandat', count: tupoksiList.length },
              { id: 'DUTY', label: 'Tugas Pokok', count: tupoksiList.filter((t) => t.type === 'DUTY').length },
              { id: 'FUNCTION', label: 'Rincian Fungsi', count: tupoksiList.filter((t) => t.type === 'FUNCTION').length },
            ]}
            activeTab={selectedType}
            onChange={setSelectedType}
          />
        </div>

        <CardContent className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-100">
          <div className="relative w-full sm:w-80">
            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari butir tugas, fungsi, atau rujukan pasal..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
            />
          </div>
          <span className="text-xs text-slate-500">
            Menampilkan {filteredList.length} Butir Tupoksi
          </span>
        </CardContent>

        <CardContent className="p-0">
          {isLoading ? (
            <Spinner />
          ) : filteredList.length === 0 ? (
            <EmptyState title="Tupoksi Tidak Ditemukan" />
          ) : (
            <div className="divide-y divide-slate-100">
              {filteredList.map((item, idx) => (
                <div key={item.id} className="p-5 hover:bg-slate-50/70 transition-colors space-y-2 text-xs">
                  <div className="flex flex-wrap items-center justify-between gap-2">
                    <div className="flex items-center gap-2">
                      <span className="font-mono text-slate-400 font-bold">#{idx + 1}</span>
                      <Badge variant={item.type === 'DUTY' ? 'gold' : 'info'}>
                        {item.type === 'DUTY' ? 'Tugas Pokok' : 'Rincian Fungsi'}
                      </Badge>
                      <span className="font-semibold text-slate-800">
                        {item.organizationUnitName || 'Unit Pimpinan'}
                      </span>
                    </div>

                    {item.legalArticleReference && (
                      <span className="text-[11px] font-mono text-primary-900 bg-primary-50 px-2.5 py-0.5 rounded border border-primary-200 font-medium">
                        {item.legalArticleReference}
                      </span>
                    )}
                  </div>

                  <p className="text-slate-800 font-medium leading-relaxed pl-6 border-l-2 border-primary-800/40">
                    {item.contentText}
                  </p>

                  <div className="flex items-center justify-between text-[10px] text-slate-400 pt-1">
                    <span>Versi Regulasi: {item.version}</span>
                    <span>Terakhir Disahkan: 2024-11-05</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* MODAL USULAN TUPOKSI */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Usulan Butir Tugas & Fungsi Baru"
        description="Pengajuan butir mandat tugas atau rincian fungsi baru berbasis regulasi dasar hukum."
        footer={
          <>
            <Button variant="ghost" size="sm" onClick={() => setIsModalOpen(false)}>
              Batal
            </Button>
            <Button variant="primary" size="sm" onClick={() => setIsModalOpen(false)}>
              Kirim ke Draf Usulan
            </Button>
          </>
        }
      >
        <div className="space-y-4 text-xs">
          <Select
            label="Jenis Butir Pernyataan"
            options={[
              { value: 'DUTY', label: 'Tugas Pokok (Duty)' },
              { value: 'FUNCTION', label: 'Rincian Fungsi (Function)' },
            ]}
          />
          <Input label="Unit Organisasi Pengampu" placeholder="Pilih unit kerja struktural..." />
          <Input label="Rujukan Pasal & Ayat Regulasi" placeholder="Contoh: Perpres No. 147/2024 Pasal 5 ayat (2)" />
          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Redaksi Teks Mandat Resmi *
            </label>
            <textarea
              rows={4}
              placeholder="Tuliskan redaksi kalimat tugas/fungsi secara lengkap..."
              className="w-full p-3 text-xs bg-white border border-slate-300 rounded-md focus:ring-2 focus:ring-primary-800 focus:outline-none"
            />
          </div>
        </div>
      </Modal>
    </div>
  );
}
