'use client';

import React from 'react';
import { TopBar } from './TopBar';
import { Sidebar } from './Sidebar';
import { Drawer } from '@/components/ui/Drawer';
import { useNotifications } from '@/context/NotificationContext';
import { formatDateTimeIndonesian } from '@/lib/utils';
import { Bell, CheckCheck, ExternalLink, Info, ShieldAlert } from 'lucide-react';
import Link from 'next/link';

export function AppShell({ children }: { children: React.ReactNode }) {
  const { notifications, isDrawerOpen, closeDrawer, markAsRead, markAllAsRead, unreadCount } = useNotifications();

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
      <TopBar />
      <div className="flex-1 flex overflow-hidden">
        <Sidebar />
        <main className="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
          {children}
        </main>
      </div>

      {/* NOTIFICATION CENTER DRAWER */}
      <Drawer
        isOpen={isDrawerOpen}
        onClose={closeDrawer}
        title="Pusat Notifikasi & Aktivitas"
        description={`${unreadCount} pemberitahuan belum dibaca`}
        width="md"
        footer={
          <div className="w-full flex items-center justify-between">
            <button
              onClick={markAllAsRead}
              className="text-xs text-primary-900 hover:text-primary-700 font-semibold flex items-center gap-1.5"
            >
              <CheckCheck className="w-4 h-4" />
              Tandai Semua Sudah Dibaca
            </button>
            <Link
              href="/notifications"
              onClick={closeDrawer}
              className="text-xs text-slate-500 hover:text-slate-800 font-medium"
            >
              Lihat Semua Notifikasi &rarr;
            </Link>
          </div>
        }
      >
        <div className="space-y-3">
          {notifications.length === 0 ? (
            <div className="text-center py-12 text-slate-400 text-xs">
              Tidak ada notifikasi baru saat ini.
            </div>
          ) : (
            notifications.map((n) => (
              <div
                key={n.id}
                onClick={() => markAsRead(n.id)}
                className={`p-3.5 rounded-lg border transition-all cursor-pointer ${
                  !n.isRead
                    ? 'bg-primary-50/40 border-primary-200 shadow-2xs'
                    : 'bg-white border-slate-200 opacity-80 hover:opacity-100'
                }`}
              >
                <div className="flex items-start justify-between gap-2">
                  <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-primary-700"></span>
                    <h4 className="text-xs font-bold text-slate-900">{n.title}</h4>
                  </div>
                  <span className="text-[10px] text-slate-400 shrink-0">
                    {formatDateTimeIndonesian(n.createdAt)}
                  </span>
                </div>
                <p className="text-xs text-slate-600 mt-1 leading-relaxed">{n.message}</p>
                {n.actionUrl && (
                  <div className="mt-2 pt-2 border-t border-slate-100 flex justify-end">
                    <Link
                      href={n.actionUrl}
                      onClick={closeDrawer}
                      className="text-[11px] text-primary-900 font-semibold flex items-center gap-1 hover:underline"
                    >
                      Buka Rincian <ExternalLink className="w-3 h-3" />
                    </Link>
                  </div>
                )}
              </div>
            ))
          )}
        </div>
      </Drawer>
    </div>
  );
}
