'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { 
  Bell, 
  Search, 
  User as UserIcon, 
  ShieldCheck, 
  ChevronDown, 
  Sparkles,
  ExternalLink,
  Layers,
  LogOut,
  LogIn,
  Server,
  Lock
} from 'lucide-react';
import { useAuth } from '@/context/RoleContext';
import { useNotifications } from '@/context/NotificationContext';
import { UserRole } from '@/types/auth';

export function TopBar() {
  const { currentUser, currentRole, switchRole, availableUsers, isApiMode, isAuthenticated, logout } = useAuth();
  const { unreadCount, openDrawer } = useNotifications();
  const [isRoleDropdownOpen, setIsRoleDropdownOpen] = useState(false);

  const getRoleBadgeColor = (role: UserRole) => {
    switch (role) {
      case 'USER':
        return 'bg-sky-100 text-sky-800 border-sky-300';
      case 'VERIFIER':
      case 'VERIFIKATOR':
        return 'bg-amber-100 text-amber-900 border-amber-300';
      case 'ADMIN':
        return 'bg-purple-100 text-purple-900 border-purple-300';
      case 'SUPER_ADMIN':
      case 'SESDEP':
        return 'bg-emerald-100 text-emerald-900 border-emerald-300';
      default:
        return 'bg-slate-100 text-slate-800 border-slate-300';
    }
  };

  const getRoleTitle = (role: UserRole) => {
    switch (role) {
      case 'USER':
        return 'Operator Instansi (K/L)';
      case 'VERIFIER':
      case 'VERIFIKATOR':
        return 'Verifikator KemenPANRB';
      case 'ADMIN':
        return 'Administrator Pusat';
      case 'SUPER_ADMIN':
      case 'SESDEP':
        return 'Super Admin / SESDEP';
      default:
        return role;
    }
  };

  return (
    <header className="sticky top-0 z-30 h-16 bg-[#0B2A4A] border-b border-slate-700/60 shadow-md text-white flex items-center justify-between px-4 lg:px-6">
      {/* Brand & Identity */}
      <div className="flex items-center gap-3">
        <Link href="/" className="flex items-center gap-2.5 group">
          <div className="w-9 h-9 rounded-lg bg-gradient-to-br from-gold-400 to-gold-600 flex items-center justify-center shadow-md border border-gold-300">
            <Layers className="w-5 h-5 text-slate-950" />
          </div>
          <div>
            <div className="flex items-center gap-1.5">
              <span className="font-bold text-base tracking-wider text-white group-hover:text-gold-300 transition-colors">
                SIGMA-K
              </span>
              <span className="text-[10px] uppercase tracking-wider font-semibold px-1.5 py-0.5 rounded bg-gold-500/20 text-gold-300 border border-gold-400/30">
                {isApiMode ? 'LIVE API' : 'PROTOTYPE'}
              </span>
            </div>
            <p className="text-[10px] text-slate-300 hidden sm:block">
              Sistem Informasi Pengelolaan Kelembagaan KemenPANRB
            </p>
          </div>
        </Link>
      </div>

      {/* Global Controls & Persona Switcher */}
      <div className="flex items-center gap-3">
        {/* ROLE INDICATOR / PERSONA SWITCHER */}
        <div className="relative">
          {isApiMode ? (
            /* IN API MODE: Read-only Authoritative Role Badge with Profile Info */
            <div className="flex items-center gap-2 px-3 py-1.5 bg-slate-800/80 border border-slate-600 rounded-lg text-xs shadow-xs">
              <Lock className="w-3.5 h-3.5 text-emerald-400" />
              <div className="text-left hidden md:block">
                <span className="text-[10px] text-slate-400 block font-normal">Peran Terotentikasi:</span>
                <span className="font-bold text-slate-200">{getRoleTitle(currentRole)}</span>
              </div>
              <span className={`px-2 py-0.5 rounded text-[10px] font-bold border ${getRoleBadgeColor(currentRole)}`}>
                {currentRole}
              </span>
            </div>
          ) : (
            /* IN MOCK MODE: Interactive Persona Switcher for Walkthrough */
            <>
              <button
                onClick={() => setIsRoleDropdownOpen(!isRoleDropdownOpen)}
                className="flex items-center gap-2 px-3 py-1.5 bg-slate-800/80 hover:bg-slate-700/80 border border-slate-600 rounded-lg text-xs transition-all shadow-xs"
                title="Klik untuk mengganti peran demonstrasi (Persona Switcher)"
              >
                <Sparkles className="w-3.5 h-3.5 text-gold-400" />
                <div className="text-left hidden md:block">
                  <span className="text-[10px] text-slate-400 block font-normal">Peran Demo:</span>
                  <span className="font-bold text-slate-200">{getRoleTitle(currentRole)}</span>
                </div>
                <span className={`md:hidden px-2 py-0.5 rounded text-[10px] font-bold border ${getRoleBadgeColor(currentRole)}`}>
                  {currentRole}
                </span>
                <ChevronDown className="w-3.5 h-3.5 text-slate-400 ml-1" />
              </button>

              {isRoleDropdownOpen && (
                <div className="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-2xl border border-slate-200 py-2 z-50 text-slate-900 animate-in fade-in slide-in-from-top-2 duration-150">
                  <div className="px-4 py-2 border-b border-slate-100">
                    <p className="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                      <ShieldCheck className="w-4 h-4 text-primary-800" />
                      Pilih Persona Demo (Mock Mode)
                    </p>
                    <p className="text-[11px] text-slate-500 mt-0.5">
                      Uji tampilan antarmuka dan hak aksi sesuai kewenangan peran resmi.
                    </p>
                  </div>
                  <div className="py-1">
                    {availableUsers.map((u) => (
                      <button
                        key={u.id}
                        onClick={() => {
                          switchRole(u.role);
                          setIsRoleDropdownOpen(false);
                        }}
                        className={`w-full px-4 py-2.5 text-left flex items-start gap-3 hover:bg-slate-50 transition-colors ${
                          currentRole === u.role ? 'bg-primary-50/70 border-l-4 border-primary-900 font-semibold' : ''
                        }`}
                      >
                        <div className="mt-0.5">
                          <span className={`px-2 py-0.5 rounded text-[10px] font-bold border ${getRoleBadgeColor(u.role)}`}>
                            {u.role}
                          </span>
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-xs font-bold text-slate-900 truncate">{u.fullName}</p>
                          <p className="text-[11px] text-slate-500 truncate">{u.institutionName || 'KemenPANRB'}</p>
                        </div>
                      </button>
                    ))}
                  </div>
                  <div className="p-2 border-t border-slate-100 bg-slate-50 rounded-b-xl flex items-center justify-between">
                    <Link
                      href="/login"
                      onClick={() => setIsRoleDropdownOpen(false)}
                      className="text-xs font-semibold text-primary-800 hover:underline flex items-center gap-1"
                    >
                      <LogIn className="w-3.5 h-3.5" />
                      Halaman Masuk Resmi
                    </Link>
                  </div>
                </div>
              )}
            </>
          )}
        </div>

        {/* Notification Bell */}
        <button
          onClick={openDrawer}
          className="relative p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700 text-slate-200 border border-slate-600 transition-colors"
          title="Pusat Notifikasi Realtime"
        >
          <Bell className="w-4 h-4" />
          {unreadCount > 0 && (
            <span className="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white rounded-full text-[10px] font-bold flex items-center justify-center shadow-xs animate-pulse">
              {unreadCount}
            </span>
          )}
        </button>

        {/* User Profile / Auth Action */}
        <div className="hidden sm:flex items-center gap-2.5 pl-2 border-l border-slate-700">
          <Link href="/login" className="flex items-center gap-2 group">
            <div className="w-8 h-8 rounded-full bg-primary-800 border border-gold-400/40 flex items-center justify-center text-xs font-bold text-gold-300 group-hover:scale-105 transition-transform">
              {currentUser.fullName.charAt(0)}
            </div>
            <div className="text-left hidden lg:block">
              <p className="text-xs font-semibold text-white leading-tight truncate max-w-[140px]">
                {currentUser.fullName}
              </p>
              <p className="text-[10px] text-slate-400 truncate max-w-[140px]">
                {currentUser.institutionName?.replace('Kementerian ', '') || 'KemenPANRB'}
              </p>
            </div>
          </Link>

          {isApiMode && isAuthenticated && (
            <button
              onClick={() => logout()}
              className="p-1.5 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded transition"
              title="Keluar (Logout)"
            >
              <LogOut className="w-3.5 h-3.5" />
            </button>
          )}
        </div>
      </div>
    </header>
  );
}
