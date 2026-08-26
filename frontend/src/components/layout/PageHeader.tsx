import React from 'react';
import { Breadcrumb, BreadcrumbItem } from '@/components/ui/Breadcrumb';
import { cn } from '@/lib/utils';

export interface PageHeaderProps {
  title: string;
  subtitle?: string;
  breadcrumbs?: BreadcrumbItem[];
  actions?: React.ReactNode;
  badge?: React.ReactNode;
  className?: string;
}

export function PageHeader({ title, subtitle, breadcrumbs, actions, badge, className }: PageHeaderProps) {
  return (
    <div className={cn('mb-6 pb-4 border-b border-slate-200/80', className)}>
      {breadcrumbs && <Breadcrumb items={breadcrumbs} className="mb-2" />}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 font-heading">
              {title}
            </h1>
            {badge}
          </div>
          {subtitle && <p className="text-xs sm:text-sm text-slate-500 mt-1">{subtitle}</p>}
        </div>
        {actions && <div className="flex items-center gap-2.5 shrink-0">{actions}</div>}
      </div>
    </div>
  );
}
