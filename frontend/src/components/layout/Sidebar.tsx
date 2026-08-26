'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  LayoutDashboard,
  Building2,
  GitCompare,
  Network,
  BookOpen,
  FileCheck2,
  Inbox,
  BarChart3,
  History,
  ShieldAlert,
  Layers,
  Sparkles,
  ChevronRight,
  Landmark,
} from 'lucide-react';
import { useRole } from '@/context/RoleContext';
import { UserRole } from '@/types/auth';
import { cn } from '@/lib/utils';

interface NavGroup {
  title: string;
  items: {
    label: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
    roles?: UserRole[];
    badge?: string;
  }[];
}

export function Sidebar() {
  const pathname = usePathname();
  const { currentRole } = useRole();

  const navGroups: NavGroup[] = [
    {
      title: 'UTAMA',
      items: [
        {
          label: 'Dashboard Eksekutif',
          href: '/',
          icon: LayoutDashboard,
        },
      ],
    },
    {
      title: 'KABINET & MASTER DATA',
      items: [
        {
          label: 'Manajemen Kabinet',
          href: '/cabinets',
          icon: Landmark,
        },
        {
          label: 'Komparasi Kabinet',
          href: '/cabinets/compare',
          icon: GitCompare,
          badge: 'Diff',
        },
        {
          label: 'Katalog Instansi',
          href: '/institutions',
          icon: Building2,
        },
      ],
    },
    {
      title: 'KELEMBAGAAN & SOTK',
      items: [
        {
          label: 'Struktur Organisasi',
          href: '/structure',
          icon: Network,
        },
        {
          label: 'Tugas dan Fungsi (Tupoksi)',
          href: '/tupoksi',
          icon: BookOpen,
        },
      ],
    },
    {
      title: 'TATA KELOLA & WORKFLOW',
      items: [
        {
          label: 'Pengajuan Usulan',
          href: '/submissions',
          icon: Inbox,
        },
        {
          label: 'Antrean Verifikasi',
          href: '/verifications',
          icon: FileCheck2,
          roles: ['VERIFIER', 'VERIFIKATOR', 'ADMIN'],
          badge: 'Review',
        },
      ],
    },
    {
      title: 'INTELIJENSI & MONITORING',
      items: [
        {
          label: 'Data & Postur ASN',
          href: '/analytics',
          icon: BarChart3,
          roles: ['SUPER_ADMIN', 'SESDEP', 'ADMIN'],
        },
        {
          label: 'Audit Trail Forensik',
          href: '/audit-logs',
          icon: History,
          roles: ['SUPER_ADMIN', 'SESDEP', 'ADMIN'],
        },
      ],
    },
  ];

  return (
    <aside className="w-64 bg-slate-900 border-r border-slate-800 text-slate-300 flex flex-col shrink-0 min-h-[calc(100vh-4rem)]">
      <div className="flex-1 py-4 px-3 space-y-6 overflow-y-auto">
        {navGroups.map((group) => {
          // Filter items based on user role
          const visibleItems = group.items.filter(
            (item) => !item.roles || item.roles.includes(currentRole)
          );

          if (visibleItems.length === 0) return null;

          return (
            <div key={group.title}>
              <h4 className="px-3 text-[10px] font-bold tracking-wider text-slate-500 uppercase mb-2">
                {group.title}
              </h4>
              <ul className="space-y-1">
                {visibleItems.map((item) => {
                  const Icon = item.icon;
                  const isActive = pathname === item.href || (item.href !== '/' && pathname.startsWith(item.href));

                  return (
                    <li key={item.href}>
                      <Link
                        href={item.href}
                        className={cn(
                          'flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-all group',
                          isActive
                            ? 'bg-primary-950 text-gold-300 border border-primary-800 font-semibold shadow-xs'
                            : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
                        )}
                      >
                        <div className="flex items-center gap-2.5 min-w-0">
                          <Icon
                            className={cn(
                              'w-4 h-4 shrink-0 transition-colors',
                              isActive ? 'text-gold-400' : 'text-slate-400 group-hover:text-slate-200'
                            )}
                          />
                          <span className="truncate">{item.label}</span>
                        </div>

                        {item.badge && (
                          <span className="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gold-500/20 text-gold-300 border border-gold-400/30">
                            {item.badge}
                          </span>
                        )}
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </div>
          );
        })}
      </div>

      {/* FOOTER METRIC / HELP */}
      <div className="p-3 border-t border-slate-800 bg-slate-950/40">
        <div className="p-3 rounded-lg bg-slate-800/60 border border-slate-700/60 space-y-1.5">
          <div className="flex items-center justify-between text-[11px]">
            <span className="text-slate-400 font-medium">Status Sistem:</span>
            <span className="inline-flex items-center gap-1 text-emerald-400 font-semibold text-[10px]">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping" />
              Operasional
            </span>
          </div>
          <p className="text-[10px] text-slate-500 leading-tight">
            Terhubung ke Database SPBE KemenPANRB v1.0.0
          </p>
        </div>
      </div>
    </aside>
  );
}
