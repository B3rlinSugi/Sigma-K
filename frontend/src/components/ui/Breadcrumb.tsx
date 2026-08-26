import React from 'react';
import Link from 'next/link';
import { ChevronRight, Home } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface BreadcrumbItem {
  label: string;
  href?: string;
}

export function Breadcrumb({ items, className }: { items: BreadcrumbItem[]; className?: string }) {
  return (
    <nav className={cn('flex items-center gap-1.5 text-xs text-slate-500', className)} aria-label="Breadcrumb">
      <Link href="/" className="hover:text-primary-900 transition-colors flex items-center gap-1">
        <Home className="w-3.5 h-3.5" />
      </Link>
      {items.map((item, index) => {
        const isLast = index === items.length - 1;
        return (
          <React.Fragment key={index}>
            <ChevronRight className="w-3 h-3 text-slate-400" />
            {item.href && !isLast ? (
              <Link href={item.href} className="hover:text-primary-900 transition-colors">
                {item.label}
              </Link>
            ) : (
              <span className={cn('font-medium', isLast ? 'text-slate-900' : 'text-slate-500')}>
                {item.label}
              </span>
            )}
          </React.Fragment>
        );
      })}
    </nav>
  );
}

export function EmptyState({
  title,
  description,
  action,
  icon,
}: {
  title: string;
  description?: string;
  action?: React.ReactNode;
  icon?: React.ReactNode;
}) {
  return (
    <div className="flex flex-col items-center justify-center p-12 text-center bg-white rounded-lg border border-dashed border-slate-300">
      {icon && <div className="p-3 bg-slate-50 rounded-full text-slate-400 mb-3">{icon}</div>}
      <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
      {description && <p className="text-xs text-slate-500 max-w-sm mt-1">{description}</p>}
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}

export function Spinner({ className }: { className?: string }) {
  return (
    <div className={cn('flex items-center justify-center p-8', className)}>
      <div className="w-8 h-8 border-3 border-primary-200 border-t-primary-900 rounded-full animate-spin"></div>
    </div>
  );
}
