'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { Landmark, Users, ArrowLeft, Building2, Calendar, GitCompare, ExternalLink, Search } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { ActiveStatusBadge } from '@/components/ui/StatusBadge';
import { Tabs } from '@/components/ui/Tabs';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { CabinetService } from '@/services/api/cabinet.service';
import { Cabinet, CabinetMembership } from '@/types/cabinet';
import { formatDateIndonesian } from '@/lib/utils';

export default function CabinetDetailPage() {
  const params = useParams();
  const cabinetId = (params?.id as string) || 'cab-merah-putih';

  const [cabinet, setCabinet] = useState<Cabinet | null>(null);
  const [memberships, setMemberships] = useState<CabinetMembership[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const [cab, members] = await Promise.all([
        CabinetService.getCabinetById(cabinetId),
        CabinetService.getCabinets(), // or get memberships
      ]);
      const memberList = await CabinetService.getCabinetMemberships();
      setCabinet(cab || {
        id: 'cab-merah-putih',
        name: 'Kabinet Merah Putih',
        presidentName: 'H. Prabowo Subianto',
        vicePresidentName: 'Gibran Rakabuming Raka',
        description: 'Kabinet pemerintahan Republik Indonesia periode 2024–2029 yang dibentuk berdasarkan Keputusan Presiden Nomor 133/P Tahun 2024.',
        isActive: true,
        totalMembers: 48,
        createdAt: '2024-10-21T00:00:00Z',
      });
      setMemberships(memberList);
      setIsLoading(false);
    }
    load();
  }, [cabinetId]);

  if (isLoading) return <Spinner />;
  if (!cabinet) return <EmptyState title="Kabinet Tidak Ditemukan" />;

  const filteredMembers = memberships.filter((m) => {
    const matchesCategory = selectedCategory === 'ALL' || m.category === selectedCategory;
    const matchesSearch =
      m.institutionName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      m.institutionCode.toLowerCase().includes(searchQuery.toLowerCase()) ||
      m.institutionShortName.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesCategory && matchesSearch;
  });

  const kemenkoCount = memberships.filter((m) => m.category === 'KEMENKO').length;
  const teknisCount = memberships.filter((m) => m.category === 'TEKNIS').length;

  return (
    <div className="space-y-6">
      <PageHeader
        title={cabinet.name}
        subtitle={`Presiden: ${cabinet.presidentName} • Wakil Presiden: ${cabinet.vicePresidentName || '-'}`}
        breadcrumbs={[
          { label: 'Master Data', href: '/cabinets' },
          { label: 'Kabinet', href: '/cabinets' },
          { label: cabinet.name },
        ]}
        badge={<ActiveStatusBadge isActive={cabinet.isActive} />}
        actions={
          <div className="flex items-center gap-2.5">
            <Link href="/cabinets/compare">
              <Button variant="outline" size="sm" leftIcon={<GitCompare className="w-4 h-4" />}>
                Komparasi dengan Kabinet Sebelumnya
              </Button>
            </Link>
          </div>
        }
      />

      {/* Top Overview Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4 bg-primary-950 text-white">
          <span className="text-[10px] text-gold-300 font-bold uppercase">Total Keanggotaan</span>
          <h3 className="text-2xl font-bold font-heading mt-1">{memberships.length} Instansi K/L</h3>
          <p className="text-[11px] text-slate-300 mt-1">7 Kemenko + {memberships.length - 7} Kementerian Bidang</p>
        </Card>
        <Card className="p-4">
          <span className="text-[10px] text-slate-500 font-bold uppercase">Dasar Hukum Pembentukan</span>
          <h3 className="text-sm font-bold text-slate-900 mt-1">Keppres No. 133/P Tahun 2024</h3>
          <p className="text-[11px] text-slate-500 mt-1">Pelantikan: 21 Oktober 2024</p>
        </Card>
        <Card className="p-4">
          <span className="text-[10px] text-slate-500 font-bold uppercase">Masa Jabatan Formal</span>
          <h3 className="text-sm font-bold text-slate-900 mt-1">2024 – 2029 (5 Tahun)</h3>
          <p className="text-[11px] text-emerald-600 font-medium mt-1">Status: Sedang Aktif Berjalan</p>
        </Card>
      </div>

      {/* Memberships Table Section */}
      <Card>
        <CardHeader>
          <div>
            <CardTitle className="text-base">Daftar Kementerian & Lembaga Anggota Kabinet</CardTitle>
            <p className="text-xs text-slate-500 mt-0.5">
              Klik nama kementerian untuk membuka profil detail, struktur organisasi, dan tupoksi.
            </p>
          </div>
        </CardHeader>

        <div className="px-5 pt-3">
          <Tabs
            tabs={[
              { id: 'ALL', label: 'Semua Anggota', count: memberships.length },
              { id: 'KEMENKO', label: 'Kementerian Koordinator', count: kemenkoCount },
              { id: 'TEKNIS', label: 'Kementerian Teknis Bidang', count: teknisCount },
            ]}
            activeTab={selectedCategory}
            onChange={setSelectedCategory}
          />
        </div>

        {/* Filter Input */}
        <div className="p-4 border-b border-slate-100 flex items-center justify-between">
          <div className="relative w-72">
            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari kode atau nama kementerian..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
            />
          </div>
          <span className="text-xs text-slate-500">
            Menampilkan {filteredMembers.length} Kementerian/Lembaga
          </span>
        </div>

        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-12 text-center">No</TableHead>
                <TableHead className="w-24">Kode</TableHead>
                <TableHead>Nama Kementerian / Lembaga</TableHead>
                <TableHead>Kategori</TableHead>
                <TableHead>Tanggal Bergabung</TableHead>
                <TableHead>Status</TableHead>
                <TableHead className="text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredMembers.map((member, idx) => (
                <TableRow key={member.id}>
                  <TableCell className="text-center font-mono text-xs">{idx + 1}</TableCell>
                  <TableCell>
                    <span className="font-mono text-xs font-bold text-primary-900 bg-primary-50 px-2 py-0.5 rounded border border-primary-200">
                      {member.institutionCode}
                    </span>
                  </TableCell>
                  <TableCell>
                    <div>
                      <span className="font-semibold text-slate-900 block">{member.institutionName}</span>
                      <span className="text-xs text-slate-500">{member.institutionShortName}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <Badge variant={member.category === 'KEMENKO' ? 'gold' : 'primary'}>
                      {member.category}
                    </Badge>
                  </TableCell>
                  <TableCell className="text-xs text-slate-600">
                    {formatDateIndonesian(member.joinedDate)}
                  </TableCell>
                  <TableCell>
                    <ActiveStatusBadge isActive={member.isActiveInCabinet} />
                  </TableCell>
                  <TableCell className="text-right">
                    <Link href={`/institutions/${member.institutionId}`}>
                      <Button variant="outline" size="sm" rightIcon={<ExternalLink className="w-3.5 h-3.5" />}>
                        Lihat Profil
                      </Button>
                    </Link>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
