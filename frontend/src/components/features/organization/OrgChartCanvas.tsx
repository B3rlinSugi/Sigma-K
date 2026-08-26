'use client';

import React, { useState, useMemo, useCallback, useEffect } from 'react';
import {
  ReactFlow,
  Controls,
  Background,
  MiniMap,
  useNodesState,
  useEdgesState,
  Node,
  Edge,
  MarkerType,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { OrgNode, OrgNodeData } from './OrgNode';
import { OrganizationUnit, Position } from '@/types/organization';
import { Drawer } from '@/components/ui/Drawer';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Search, Building2, Users, ShieldCheck, Briefcase, ChevronRight } from 'lucide-react';
import { OrganizationService } from '@/services/api/organization.service';

const nodeTypes = {
  orgNode: OrgNode,
};

export interface OrgChartCanvasProps {
  units: OrganizationUnit[];
  institutionName?: string;
}

export function OrgChartCanvas({ units, institutionName }: OrgChartCanvasProps) {
  const [selectedUnit, setSelectedUnit] = useState<OrganizationUnit | null>(null);
  const [unitPositions, setUnitPositions] = useState<Position[]>([]);
  const [isLoadingPositions, setIsLoadingPositions] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  // 1. Convert units to React Flow hierarchy nodes with computed positions
  const { initialNodes, initialEdges } = useMemo(() => {
    const nodes: Node<OrgNodeData>[] = [];
    const edges: Edge[] = [];

    // Group units by level
    const levelMap: Record<number, OrganizationUnit[]> = {};
    units.forEach((u) => {
      if (!levelMap[u.hierarchyLevel]) levelMap[u.hierarchyLevel] = [];
      levelMap[u.hierarchyLevel].push(u);
    });

    const HORIZONTAL_SPACING = 320;
    const VERTICAL_SPACING = 240;

    Object.keys(levelMap).forEach((levelStr) => {
      const level = parseInt(levelStr, 10);
      const unitsInLevel = levelMap[level];
      const totalWidth = unitsInLevel.length * HORIZONTAL_SPACING;
      const startX = -totalWidth / 2 + HORIZONTAL_SPACING / 2;

      unitsInLevel.forEach((unit, idx) => {
        const x = startX + idx * HORIZONTAL_SPACING;
        const y = (level - 1) * VERTICAL_SPACING;

        nodes.push({
          id: unit.id,
          type: 'orgNode',
          position: { x, y },
          data: { unit },
        });

        if (unit.parentId) {
          edges.push({
            id: `e-${unit.parentId}-${unit.id}`,
            source: unit.parentId,
            target: unit.id,
            type: 'smoothstep',
            animated: true,
            style: { stroke: '#0B2A4A', strokeWidth: 2 },
            markerEnd: {
              type: MarkerType.ArrowClosed,
              color: '#0B2A4A',
              width: 14,
              height: 14,
            },
          });
        }
      });
    });

    return { initialNodes: nodes, initialEdges: edges };
  }, [units]);

  const [nodes, setNodes, onNodesChange] = useNodesState<Node<OrgNodeData>>(initialNodes);
  const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);

  // Sync state if units prop updates
  useEffect(() => {
    setNodes(initialNodes);
    setEdges(initialEdges);
  }, [initialNodes, initialEdges, setNodes, setEdges]);

  // Load positions when a unit is selected
  useEffect(() => {
    async function loadPositions() {
      if (!selectedUnit) {
        setUnitPositions([]);
        return;
      }
      setIsLoadingPositions(true);
      try {
        const positions = await OrganizationService.getPositionsByUnitId(selectedUnit.id);
        setUnitPositions(positions);
      } catch (err) {
        console.warn('Failed to load positions for unit:', err);
        setUnitPositions([]);
      } finally {
        setIsLoadingPositions(false);
      }
    }
    loadPositions();
  }, [selectedUnit]);

  // Search and select
  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (!searchQuery.trim()) return;
    const found = units.find((u) =>
      u.unitName.toLowerCase().includes(searchQuery.toLowerCase()) ||
      u.unitCode.toLowerCase().includes(searchQuery.toLowerCase())
    );
    if (found) {
      setSelectedUnit(found);
    }
  };

  const onNodeClick = useCallback(
    (_: React.MouseEvent, node: Node<OrgNodeData>) => {
      const unit = node.data?.unit;
      if (unit) setSelectedUnit(unit);
    },
    []
  );

  return (
    <div className="relative w-full h-[680px] bg-slate-900 rounded-xl border border-slate-700 shadow-xl overflow-hidden flex flex-col">
      {/* Top Floating Control Bar */}
      <div className="absolute top-4 left-4 right-4 z-10 flex flex-wrap items-center justify-between gap-3 pointer-events-none">
        <div className="pointer-events-auto bg-slate-950/80 backdrop-blur-md px-4 py-2 rounded-lg border border-slate-700/80 text-white flex items-center gap-2.5 shadow-lg">
          <Building2 className="w-4 h-4 text-gold-400" />
          <span className="text-xs font-bold">{institutionName || 'Bagan Struktur Organisasi'}</span>
          <Badge variant="gold" size="sm">
            {units.length} Unit Kerja
          </Badge>
        </div>

        {/* Search in Canvas */}
        <form onSubmit={handleSearch} className="pointer-events-auto flex items-center gap-1.5 bg-slate-950/80 backdrop-blur-md p-1 rounded-lg border border-slate-700/80 shadow-lg">
          <input
            type="text"
            placeholder="Cari nama unit..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="h-8 px-2.5 text-xs bg-slate-900 text-white border-0 rounded focus:ring-1 focus:ring-gold-400 placeholder:text-slate-500 w-48"
          />
          <Button type="submit" size="sm" variant="gold" leftIcon={<Search className="w-3.5 h-3.5" />}>
            Cari
          </Button>
        </form>
      </div>

      {/* React Flow Core Viewport */}
      <div className="flex-1 w-full h-full">
        <ReactFlow<Node<OrgNodeData>>
          nodes={nodes}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onNodeClick={onNodeClick}
          nodeTypes={nodeTypes}
          fitView
          fitViewOptions={{ padding: 0.2 }}
          minZoom={0.2}
          maxZoom={1.8}
        >
          <Background color="#334155" gap={24} size={1.5} />
          <Controls className="bg-white border-slate-200 rounded-lg shadow-md overflow-hidden text-slate-800" />
          <MiniMap
            className="bg-slate-950/80 border border-slate-800 rounded-lg overflow-hidden"
            nodeColor="#D4AF37"
            maskColor="rgba(15, 23, 42, 0.7)"
          />
        </ReactFlow>
      </div>

      {/* NODE DETAIL DRAWER */}
      <Drawer
        isOpen={!!selectedUnit}
        onClose={() => setSelectedUnit(null)}
        title={selectedUnit?.unitName || 'Rincian Unit Kerja'}
        description={`Kode Unit: ${selectedUnit?.unitCode || '-'}`}
        width="md"
        footer={
          <Button variant="secondary" size="sm" onClick={() => setSelectedUnit(null)}>
            Tutup Lembar Rincian
          </Button>
        }
      >
        {selectedUnit && (
          <div className="space-y-4 text-xs">
            <div className="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-2">
              <div className="flex justify-between">
                <span className="text-slate-500">Tingkat Hierarki:</span>
                <span className="font-bold text-slate-800">Level {selectedUnit.hierarchyLevel}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Urutan Sort:</span>
                <span className="font-bold text-slate-800">#{selectedUnit.sortOrder}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Status Operasional:</span>
                <Badge variant={selectedUnit.isActive ? 'success' : 'secondary'}>
                  {selectedUnit.isActive ? 'Aktif Beroperasi' : 'Tidak Aktif'}
                </Badge>
              </div>
            </div>

            {/* LIVE POSITIONS (JABATAN) LIST */}
            <div className="p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
              <div className="flex items-center justify-between">
                <h5 className="font-bold text-slate-800 flex items-center gap-1.5">
                  <Briefcase className="w-4 h-4 text-primary-800" />
                  Formasi Jabatan ({unitPositions.length})
                </h5>
                {isLoadingPositions && <span className="text-[10px] text-slate-400">Memuat...</span>}
              </div>

              {unitPositions.length === 0 && !isLoadingPositions ? (
                <p className="text-slate-500 italic text-[11px]">
                  Belum ada formasi jabatan terdaftar pada unit kerja ini.
                </p>
              ) : (
                <div className="space-y-2">
                  {unitPositions.map((pos) => (
                    <div key={pos.id} className="p-2.5 bg-white rounded border border-slate-200 flex items-start justify-between gap-2 shadow-xs">
                      <div>
                        <span className="font-bold text-slate-900 block">{pos.positionName}</span>
                        <div className="flex items-center gap-2 mt-1">
                          <span className="text-[10px] text-slate-500 font-mono">
                            {pos.positionType}
                          </span>
                          {pos.echelon && (
                            <span className="text-[10px] bg-primary-50 text-primary-800 px-1.5 py-0.2 rounded border border-primary-200">
                              Eselon {pos.echelon}
                            </span>
                          )}
                        </div>
                      </div>
                      <span className="text-[11px] font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded shrink-0">
                        {pos.formationCount} Formasi
                      </span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </Drawer>
    </div>
  );
}
