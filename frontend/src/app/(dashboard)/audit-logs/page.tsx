'use client';

import React, { useState, useEffect } from 'react';
import { 
  History, 
  Search, 
  Filter, 
  ShieldCheck, 
  Terminal, 
  Eye, 
  Download, 
  UserCheck, 
  Clock 
} from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Modal } from '@/components/ui/Modal';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { Tabs } from '@/components/ui/Tabs';
import { AuditService } from '@/services/api/audit.service';
import { AuditLogEntry } from '@/types/audit';
import { formatDateTimeIndonesian } from '@/lib/utils';

export default function AuditLogsPage() {
  const [logs, setLogs] = useState<AuditLogEntry[]>([]);
  const [selectedAction, setSelectedAction] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedLogForDetail, setSelectedLogForDetail] = useState<AuditLogEntry | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const list = await AuditService.getAuditLogs();
      setLogs(list);
      setIsLoading(false);
    }
    load();
  }, []);

  const filteredLogs = logs.filter((log) => {
    const matchesAction = selectedAction === 'ALL' || log.action === selectedAction;
    const matchesSearch =
      log.actorName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      log.description.toLowerCase().includes(searchQuery.toLowerCase()) ||
      log.entity.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (log.institutionName && log.institutionName.toLowerCase().includes(searchQuery.toLowerCase()));
    return matchesAction && matchesSearch;
  });

  const getActionBadge = (action: string) => {
    switch (action) {
      case 'CREATE':
        return <Badge variant="success">CREATE</Badge>;
      case 'UPDATE':
        return <Badge variant="info">UPDATE</Badge>;
      case 'SUBMIT':
        return <Badge variant="gold">SUBMIT</Badge>;
      case 'VERIFY':
        return <Badge variant="primary">VERIFY</Badge>;
      case 'APPROVE':
        return <Badge variant="success">APPROVE</Badge>;
      case 'DELETE':
        return <Badge variant="danger">DELETE</Badge>;
      default:
        return <Badge variant="default">{action}</Badge>;
    }
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title="Audit Trail & Rekam Forensik Data"
        subtitle="Log audit tak-terhapuskan (immutable audit log) yang merekam seluruh mutasi master data, telaah verifikator, dan pengesahan kementerian."
        breadcrumbs={[
          { label: 'Intelijensi & Audit' },
          { label: 'Audit Trail Forensik' },
        ]}
        actions={
          <Button variant="outline" size="sm" leftIcon={<Download className="w-3.5 h-3.5" />}>
            Ekspor Log Audit (.CSV)
          </Button>
        }
      />

      <Card>
        {/* TABS BY ACTION */}
        <div className="px-5 pt-3 border-b border-slate-100">
          <Tabs
            tabs={[
              { id: 'ALL', label: 'Semua Aktivitas', count: logs.length },
              { id: 'SUBMIT', label: 'Pengajuan', count: logs.filter((l) => l.action === 'SUBMIT').length },
              { id: 'VERIFY', label: 'Telaah Verifikasi', count: logs.filter((l) => l.action === 'VERIFY').length },
              { id: 'APPROVE', label: 'Pengesahan Master', count: logs.filter((l) => l.action === 'APPROVE').length },
              { id: 'CREATE', label: 'Penambahan Data', count: logs.filter((l) => l.action === 'CREATE').length },
            ]}
            activeTab={selectedAction}
            onChange={setSelectedAction}
          />
        </div>

        {/* SEARCH BAR */}
        <CardContent className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-100">
          <div className="relative w-full sm:w-80">
            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari aktor, entitas, deskripsi, atau IP..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
            />
          </div>
          <span className="text-xs text-slate-500 font-medium">
            Menampilkan {filteredLogs.length} Catatan Forensik
          </span>
        </CardContent>

        {/* TABLE */}
        <CardContent className="p-0">
          {isLoading ? (
            <Spinner />
          ) : filteredLogs.length === 0 ? (
            <EmptyState title="Tidak Ada Log Ditemukan" />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12 text-center">No</TableHead>
                  <TableHead className="w-44">Waktu (WIB)</TableHead>
                  <TableHead>Aktor & Peran</TableHead>
                  <TableHead>Aksi</TableHead>
                  <TableHead>Entitas Target</TableHead>
                  <TableHead>Deskripsi Aktivitas</TableHead>
                  <TableHead>Alamat IP</TableHead>
                  <TableHead className="text-right">Snapshot</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredLogs.map((log, idx) => (
                  <TableRow key={log.id}>
                    <TableCell className="text-center font-mono text-xs text-slate-500">{idx + 1}</TableCell>
                    <TableCell className="text-xs text-slate-600 font-mono">
                      {formatDateTimeIndonesian(log.timestamp)}
                    </TableCell>
                    <TableCell>
                      <div>
                        <span className="font-semibold text-xs text-slate-900 block">{log.actorName}</span>
                        <span className="text-[10px] text-slate-500">{log.actorRole} • {log.institutionName || 'KemenPANRB'}</span>
                      </div>
                    </TableCell>
                    <TableCell>{getActionBadge(log.action)}</TableCell>
                    <TableCell>
                      <span className="font-mono text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded border">
                        {log.entity}
                      </span>
                    </TableCell>
                    <TableCell className="text-xs text-slate-700 max-w-xs truncate">
                      {log.description}
                    </TableCell>
                    <TableCell className="font-mono text-xs text-slate-500">{log.ipAddress}</TableCell>
                    <TableCell className="text-right">
                      <Button
                        variant="ghost"
                        size="sm"
                        leftIcon={<Eye className="w-3.5 h-3.5" />}
                        onClick={() => setSelectedLogForDetail(log)}
                      >
                        Lihat JSON
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* JSON SNAPSHOT MODAL */}
      <Modal
        isOpen={!!selectedLogForDetail}
        onClose={() => setSelectedLogForDetail(null)}
        title={`Snapshot Mutasi Data: ${selectedLogForDetail?.id}`}
        description={`${selectedLogForDetail?.entity} • Aksi: ${selectedLogForDetail?.action} oleh ${selectedLogForDetail?.actorName}`}
        size="lg"
        footer={
          <Button variant="secondary" size="sm" onClick={() => setSelectedLogForDetail(null)}>
            Tutup Snapshot
          </Button>
        }
      >
        {selectedLogForDetail && (
          <div className="space-y-4 text-xs">
            <div className="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-1">
              <p className="font-bold text-slate-800">{selectedLogForDetail.description}</p>
              <p className="text-[11px] text-slate-500 font-mono">
                Waktu: {selectedLogForDetail.timestamp} | IP: {selectedLogForDetail.ipAddress} | Agen: {selectedLogForDetail.userAgent || '-'}
              </p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <span className="font-bold text-slate-600 block mb-1 uppercase text-[11px]">Nilai Sebelum Mutasi (Old Values):</span>
                <pre className="p-3 bg-slate-900 text-slate-200 rounded-lg text-xs font-mono overflow-x-auto min-h-[120px]">
                  {JSON.stringify(selectedLogForDetail.oldValues, null, 2) || 'null (Entitas Baru)'}
                </pre>
              </div>

              <div>
                <span className="font-bold text-emerald-800 block mb-1 uppercase text-[11px]">Nilai Sesudah Mutasi (New Values):</span>
                <pre className="p-3 bg-slate-900 text-emerald-300 rounded-lg text-xs font-mono overflow-x-auto min-h-[120px]">
                  {JSON.stringify(selectedLogForDetail.newValues, null, 2) || 'null'}
                </pre>
              </div>
            </div>
          </div>
        )}
      </Modal>
    </div>
  );
}
