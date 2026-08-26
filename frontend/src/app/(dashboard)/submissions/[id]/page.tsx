'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { 
  Inbox, 
  ArrowLeft, 
  FileText, 
  Download, 
  FileEdit, 
  CheckCircle2, 
  AlertCircle, 
  Clock, 
  ShieldCheck, 
  ExternalLink,
  Split,
  MessageSquare
} from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { WorkflowStatusBadge } from '@/components/ui/StatusBadge';
import { WorkflowStepper } from '@/components/ui/WorkflowStepper';
import { DiffViewer } from '@/components/ui/DiffViewer';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { SubmissionService } from '@/services/api/submission.service';
import { SubmissionTicket } from '@/types/submission';
import { useRole } from '@/context/RoleContext';
import { canPerformWorkflowAction } from '@/config/workflow.config';
import { formatDateTimeIndonesian } from '@/lib/utils';

export default function SubmissionDetailPage() {
  const params = useParams();
  const subId = (params?.id as string) || 'sub-001';
  const { currentRole, currentUser } = useRole();

  const [submission, setSubmission] = useState<SubmissionTicket | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isApproving, setIsApproving] = useState(false);
  const [approvedSuccess, setApprovedSuccess] = useState(false);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const data = await SubmissionService.getSubmissionById(subId);
      setSubmission(data);
      setIsLoading(false);
    }
    load();
  }, [subId]);

  const handleAdminApprove = async () => {
    setIsApproving(true);
    const updated = await SubmissionService.updateStatus(subId, 'APPROVED');
    setIsApproving(false);
    if (updated) {
      setSubmission(updated);
      setApprovedSuccess(true);
    }
  };

  if (isLoading) return <Spinner />;
  if (!submission) return <EmptyState title="Tiket Pengajuan Tidak Ditemukan" />;

  const canVerify = (currentRole === 'VERIFIKATOR' || currentRole === 'ADMIN') && (submission.status === 'SUBMITTED' || submission.status === 'IN_REVIEW');
  const canRespondRevision = (currentRole === 'USER' || currentRole === 'ADMIN') && submission.status === 'REVISION_REQUIRED';
  const canApproveToMaster = canPerformWorkflowAction(submission.status, 'APPROVED', currentRole);

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title={`Tiket: ${submission.ticketNumber}`}
        subtitle={`${submission.institutionName} • Pengusul: ${submission.submitterName}`}
        breadcrumbs={[
          { label: 'Pengajuan', href: '/submissions' },
          { label: submission.ticketNumber },
        ]}
        badge={<WorkflowStatusBadge status={submission.status} />}
        actions={
          <div className="flex items-center gap-2.5">
            <Link href="/submissions">
              <Button variant="outline" size="sm" leftIcon={<ArrowLeft className="w-3.5 h-3.5" />}>
                Kembali
              </Button>
            </Link>

            {/* ACTION FOR VERIFIKATOR */}
            {canVerify && (
              <Link href={`/verifications/${submission.id}`}>
                <Button variant="primary" size="sm" leftIcon={<ShieldCheck className="w-3.5 h-3.5" />}>
                  Buka Ruang Telaah Verifikasi
                </Button>
              </Link>
            )}

            {/* ACTION FOR OPERATOR RESPONDING TO REVISION */}
            {canRespondRevision && (
              <Link href={`/submissions/${submission.id}/revision`}>
                <Button variant="gold" size="sm" leftIcon={<FileEdit className="w-3.5 h-3.5" />}>
                  Tanggapi Catatan Revisi
                </Button>
              </Link>
            )}

            {/* ACTION FOR ADMIN FINAL APPROVAL */}
            {canApproveToMaster && (
              <Button
                variant="primary"
                size="sm"
                isLoading={isApproving}
                onClick={handleAdminApprove}
                leftIcon={<CheckCircle2 className="w-3.5 h-3.5" />}
              >
                Sahkan ke Master Data
              </Button>
            )}
          </div>
        }
      />

      {approvedSuccess && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
          <div>
            <p className="text-xs font-bold">Usulan Berhasil Disahkan!</p>
            <p className="text-[11px] text-emerald-700">
              Perubahan struktur dan data profil telah otomatis diterapkan secara atomik ke Master Data SIGMA-K dan dicatat pada Audit Trail.
            </p>
          </div>
        </div>
      )}

      {/* VISUAL WORKFLOW STEPPER */}
      <Card>
        <CardContent className="p-6">
          <WorkflowStepper currentStatus={submission.status} />
        </CardContent>
      </Card>

      {/* 2-COLUMN DETAILS */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left 2 Cols: Proposed Delta Changes */}
        <div className="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <div>
                <CardTitle className="text-base font-bold">Rincian Usulan Perubahan ({submission.items?.length || 0} Butir)</CardTitle>
                <p className="text-xs text-slate-500 mt-0.5">Komparasi data sebelum vs usulan perubahan draf</p>
              </div>
            </CardHeader>

            <CardContent className="p-5 space-y-4">
              <div className="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-xs space-y-1">
                <span className="font-bold text-slate-700 block">Penjelasan Urgensi dari Pengusul:</span>
                <p className="text-slate-600 leading-relaxed">{submission.submissionNotes}</p>
              </div>

              {submission.items?.map((item, idx) => (
                <DiffViewer
                  key={item.id}
                  title={`${idx + 1}. ${item.label}`}
                  transitionType={item.actionType === 'CREATE' ? 'NEW' : 'RENAME'}
                  beforeLabel="Data Lama"
                  afterLabel="Data Baru / Diusulkan"
                  beforeContent={
                    item.payloadBefore ? (
                      <pre className="text-[11px] font-mono text-slate-700 whitespace-pre-wrap">
                        {JSON.stringify(item.payloadBefore, null, 2)}
                      </pre>
                    ) : null
                  }
                  afterContent={
                    <pre className="text-[11px] font-mono text-primary-950 whitespace-pre-wrap font-medium">
                      {JSON.stringify(item.payloadAfter, null, 2)}
                    </pre>
                  }
                />
              ))}
            </CardContent>
          </Card>
        </div>

        {/* Right 1 Col: Attachments & Verification Timeline */}
        <div className="space-y-6">
          {/* Legal PDF Attachment */}
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-bold flex items-center gap-1.5">
                <FileText className="w-4 h-4 text-primary-800" />
                Lampiran Dasar Hukum
              </CardTitle>
            </CardHeader>
            <CardContent className="p-4 space-y-3 text-xs">
              <div className="p-3 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div className="flex items-center gap-2.5 min-w-0">
                  <span className="w-8 h-8 rounded bg-red-100 text-red-700 font-bold flex items-center justify-center text-[10px] shrink-0">
                    PDF
                  </span>
                  <div className="truncate">
                    <p className="font-semibold text-slate-800 truncate">{submission.legalDocName || 'Dasar_Hukum_Perpres.pdf'}</p>
                    <p className="text-[10px] text-slate-400">Ukuran: 2.4 MB • Terverifikasi</p>
                  </div>
                </div>
                <Button variant="outline" size="sm" leftIcon={<Download className="w-3 h-3" />}>
                  Unduh
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Verification Logs Timeline */}
          <Card>
            <CardHeader>
              <CardTitle className="text-sm font-bold flex items-center gap-1.5">
                <MessageSquare className="w-4 h-4 text-primary-800" />
                Catatan & Riwayat Telaah
              </CardTitle>
            </CardHeader>
            <CardContent className="p-4">
              {!submission.verificationLogs || submission.verificationLogs.length === 0 ? (
                <p className="text-xs text-slate-400 italic">Belum ada catatan telaah verifikator.</p>
              ) : (
                <div className="space-y-3">
                  {submission.verificationLogs.map((log) => (
                    <div key={log.id} className="p-3 rounded-lg bg-slate-50 border border-slate-200 space-y-1.5 text-xs">
                      <div className="flex items-center justify-between">
                        <span className="font-bold text-slate-800">{log.verifierName}</span>
                        <Badge variant={log.decision === 'PASS' ? 'success' : 'warning'}>
                          {log.decision === 'PASS' ? 'Lolos' : 'Revisi'}
                        </Badge>
                      </div>
                      <p className="text-slate-600 leading-relaxed">{log.notes}</p>
                      <span className="text-[10px] text-slate-400 block pt-1">
                        {formatDateTimeIndonesian(log.verifiedAt)}
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
