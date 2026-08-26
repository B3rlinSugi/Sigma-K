'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import { useParams, useRouter } from 'next/navigation';
import { 
  FileEdit, 
  ArrowLeft, 
  AlertTriangle, 
  CheckCircle2, 
  Send, 
  Building2, 
  MessageSquare 
} from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Input } from '@/components/ui/Input';
import { Spinner, EmptyState } from '@/components/ui/Breadcrumb';
import { SubmissionService } from '@/services/api/submission.service';
import { SubmissionTicket } from '@/types/submission';
import { useRole } from '@/context/RoleContext';

export default function RevisionWorkflowPage() {
  const params = useParams();
  const router = useRouter();
  const subId = (params?.id as string) || 'sub-003';
  const { currentUser } = useRole();

  const [submission, setSubmission] = useState<SubmissionTicket | null>(null);
  const [revisedText, setRevisedText] = useState('');
  const [revisedCitation, setRevisedCitation] = useState('');
  const [operatorResponseNote, setOperatorResponseNote] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSuccess, setIsSuccess] = useState(false);

  useEffect(() => {
    async function load() {
      setIsLoading(true);
      const data = await SubmissionService.getSubmissionById(subId);
      setSubmission(data);
      if (data?.items?.[0]?.payloadAfter) {
        const p = data.items[0].payloadAfter;
        if (typeof p.contentText === 'string') {
          setRevisedText(p.contentText);
        }
        if (typeof p.legalArticleReference === 'string') {
          setRevisedCitation(p.legalArticleReference);
        }
      }
      setIsLoading(false);
    }
    load();
  }, [subId]);

  const handleResubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    // Update status to SUBMITTED (Resubmitted)
    const updated = await SubmissionService.updateStatus(
      subId,
      'SUBMITTED',
      `Operator mengirimkan perbaikan: ${operatorResponseNote}`,
      currentUser.fullName
    );

    setIsSubmitting(false);
    if (updated) {
      setIsSuccess(true);
      setTimeout(() => {
        router.push(`/submissions/${subId}`);
      }, 1500);
    }
  };

  if (isLoading) return <Spinner />;
  if (!submission) return <EmptyState title="Pengajuan Tidak Ditemukan" />;

  const latestLog = submission.verificationLogs?.[submission.verificationLogs.length - 1];

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title={`Perbaikan Revisi: ${submission.ticketNumber}`}
        subtitle={`${submission.institutionName} • Tanggapi Catatan Verifikator`}
        breadcrumbs={[
          { label: 'Pengajuan', href: '/submissions' },
          { label: submission.ticketNumber, href: `/submissions/${subId}` },
          { label: 'Formulir Perbaikan Revisi' },
        ]}
      />

      {isSuccess && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
          <p className="text-xs font-semibold">
            Berkas perbaikan berhasil dikirimkan kembali ke antrean Verifikator KemenPANRB. Mengalihkan...
          </p>
        </div>
      )}

      {/* VERIFICATION CORRECTION NOTE CALLOUT */}
      <Card className="bg-amber-50/70 border-amber-300 shadow-2xs">
        <CardHeader className="bg-amber-100/60 border-amber-200 py-3">
          <div className="flex items-center gap-2 text-amber-900">
            <AlertTriangle className="w-4 h-4 text-amber-700" />
            <CardTitle className="text-sm font-bold">Catatan Poin Perbaikan dari Verifikator</CardTitle>
          </div>
          <Badge variant="warning">Perlu Revisi</Badge>
        </CardHeader>
        <CardContent className="p-4 space-y-1.5 text-xs text-amber-950">
          <p className="font-semibold">{latestLog?.verifierName || 'Analis Kelembagaan KemenPANRB'}:</p>
          <p className="leading-relaxed bg-white p-3 rounded-lg border border-amber-200 font-medium">
            &quot;{latestLog?.notes || 'Mohon lengkapi rujukan pasal regulasi dan sesuaikan nomenklatur unit organisasi.'}&quot;
          </p>
        </CardContent>
      </Card>

      {/* REVISION EDIT FORM */}
      <form onSubmit={handleResubmit}>
        <Card>
          <CardHeader>
            <CardTitle className="text-base font-bold flex items-center gap-2">
              <FileEdit className="w-4 h-4 text-primary-800" />
              Formulir Penyesuaian Data Usulan
            </CardTitle>
          </CardHeader>

          <CardContent className="space-y-4 text-xs">
            <Input
              label="Rujukan Pasal & Ayat Regulasi (Sesuai Perpres)"
              required
              value={revisedCitation}
              onChange={(e) => setRevisedCitation(e.target.value)}
              placeholder="Contoh: Perpres No. 190/2024 Pasal 5 ayat (2) huruf a"
            />

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Redaksi Teks Perbaikan *
              </label>
              <textarea
                rows={4}
                required
                value={revisedText}
                onChange={(e) => setRevisedText(e.target.value)}
                className="w-full p-3 text-xs bg-white border border-slate-300 rounded-md focus:ring-2 focus:ring-primary-800 focus:outline-none"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Tanggapan / Catatan Operator Pengusul *
              </label>
              <textarea
                rows={3}
                required
                placeholder="Jelaskan penyesuaian yang telah Anda lakukan..."
                value={operatorResponseNote}
                onChange={(e) => setOperatorResponseNote(e.target.value)}
                className="w-full p-3 text-xs bg-white border border-slate-300 rounded-md focus:ring-2 focus:ring-primary-800 focus:outline-none"
              />
            </div>
          </CardContent>

          <CardFooter>
            <Link href={`/submissions/${subId}`}>
              <Button variant="ghost" size="sm" leftIcon={<ArrowLeft className="w-3.5 h-3.5" />}>
                Batal
              </Button>
            </Link>
            <Button
              type="submit"
              variant="primary"
              size="sm"
              isLoading={isSubmitting}
              leftIcon={<Send className="w-3.5 h-3.5" />}
            >
              Kirim Ulang Berkas Perbaikan
            </Button>
          </CardFooter>
        </Card>
      </form>
    </div>
  );
}
