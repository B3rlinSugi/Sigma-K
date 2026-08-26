'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { 
  ShieldCheck, 
  Lock, 
  User, 
  Sparkles, 
  AlertCircle, 
  ArrowRight, 
  CheckCircle2, 
  KeyRound, 
  Server,
  Building2
} from 'lucide-react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { useAuth } from '@/context/RoleContext';
import { AppError } from '@/services/http/errors';

export default function LoginPage() {
  const router = useRouter();
  const { login, isApiMode, currentUser } = useAuth();

  const [username, setUsername] = useState('test_user_a');
  const [password, setPassword] = useState('password');
  const [isLoading, setIsLoading] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username.trim() || !password.trim()) {
      setErrorMessage('Username dan password wajib diisi.');
      return;
    }

    setIsLoading(true);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      const user = await login({ username: username.trim(), password });
      setSuccessMessage(`Login berhasil sebagai ${user.fullName} (${user.role}). Mengarahkan...`);
      setTimeout(() => {
        router.push('/');
      }, 1000);
    } catch (err) {
      if (err instanceof AppError) {
        setErrorMessage(err.message || 'Kombinasi username atau password salah.');
      } else {
        setErrorMessage('Gagal terhubung ke server autentikasi.');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleQuickFill = (u: string, p: string = 'password') => {
    setUsername(u);
    setPassword(p);
    setErrorMessage(null);
  };

  return (
    <div className="min-h-[80vh] flex items-center justify-center p-4 animate-in fade-in duration-200">
      <div className="w-full max-w-md space-y-6">
        {/* TOP BRANDING */}
        <div className="text-center space-y-2">
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-900/10 text-primary-900 border border-primary-800/20 text-xs font-semibold">
            <Sparkles className="w-3.5 h-3.5 text-gold-600" />
            Sistem Informasi Kelembagaan Nasional — SIGMA-K
          </div>
          <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 font-heading">
            Masuk ke Akun Anda
          </h1>
          <p className="text-xs text-slate-500">
            Autentikasi terpusat KemenPANRB berbasis Zero-Trust & JSON Web Token.
          </p>
        </div>

        {/* LOGIN CARD */}
        <Card className="shadow-xl border-slate-200">
          <CardHeader className="border-b border-slate-100 pb-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-5 h-5 text-primary-800" />
                <CardTitle className="text-sm font-bold text-slate-900">
                  Formulir Masuk
                </CardTitle>
              </div>
              <Badge variant={isApiMode ? 'success' : 'warning'} size="sm">
                <Server className="w-3 h-3 mr-1" />
                {isApiMode ? 'Live API Mode' : 'Demo Mock Mode'}
              </Badge>
            </div>
          </CardHeader>

          <CardContent className="p-6 space-y-4">
            {/* ALERT MESSAGES */}
            {errorMessage && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2.5 text-xs text-red-800 animate-in fade-in">
                <AlertCircle className="w-4 h-4 text-red-600 shrink-0 mt-0.5" />
                <span>{errorMessage}</span>
              </div>
            )}

            {successMessage && (
              <div className="p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-start gap-2.5 text-xs text-emerald-800 animate-in fade-in">
                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                <span>{successMessage}</span>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-1.5">
                <label className="text-xs font-semibold text-slate-700">Username / NIP</label>
                <div className="relative">
                  <User className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                  <input
                    type="text"
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    placeholder="Masukkan username akun..."
                    required
                    className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
                  />
                </div>
              </div>

              <div className="space-y-1.5">
                <label className="text-xs font-semibold text-slate-700">Kata Sandi</label>
                <div className="relative">
                  <Lock className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                  <input
                    type="password"
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    placeholder="••••••••"
                    required
                    className="w-full h-9 pl-9 pr-3 text-xs bg-slate-50 border border-slate-200 rounded-md focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary-800"
                  />
                </div>
              </div>

              <Button
                type="submit"
                variant="primary"
                size="md"
                className="w-full justify-center"
                isLoading={isLoading}
                rightIcon={<ArrowRight className="w-4 h-4" />}
              >
                Masuk Sistem
              </Button>
            </form>

            {/* QUICK-FILL TEST SHORTCUTS */}
            <div className="pt-4 border-t border-slate-100 space-y-2">
              <span className="text-[11px] font-semibold text-slate-500 uppercase tracking-wider block">
                Akun Uji Coba Cepat (Test Sandbox):
              </span>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => handleQuickFill('test_user_a')}
                  className="p-2 text-left rounded border border-slate-200 hover:border-primary-600 hover:bg-primary-50 transition text-[11px]"
                >
                  <span className="font-bold text-slate-800 block">Operator Instansi</span>
                  <span className="text-slate-500 font-mono">test_user_a</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleQuickFill('test_admin')}
                  className="p-2 text-left rounded border border-slate-200 hover:border-primary-600 hover:bg-primary-50 transition text-[11px]"
                >
                  <span className="font-bold text-slate-800 block">Admin Gate 1</span>
                  <span className="text-slate-500 font-mono">test_admin</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleQuickFill('test_verifier')}
                  className="p-2 text-left rounded border border-slate-200 hover:border-primary-600 hover:bg-primary-50 transition text-[11px]"
                >
                  <span className="font-bold text-slate-800 block">Verifikator Analis</span>
                  <span className="text-slate-500 font-mono">test_verifier</span>
                </button>

                <button
                  type="button"
                  onClick={() => handleQuickFill('test_super_admin')}
                  className="p-2 text-left rounded border border-slate-200 hover:border-primary-600 hover:bg-primary-50 transition text-[11px]"
                >
                  <span className="font-bold text-slate-800 block">Super Administrator</span>
                  <span className="text-slate-500 font-mono">test_super_admin</span>
                </button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
