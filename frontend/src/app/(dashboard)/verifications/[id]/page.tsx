'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useParams, useRouter } from 'next/navigation';
import { 
  FileCheck2, 
  ArrowLeft, 
  CheckCircle2, 
  AlertTriangle, 
  XCircle, 
  FileText, 
  Download, 
  Building2, 
  ShieldCheck,
  Split,
  MessageSquare
} from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { WorkflowStatusBadge } from '@/components/ui/StatusBadge';
import { Modal } from '@/components/ui/Modal';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { SubmissionService } from '@/services/api/submission.service';
import { SubmissionTicket } from '@/types/submission';
import { useRole } from '@/context/RoleContext';
import { canPerformWorkflowAction } from '@/config/workflow.config';

export default function VerificationWorkspacePage() {
  const params = useParams();
  const router = useRouter();
  const subId = (params?.id as string) || 'sub-001';
  const { currentUser, currentRole } = useRole();

  const [submission, setSubmission] = useState<SubmissionTicket | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Decision Modal States
  const [decisionType, setDecisionType] = useState<'PASS' | 'REVISION_REQUIRED' | 'REJECT' | null>(null);
  const [notes, setNotes] = useState('');
  const [isSubmittingDecision, setIsSubmittingDecision] = useState(false);
  const [actionSuccessMessage, setActionSuccessMessage] = useState('');

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const data = await SubmissionService.getSubmissionById(subId);
      setSubmission(data);
      setIsLoading(false);
    }
    load();
  }, [subId]);

  const handleConfirmDecision = async () => {
    if (!decisionType || !notes.trim()) return;
    setIsSubmittingDecision(true);

    const nextStatus = decisionType === 'PASS' ? 'VERIFIED' : decisionType === 'REVISION_REQUIRED' ? 'REVISION_REQUIRED' : 'REJECTED';
    const updated = await SubmissionService.updateStatus(subId, nextStatus, notes, currentUser.fullName);

    setIsSubmittingDecision(false);
    setDecisionType(null);

    if (updated) {
      setSubmission(updated);
      setActionSuccessMessage(
        decisionType === 'PASS'
          ? 'Telaah berhasil diselesaikan: Lolos Verifikasi dan diteruskan ke Administrator Pusat.'
          : decisionType === 'REVISION_REQUIRED'
          ? 'Catatan revisi telah dikirimkan ke Operator pengusul.'
          : 'Pengajuan usulan telah ditolak.'
      );
    }
  };

  if (isLoading) return <Spinner />;
  if (!submission) return <EmptyState title="Pengajuan Tidak Ditemukan" />;

  const canPass = canPerformWorkflowAction(submission.status, 'VERIFIED', currentRole);
  const canRequestRevision = canPerformWorkflowAction(submission.status, 'REVISION_REQUIRED', currentRole);
  const canReject = canPerformWorkflowAction(submission.status, 'REVISION_REQUIRED', currentRole);

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title={`Ruang Telaah: ${submission.ticketNumber}`}
        subtitle={`${submission.institutionName} • Pengusul: ${submission.submitterName}`}
        breadcrumbs={[
          { label: 'Verifikasi', href: '/verifications' },
          { label: 'Antrean', href: '/verifications' },
          { label: submission.ticketNumber },
        ]}
        badge={<WorkflowStatusBadge status={submission.status} />}
        actions={
          <div className="flex items-center gap-2">
            <Link href="/verifications">
              <Button variant="outline" size="sm" leftIcon={<ArrowLeft className="w-3.5 h-3.5" />}>
                Kembali ke Antrean
              </Button>
            </Link>
          </div>
        }
      />

      {actionSuccessMessage && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
          <p className="text-xs font-semibold">{actionSuccessMessage}</p>
        </div>
      )}

      {/* TOP FLOATING ACTION BAR FOR VERIFIKATOR */}
      <Card className="bg-slate-900 text-white border-slate-800 shadow-lg">
        <CardContent className="p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-gold-500/20 text-gold-300 flex items-center justify-center border border-gold-400/30">
              <ShieldCheck className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-bold">Panel Keputusan Verifikator Kelembagaan</h4>
              <p className="text-xs text-slate-400">Teliti kesesuaian lampiran dasar hukum dengan usulan perubahan</p>
            </div>
          </div>

          <div className="flex items-center gap-2.5 w-full sm:w-auto">
            {canReject && (
              <Button
                variant="danger"
                size="sm"
                leftIcon={<XCircle className="w-4 h-4" />}
                onClick={() => {
                  setDecisionType('REJECT');
                  setNotes('');
                }}
              >
                Tolak Usulan
              </Button>
            )}
            {canRequestRevision && (
              <Button
                variant="gold"
                size="sm"
                leftIcon={<AlertTriangle className="w-4 h-4" />}
                onClick={() => {
                  setDecisionType('REVISION_REQUIRED');
                  setNotes('');
                }}
              >
                Minta Revisi
              </Button>
            )}
            {canPass && (
              <Button
                variant="primary"
                size="sm"
                className="bg-emerald-600 hover:bg-emerald-700 text-white border-0"
                leftIcon={<CheckCircle2 className="w-4 h-4" />}
                onClick={() => {
                  setDecisionType('PASS');
                  setNotes('Dokumen dasar hukum dan nomenklatur unit organisasi telah diteliti sesuai kaidah penataan kementerian.');
                }}
              >
                Lolos Verifikasi (Pass)
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      {/* SIDE-BY-SIDE SPLIT REVIEW WORKSPACE */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-sm font-bold text-slate-800 flex items-center gap-2">
            <Split className="w-4 h-4 text-primary-800" />
            Komparasi Berdampingan (*Side-by-Side Verification Panels*)
          </h3>
          <span className="text-xs text-slate-500">Total {submission.items?.length || 0} Butir Usulan</span>
        </div>

        {submission.items?.map((item, idx) => (
          <Card key={item.id} className="overflow-hidden shadow-2xs">
            <CardHeader className="bg-slate-50 py-3">
              <div className="flex items-center gap-2">
                <span className="font-mono text-xs font-bold text-slate-500">#{idx + 1}</span>
                <CardTitle className="text-sm font-bold">{item.label}</CardTitle>
              </div>
              <Badge variant={item.actionType === 'CREATE' ? 'success' : 'primary'}>
                {item.actionType}
              </Badge>
            </CardHeader>

            <div className="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200">
              {/* LEFT: EXISTING LIVE MASTER DATA */}
              <div className="p-4 bg-slate-50/50">
                <div className="flex items-center justify-between text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">
                  <span>Data Master Saat Ini (Eksisting)</span>
                  <span className="text-slate-400 font-mono">LIVE</span>
                </div>
                {item.payloadBefore ? (
                  <pre className="p-3 bg-white rounded-lg border border-slate-200 text-xs font-mono text-slate-700 whitespace-pre-wrap">
                    {JSON.stringify(item.payloadBefore, null, 2)}
                  </pre>
                ) : (
                  <div className="p-4 bg-slate-100/60 rounded-lg border border-dashed border-slate-200 text-center text-xs text-slate-400 italic">
                    Tidak ada record master sebelumnya (Entitas Baru).
                  </div>
                )}
              </div>

              {/* RIGHT: PROPOSED DRAFT DATA */}
              <div className="p-4 bg-emerald-50/20">
                <div className="flex items-center justify-between text-[11px] font-bold text-emerald-800 uppercase tracking-wider mb-2">
                  <span>Data Usulan Baru (Draf Pengajuan)</span>
                  <span className="text-emerald-700 font-mono">USULAN</span>
                </div>
                <pre className="p-3 bg-white rounded-lg border border-emerald-200 text-xs font-mono text-emerald-950 font-medium whitespace-pre-wrap shadow-2xs">
                  {JSON.stringify(item.payloadAfter, null, 2)}
                </pre>
              </div>
            </div>
          </Card>
        ))}
      </div>

      {/* DECISION MODAL */}
      <Modal
        isOpen={!!decisionType}
        onClose={() => setDecisionType(null)}
        title={
          decisionType === 'PASS'
            ? 'Konfirmasi Lolos Verifikasi'
            : decisionType === 'REVISION_REQUIRED'
            ? 'Kirim Permintaan Revisi'
            : 'Konfirmasi Penolakan Pengajuan'
        }
        description={`Nomor Tiket: ${submission.ticketNumber} (${submission.institutionName})`}
        footer={
          <>
            <Button variant="ghost" size="sm" onClick={() => setDecisionType(null)}>
              Batal
            </Button>
            <Button
              variant={decisionType === 'PASS' ? 'primary' : decisionType === 'REVISION_REQUIRED' ? 'gold' : 'danger'}
              size="sm"
              isLoading={isSubmittingDecision}
              onClick={handleConfirmDecision}
            >
              Simpan & Kirim Keputusan
            </Button>
          </>
        }
      >
        <div className="space-y-4 text-xs">
          <div>
            <label className="block text-xs font-semibold text-slate-800 mb-1">
              Catatan Telaah & Rekomendasi Verifikator *
            </label>
            <textarea
              rows={4}
              required
              placeholder="Tuliskan alasan pengesahan, rincian butir revisi yang harus diperbaiki, atau alasan penolakan..."
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              className="w-full p-3 text-xs bg-white border border-slate-300 rounded-md focus:ring-2 focus:ring-primary-800 focus:outline-none"
            />
          </div>
        </div>
      </Modal>
    </div>
  );
}
