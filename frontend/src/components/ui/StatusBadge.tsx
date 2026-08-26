import React from 'react';
import { Badge } from './Badge';
import { WorkflowStatus } from '@/types/submission';
import { LineageTransitionType } from '@/types/cabinet';

export function WorkflowStatusBadge({ status }: { status: WorkflowStatus }) {
  switch (status) {
    case 'DRAFT':
      return <Badge variant="secondary">Draf</Badge>;
    case 'SUBMITTED':
      return <Badge variant="info">Diajukan</Badge>;
    case 'IN_REVIEW':
      return <Badge variant="warning">Sedang Ditelaah</Badge>;
    case 'VERIFIED':
      return <Badge variant="primary">Lolos Verifikasi</Badge>;
    case 'REVISION_REQUIRED':
      return <Badge variant="danger">Perlu Revisi</Badge>;
    case 'APPROVED':
      return <Badge variant="success">Disahkan</Badge>;
    case 'REJECTED':
      return <Badge variant="danger">Ditolak</Badge>;
    default:
      return <Badge variant="default">{status}</Badge>;
  }
}

export function TransitionBadge({ type }: { type: LineageTransitionType }) {
  switch (type) {
    case 'SPLIT':
      return <Badge variant="warning">Pecah Instansi</Badge>;
    case 'NEW':
      return <Badge variant="success">+ Instansi Baru</Badge>;
    case 'MERGE':
      return <Badge variant="info">Penggabungan</Badge>;
    case 'RENAME':
      return <Badge variant="primary">Ubah Nomenklatur</Badge>;
    case 'DISSOLVED':
      return <Badge variant="danger">Dibubarkan</Badge>;
    case 'UNCHANGED':
      return <Badge variant="secondary">Tetap</Badge>;
    default:
      return <Badge variant="default">{type}</Badge>;
  }
}

export function ActiveStatusBadge({ isActive }: { isActive: boolean }) {
  return isActive ? (
    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
      <span className="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
      Aktif
    </span>
  ) : (
    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
      <span className="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
      Non-Aktif
    </span>
  );
}
