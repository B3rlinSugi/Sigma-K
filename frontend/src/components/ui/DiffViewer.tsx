import React from 'react';
import { ArrowRight, Plus, Minus, RefreshCw, CheckCircle2, Split, GitMerge } from 'lucide-react';
import { LineageTransitionType } from '@/types/cabinet';
import { TransitionBadge } from './StatusBadge';
import { cn } from '@/lib/utils';

export interface DiffViewerProps {
  title?: string;
  transitionType: LineageTransitionType;
  beforeLabel?: string;
  afterLabel?: string;
  beforeContent?: React.ReactNode;
  afterContent?: React.ReactNode;
  details?: string;
  className?: string;
}

export function DiffViewer({
  title,
  transitionType,
  beforeLabel = 'Data Eksisting / Sebelumnya',
  afterLabel = 'Data Baru / Usulan',
  beforeContent,
  afterContent,
  details,
  className,
}: DiffViewerProps) {
  const getIcon = () => {
    switch (transitionType) {
      case 'SPLIT':
        return <Split className="w-4 h-4 text-amber-600" />;
      case 'MERGE':
        return <GitMerge className="w-4 h-4 text-sky-600" />;
      case 'NEW':
        return <Plus className="w-4 h-4 text-emerald-600" />;
      case 'RENAME':
        return <RefreshCw className="w-4 h-4 text-primary-700" />;
      case 'DISSOLVED':
        return <Minus className="w-4 h-4 text-red-600" />;
      case 'UNCHANGED':
        return <CheckCircle2 className="w-4 h-4 text-slate-500" />;
    }
  };

  return (
    <div className={cn('bg-white rounded-lg border border-slate-200 overflow-hidden shadow-2xs', className)}>
      {title && (
        <div className="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
          <div className="flex items-center gap-2">
            {getIcon()}
            <span className="text-sm font-semibold text-slate-800">{title}</span>
          </div>
          <TransitionBadge type={transitionType} />
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-200">
        {/* Left: Previous */}
        <div className="p-4 bg-slate-50/40">
          <div className="flex items-center justify-between text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
            <span>{beforeLabel}</span>
            {beforeContent ? <span className="text-slate-400 font-mono">LAMA</span> : <span className="text-amber-600 font-mono">TIDAK ADA</span>}
          </div>
          <div className="text-sm text-slate-700">
            {beforeContent || <p className="text-xs text-slate-400 italic">Tidak ada data sebelumnya (Entitas Baru).</p>}
          </div>
        </div>

        {/* Right: New / Proposed */}
        <div className="p-4 bg-emerald-50/20">
          <div className="flex items-center justify-between text-xs font-semibold text-emerald-800 uppercase tracking-wider mb-2">
            <span>{afterLabel}</span>
            <span className="text-emerald-700 font-mono">TARGET</span>
          </div>
          <div className="text-sm text-slate-900 font-medium">
            {afterContent || <p className="text-xs text-slate-400 italic">Tidak ada perubahan.</p>}
          </div>
        </div>
      </div>

      {details && (
        <div className="px-4 py-2.5 bg-slate-50/80 border-t border-slate-200 text-xs text-slate-600 flex items-center gap-2">
          <span className="font-semibold text-slate-700">Catatan:</span>
          <span>{details}</span>
        </div>
      )}
    </div>
  );
}
