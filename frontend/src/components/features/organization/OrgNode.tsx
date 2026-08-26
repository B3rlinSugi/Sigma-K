'use client';

import React, { memo } from 'react';
import { Handle, Position, NodeProps, Node } from '@xyflow/react';
import { Building, Users, UserCheck, Shield } from 'lucide-react';
import { OrganizationUnit } from '@/types/organization';
import { cn } from '@/lib/utils';

export interface OrgNodeData extends Record<string, unknown> {
  unit: OrganizationUnit;
  isExpanded?: boolean;
  onToggleExpand?: (nodeId: string) => void;
  isSelected?: boolean;
}

export const OrgNode = memo(({ data, selected }: NodeProps<Node<OrgNodeData>>) => {
  const unit = data?.unit;

  if (!unit) return null;

  const getEchelonColor = (hierarchyLevel: number) => {
    switch (hierarchyLevel) {
      case 1:
        return 'bg-primary-950 text-gold-400 border-gold-500/50';
      case 2:
        return 'bg-primary-900 text-white border-primary-700';
      case 3:
        return 'bg-slate-800 text-slate-100 border-slate-700';
      default:
        return 'bg-slate-700 text-slate-200 border-slate-600';
    }
  };

  const isRoot = unit.hierarchyLevel === 1;

  return (
    <div
      className={cn(
        'w-72 rounded-xl bg-white border shadow-md transition-all select-none',
        selected ? 'ring-2 ring-gold-500 border-gold-500 shadow-lg' : 'border-slate-200 hover:border-primary-400 hover:shadow-md'
      )}
    >
      {/* Top Handle for Connection */}
      {!isRoot && (
        <Handle
          type="target"
          position={Position.Top}
          className="w-3 h-3 bg-primary-800 border-2 border-white rounded-full -top-1.5"
        />
      )}

      {/* Header Banner */}
      <div className={cn('px-3.5 py-2 rounded-t-xl flex items-center justify-between border-b text-xs font-semibold', getEchelonColor(unit.hierarchyLevel))}>
        <div className="flex items-center gap-1.5 truncate">
          {isRoot ? <Shield className="w-3.5 h-3.5 text-gold-400" /> : <Building className="w-3.5 h-3.5 text-slate-300" />}
          <span className="truncate">{unit.echelonName || 'Unit Struktural'}</span>
        </div>
        <span className="text-[10px] font-mono opacity-80 shrink-0">L{unit.hierarchyLevel}</span>
      </div>

      {/* Body Content */}
      <div className="p-3.5">
        <h4 className="text-xs font-bold text-slate-900 leading-snug line-clamp-2 min-h-[32px]">
          {unit.unitName}
        </h4>

        {unit.leaderName && (
          <div className="mt-2 pt-2 border-t border-slate-100 flex items-center gap-1.5 text-[11px] text-slate-600">
            <UserCheck className="w-3.5 h-3.5 text-primary-700 shrink-0" />
            <div className="truncate">
              <span className="font-semibold text-slate-800 block truncate">{unit.leaderName}</span>
              <span className="text-[10px] text-slate-400 truncate block">{unit.leaderTitle}</span>
            </div>
          </div>
        )}

        {/* Footer Meta */}
        <div className="mt-2.5 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-500">
          <span className="flex items-center gap-1">
            <Users className="w-3 h-3 text-slate-400" />
            {unit.staffCount || 0} Pegawai
          </span>
          <span className="font-mono text-slate-400 font-medium">
            {unit.unitCode}
          </span>
        </div>
      </div>

      {/* Bottom Handle */}
      <Handle
        type="source"
        position={Position.Bottom}
        className="w-3 h-3 bg-primary-800 border-2 border-white rounded-full -bottom-1.5"
      />
    </div>
  );
});

OrgNode.displayName = 'OrgNode';
