'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { FileCheck2, Search, Filter, ShieldCheck, ExternalLink, Clock, AlertTriangle, ArrowRight } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { WorkflowStatusBadge } from '@/components/ui/StatusBadge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { SubmissionService } from '@/services/api/submission.service';
import { SubmissionTicket } from '@/types/submission';
import { formatDateTimeIndonesian } from '@/lib/utils';

export default function VerificationQueuePage() {
  const [submissions, setSubmissions] = useState<SubmissionTicket[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const list = await SubmissionService.getSubmissions();
      // Filter those needing verification (SUBMITTED, IN_REVIEW, VERIFIED)
      setSubmissions(list);
      setIsLoading(false);
    }
    load();
  }, []);

  const queueItems = submissions.filter((s) => s.status === 'SUBMITTED' || s.status === 'IN_REVIEW' || s.status === 'REVISION_REQUIRED');

  const filteredQueue = queueItems.filter((s) =>
    s.ticketNumber.toLowerCase().includes(searchQuery.toLowerCase()) ||
    s.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
    s.institutionName.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title="Antrean Telaah & Verifikasi Kelembagaan"
        subtitle="Ruang kerja Verifikator Analis Kelembagaan KemenPANRB untuk meneliti keabsahan usulan struktur organisasi, tupoksi, dan kelengkapan dokumen regulasi."
        breadcrumbs={[
          { label: 'Tata Kelola & Workflow' },
          { label: 'Antrean Verifikasi' },
        ]}
      />

      {/* SUMMARY BANNER */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card className="p-4 bg-amber-500/10 border-amber-300">
          <span className="text-[10px] text-amber-800 font-bold uppercase">Perlu Ditelaah Segera</span>
          <h3 className="text-2xl font-bold text-amber-900 font-heading mt-1">{queueItems.length} Berkas</h3>
          <p className="text-[11px] text-amber-700 mt-0.5">Prioritas telaah usulan K/L Kabinet Merah Putih</p>
        </Card>
        <Card className="p-4 bg-emerald-50 border-emerald-200">
          <span className="text-[10px] text-emerald-800 font-bold uppercase">Lolos Verifikasi Pekan Ini</span>
          <h3 className="text-2xl font-bold text-emerald-900 font-heading mt-1">
            {submissions.filter((s) => s.status === 'VERIFIED').length} Berkas
          </h3>
          <p className="text-[11px] text-emerald-700 mt-0.5">Menunggu pengesahan Admin</p>
        </Card>
        <Card className="p-4 bg-slate-50 border-slate-200">
          <span className="text-[10px] text-slate-500 font-bold uppercase">Target SLA Penyelesaian</span>
          <h3 className="text-2xl font-bold text-primary-900 font-heading mt-1">&le; 3 Hari Kerja</h3>
          <p className="text-[11px] text-slate-500 mt-0.5">Standar Pelayanan Prima SPBE</p>
        </Card>
      </div>

      {/* QUEUE TABLE */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between w-full">
            <div>
              <CardTitle className="text-base font-bold flex items-center gap-2">
                <ShieldCheck className="w-5 h-5 text-primary-800" />
                Antrean Berkas Pengajuan Masuk
              </CardTitle>
            </div>
            <div className="relative w-72">
              <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
              <input
                type="text"
                placeholder="Cari tiket, instansi, judul..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full h-8 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
              />
            </div>
          </div>
        </CardHeader>

        <CardContent className="p-0">
          {isLoading ? (
            <Spinner />
          ) : filteredQueue.length === 0 ? (
            <EmptyState title="Antrean Kosong" description="Semua berkas pengajuan telah selesai ditelaah." />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12 text-center">No</TableHead>
                  <TableHead className="w-32">Nomor Tiket</TableHead>
                  <TableHead>Kementerian / Instansi</TableHead>
                  <TableHead>Judul Usulan</TableHead>
                  <TableHead>Prioritas</TableHead>
                  <TableHead>Tanggal Masuk</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Aksi Telaah</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredQueue.map((item, idx) => (
                  <TableRow key={item.id}>
                    <TableCell className="text-center font-mono text-xs">{idx + 1}</TableCell>
                    <TableCell>
                      <span className="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border">
                        {item.ticketNumber}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className="font-bold text-xs text-slate-800 block">{item.institutionName}</span>
                      <span className="text-[11px] text-slate-500">{item.institutionCode}</span>
                    </TableCell>
                    <TableCell>
                      <span className="text-xs font-medium text-slate-900 line-clamp-1">{item.title}</span>
                      <span className="text-[11px] text-slate-500">{item.itemsCount} Butir Usulan</span>
                    </TableCell>
                    <TableCell>
                      <Badge variant={item.priority === 'HIGH' ? 'danger' : 'primary'}>
                        {item.priority || 'NORMAL'}
                      </Badge>
                    </TableCell>
                    <TableCell className="text-xs text-slate-600">
                      {formatDateTimeIndonesian(item.submittedAt)}
                    </TableCell>
                    <TableCell>
                      <WorkflowStatusBadge status={item.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      <Link href={`/verifications/${item.id}`}>
                        <Button variant="primary" size="sm" rightIcon={<ArrowRight className="w-3.5 h-3.5" />}>
                          Buka Telaah
                        </Button>
                      </Link>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
