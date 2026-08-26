'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { Inbox, Plus, Search, Filter, ExternalLink, FileText, Upload, CheckCircle2 } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { WorkflowStatusBadge } from '@/components/ui/StatusBadge';
import { Tabs } from '@/components/ui/Tabs';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { Modal } from '@/components/ui/Modal';
import { Input, Select } from '@/components/ui/Input';
import { SubmissionService } from '@/services/api/submission.service';
import { SubmissionTicket, WorkflowStatus } from '@/types/submission';
import { useRole } from '@/context/RoleContext';
import { formatDateTimeIndonesian } from '@/lib/utils';

export default function SubmissionsPage() {
  const { currentRole, currentUser } = useRole();
  const [submissions, setSubmissions] = useState<SubmissionTicket[]>([]);
  const [selectedStatus, setSelectedStatus] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isModalOpen, setIsModalOpen] = useState(false);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const list = await SubmissionService.getSubmissions();
      setSubmissions(list);
      setIsLoading(false);
    }
    load();
  }, []);

  const filteredSubmissions = submissions.filter((sub) => {
    const matchesStatus = selectedStatus === 'ALL' || sub.status === selectedStatus;
    const matchesSearch =
      sub.ticketNumber.toLowerCase().includes(searchQuery.toLowerCase()) ||
      sub.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      sub.institutionName.toLowerCase().includes(searchQuery.toLowerCase());
    return matchesStatus && matchesSearch;
  });

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title={currentRole === 'USER' ? 'Pengajuan Saya' : 'Daftar Pengajuan Usulan Perubahan'}
        subtitle="Kelola berkas tiket usulan penataan struktur organisasi, pembaruan tugas fungsi, dan data profil kementerian."
        breadcrumbs={[
          { label: 'Tata Kelola & Workflow' },
          { label: 'Pengajuan Usulan' },
        ]}
        actions={
          (currentRole === 'USER' || currentRole === 'ADMIN') && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<Plus className="w-3.5 h-3.5" />}
              onClick={() => setIsModalOpen(true)}
            >
              Buat Pengajuan Baru
            </Button>
          )
        }
      />

      <Card>
        {/* TABS BY STATUS */}
        <div className="px-5 pt-3 border-b border-slate-100">
          <Tabs
            tabs={[
              { id: 'ALL', label: 'Semua Tiket', count: submissions.length },
              { id: 'SUBMITTED', label: 'Terkirim', count: submissions.filter((s) => s.status === 'SUBMITTED').length },
              { id: 'IN_REVIEW', label: 'Sedang Ditelaah', count: submissions.filter((s) => s.status === 'IN_REVIEW').length },
              { id: 'VERIFIED', label: 'Lolos Verifikasi', count: submissions.filter((s) => s.status === 'VERIFIED').length },
              { id: 'REVISION_REQUIRED', label: 'Perlu Revisi', count: submissions.filter((s) => s.status === 'REVISION_REQUIRED').length },
              { id: 'APPROVED', label: 'Disahkan', count: submissions.filter((s) => s.status === 'APPROVED').length },
            ]}
            activeTab={selectedStatus}
            onChange={setSelectedStatus}
          />
        </div>

        {/* SEARCH & STATS */}
        <CardContent className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4 border-b border-slate-100">
          <div className="relative w-full sm:w-80">
            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
            <input
              type="text"
              placeholder="Cari nomor tiket, judul, instansi..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
            />
          </div>
          <span className="text-xs text-slate-500 font-medium">
            Menampilkan {filteredSubmissions.length} Berkas Pengajuan
          </span>
        </CardContent>

        {/* TABLE */}
        <CardContent className="p-0">
          {isLoading ? (
            <Spinner />
          ) : filteredSubmissions.length === 0 ? (
            <EmptyState title="Tidak Ada Pengajuan Ditemukan" description="Belum ada tiket pengajuan dengan status ini." />
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="w-12 text-center">No</TableHead>
                  <TableHead className="w-36">Nomor Tiket</TableHead>
                  <TableHead>Kementerian / Instansi</TableHead>
                  <TableHead>Judul Usulan Perubahan</TableHead>
                  <TableHead>Tanggal Kirim</TableHead>
                  <TableHead>Status Workflow</TableHead>
                  <TableHead className="text-right">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredSubmissions.map((sub, idx) => (
                  <TableRow key={sub.id}>
                    <TableCell className="text-center font-mono text-xs text-slate-500">{idx + 1}</TableCell>
                    <TableCell>
                      <span className="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 block">
                        {sub.ticketNumber}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span className="font-semibold text-xs text-slate-800 block">{sub.institutionName}</span>
                      <span className="text-[11px] text-slate-500">{sub.institutionCode}</span>
                    </TableCell>
                    <TableCell>
                      <span className="font-medium text-xs text-slate-900 line-clamp-1">{sub.title}</span>
                      <span className="text-[11px] text-slate-500">{sub.itemsCount} Butir Usulan Perubahan</span>
                    </TableCell>
                    <TableCell className="text-xs text-slate-600">
                      {formatDateTimeIndonesian(sub.submittedAt)}
                    </TableCell>
                    <TableCell>
                      <WorkflowStatusBadge status={sub.status} />
                    </TableCell>
                    <TableCell className="text-right">
                      <Link href={`/submissions/${sub.id}`}>
                        <Button variant="outline" size="sm" rightIcon={<ExternalLink className="w-3.5 h-3.5" />}>
                          Buka
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

      {/* MODAL BUAT PENGAJUAN BARU */}
      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title="Buat Pengajuan Usulan Perubahan Data"
        description="Lengkapi formulir usulan penataan kelembagaan untuk diteruskan ke tim Verifikator KemenPANRB."
        footer={
          <>
            <Button variant="ghost" size="sm" onClick={() => setIsModalOpen(false)}>
              Batal
            </Button>
            <Button
              variant="primary"
              size="sm"
              onClick={() => {
                setIsModalOpen(false);
              }}
            >
              Simpan & Kirim Berkas
            </Button>
          </>
        }
      >
        <div className="space-y-4 text-xs">
          <Input
            label="Kementerian / Lembaga Pengusul"
            value={currentUser.institutionName || 'Kementerian Koordinator Bidang Pangan'}
            disabled
          />

          <Select
            label="Jenis Usulan Perubahan"
            options={[
              { value: 'STRUKTUR_ORGANISASI', label: 'Penataan / Pembentukan Struktur Organisasi Baru' },
              { value: 'TUGAS_FUNGSI', label: 'Pembaruan Butir Tugas dan Fungsi' },
              { value: 'PROFIL_INSTANSI', label: 'Pembaruan Profil, Kontak, dan Portal Lembaga' },
            ]}
          />

          <Input
            label="Judul Ringkas Pengajuan *"
            placeholder="Contoh: Penetapan Struktur Biro Perencanaan Baru"
            required
          />

          <div>
            <label className="block text-xs font-semibold text-slate-700 mb-1">
              Catatan Penjelasan Urgensi Perubahan *
            </label>
            <textarea
              rows={3}
              placeholder="Tuliskan latar belakang dan urgensi perubahan..."
              className="w-full p-3 text-xs bg-white border border-slate-300 rounded-md focus:ring-2 focus:ring-primary-800 focus:outline-none"
            />
          </div>

          {/* Upload PDF Box */}
          <div className="p-4 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-center">
            <Upload className="w-6 h-6 text-slate-400 mx-auto mb-1.5" />
            <p className="font-semibold text-slate-700">Unggah Salinan PDF Dasar Hukum Regulasi</p>
            <p className="text-[11px] text-slate-500 mt-0.5">Perpres / Permen / SK Menteri (Maksimal 10 MB)</p>
            <div className="mt-2">
              <Button type="button" variant="outline" size="sm">
                Pilih Berkas PDF
              </Button>
            </div>
          </div>
        </div>
      </Modal>
    </div>
  );
}
