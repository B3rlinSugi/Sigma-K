import React from 'react';
import { Check, Clock, AlertCircle, FileEdit, CheckCircle2, XCircle, Sparkles } from 'lucide-react';
import { WorkflowStatus } from '@/types/submission';
import { WORKFLOW_STATES_MAP } from '@/config/workflow.config';
import { cn } from '@/lib/utils';

export interface WorkflowStepperProps {
  currentStatus: WorkflowStatus;
  className?: string;
  workflowModelName?: string;
}

export function WorkflowStepper({ currentStatus, className, workflowModelName = 'Prototype Workflow (Standard 5-Step)' }: WorkflowStepperProps) {
  const steps: { key: WorkflowStatus[]; label: string; description: string }[] = [
    { key: ['DRAFT'], label: '1. Draf Usulan', description: WORKFLOW_STATES_MAP.DRAFT.description },
    { key: ['SUBMITTED', 'RESUBMITTED'], label: '2. Pengajuan', description: WORKFLOW_STATES_MAP.SUBMITTED.description },
    { key: ['IN_REVIEW', 'REVISION_REQUIRED'], label: '3. Verifikasi & Telaah', description: WORKFLOW_STATES_MAP.IN_REVIEW.description },
    { key: ['VERIFIED'], label: '4. Lolos Telaah', description: WORKFLOW_STATES_MAP.VERIFIED.description },
    { key: ['APPROVED'], label: '5. Pengesahan Akhir', description: WORKFLOW_STATES_MAP.APPROVED.description },
  ];

  const getStepStatus = (index: number) => {
    if (currentStatus === 'REJECTED') {
      return index === 2 ? 'rejected' : 'upcoming';
    }
    if (currentStatus === 'REVISION_REQUIRED' && index === 2) {
      return 'revision';
    }

    const currentOrder = WORKFLOW_STATES_MAP[currentStatus]?.orderStandard ?? 0;

    if (index < currentOrder) return 'completed';
    if (index === currentOrder) return 'current';
    return 'upcoming';
  };

  return (
    <div className={cn('w-full space-y-3', className)}>
      <div className="flex items-center justify-between text-xs text-slate-500 border-b border-slate-100 pb-2">
        <span className="flex items-center gap-1.5 font-semibold text-slate-700">
          <Sparkles className="w-3.5 h-3.5 text-gold-500" />
          Model Alur Kerja: {workflowModelName}
        </span>
        <span className="text-[10px] font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-600">
          Status: {WORKFLOW_STATES_MAP[currentStatus]?.label || currentStatus}
        </span>
      </div>

      <div className="w-full py-2 overflow-x-auto">
        <div className="flex items-center justify-between min-w-[640px]">
          {steps.map((step, index) => {
            const status = getStepStatus(index);
            const isLast = index === steps.length - 1;

            return (
              <React.Fragment key={step.label}>
                <div className="flex flex-col items-center text-center relative z-10 flex-1">
                  <div
                    className={cn(
                      'w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs transition-all shadow-xs',
                      status === 'completed' && 'bg-emerald-600 text-white',
                      status === 'current' && 'bg-primary-900 text-white ring-4 ring-primary-100',
                      status === 'revision' && 'bg-amber-500 text-white ring-4 ring-amber-100',
                      status === 'rejected' && 'bg-red-600 text-white ring-4 ring-red-100',
                      status === 'upcoming' && 'bg-slate-100 text-slate-400 border border-slate-300'
                    )}
                  >
                    {status === 'completed' && <Check className="w-4 h-4" />}
                    {status === 'current' && <Clock className="w-4 h-4 animate-pulse" />}
                    {status === 'revision' && <FileEdit className="w-4 h-4" />}
                    {status === 'rejected' && <XCircle className="w-4 h-4" />}
                    {status === 'upcoming' && <span>{index + 1}</span>}
                  </div>
                  <span
                    className={cn(
                      'text-xs font-semibold mt-2',
                      status === 'completed' && 'text-emerald-700',
                      status === 'current' && 'text-primary-950 font-bold',
                      status === 'revision' && 'text-amber-700 font-bold',
                      status === 'rejected' && 'text-red-700',
                      status === 'upcoming' && 'text-slate-400'
                    )}
                  >
                    {step.label}
                  </span>
                  <span className="text-[11px] text-slate-500 max-w-[120px] line-clamp-2 mt-0.5">
                    {step.description}
                  </span>
                </div>

                {!isLast && (
                  <div
                    className={cn(
                      'h-0.5 flex-1 mx-2 relative -top-3 z-0',
                      getStepStatus(index + 1) !== 'upcoming' ? 'bg-emerald-500' : 'bg-slate-200'
                    )}
                  />
                )}
              </React.Fragment>
            );
          })}
        </div>
      </div>
    </div>
  );
}
