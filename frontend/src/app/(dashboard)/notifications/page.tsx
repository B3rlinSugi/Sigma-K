'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { Bell, CheckCheck, ExternalLink, Filter, ShieldAlert, Sparkles, Inbox, Landmark } from 'lucide-react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Tabs } from '@/components/ui/Tabs';
import { EmptyState } from '@/components/ui/Breadcrumb';
import { useNotifications } from '@/context/NotificationContext';
import { formatDateTimeIndonesian } from '@/lib/utils';
import { NotificationCategory } from '@/types/notification';

export default function NotificationsPage() {
  const { notifications, markAsRead, markAllAsRead, unreadCount } = useNotifications();
  const [selectedCategory, setSelectedCategory] = useState<string>('ALL');

  const filteredNotifs = notifications.filter(
    (n) => selectedCategory === 'ALL' || n.category === selectedCategory
  );

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-in fade-in duration-200">
      <PageHeader
        title="Pusat Notifikasi & Aktivitas Realtime"
        subtitle="Riwayat pemberitahuan otomatis seputar usulan perubahan data kelembagaan, proses verifikasi, dan pembaruan master data."
        breadcrumbs={[
          { label: 'Intelijensi & Audit' },
          { label: 'Pusat Notifikasi' },
        ]}
        actions={
          <Button
            variant="outline"
            size="sm"
            onClick={markAllAsRead}
            leftIcon={<CheckCheck className="w-4 h-4" />}
          >
            Tandai Semua Dibaca
          </Button>
        }
      />

      <Card>
        <div className="px-5 pt-3 border-b border-slate-100">
          <Tabs
            tabs={[
              { id: 'ALL', label: 'Semua Notifikasi', count: notifications.length },
              { id: 'WORKFLOW', label: 'Alur Kerja (Workflow)', count: notifications.filter((n) => n.category === 'WORKFLOW').length },
              { id: 'MASTER_DATA', label: 'Master Data & Kabinet', count: notifications.filter((n) => n.category === 'MASTER_DATA').length },
              { id: 'SECURITY', label: 'Keamanan Akun', count: notifications.filter((n) => n.category === 'SECURITY').length },
            ]}
            activeTab={selectedCategory}
            onChange={setSelectedCategory}
          />
        </div>

        <CardContent className="p-0">
          {filteredNotifs.length === 0 ? (
            <EmptyState title="Tidak Ada Notifikasi" description="Semua pemberitahuan telah Anda baca." />
          ) : (
            <div className="divide-y divide-slate-100">
              {filteredNotifs.map((n) => (
                <div
                  key={n.id}
                  onClick={() => markAsRead(n.id)}
                  className={`p-4 transition-all flex items-start justify-between gap-4 cursor-pointer ${
                    !n.isRead ? 'bg-primary-50/30 font-medium' : 'bg-white hover:bg-slate-50 opacity-80'
                  }`}
                >
                  <div className="flex items-start gap-3 min-w-0">
                    <div
                      className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5 ${
                        n.category === 'WORKFLOW'
                          ? 'bg-amber-100 text-amber-800'
                          : n.category === 'MASTER_DATA'
                          ? 'bg-primary-100 text-primary-800'
                          : 'bg-red-100 text-red-800'
                      }`}
                    >
                      {n.category === 'WORKFLOW' ? (
                        <Inbox className="w-4 h-4" />
                      ) : n.category === 'MASTER_DATA' ? (
                        <Landmark className="w-4 h-4" />
                      ) : (
                        <ShieldAlert className="w-4 h-4" />
                      )}
                    </div>
                    <div className="space-y-1">
                      <div className="flex items-center gap-2">
                        <h4 className="text-xs font-bold text-slate-900">{n.title}</h4>
                        {!n.isRead && (
                          <span className="w-2 h-2 rounded-full bg-primary-700 animate-pulse"></span>
                        )}
                      </div>
                      <p className="text-xs text-slate-600 leading-relaxed">{n.message}</p>
                      <span className="text-[10px] text-slate-400 block pt-0.5">
                        {formatDateTimeIndonesian(n.createdAt)}
                      </span>
                    </div>
                  </div>

                  {n.actionUrl && (
                    <Link
                      href={n.actionUrl}
                      className="shrink-0 text-xs text-primary-900 font-semibold hover:underline flex items-center gap-1"
                    >
                      Buka <ExternalLink className="w-3.5 h-3.5" />
                    </Link>
                  )}
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
