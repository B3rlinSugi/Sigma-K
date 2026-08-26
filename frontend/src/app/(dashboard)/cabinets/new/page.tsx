'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { AlertCircle, CheckCircle2, Landmark, ArrowLeft } from 'lucide-react';

export default function NewCabinetPage() {
  const router = useRouter();
  const [formData, setFormData] = useState({
    name: '',
    presidentName: '',
    vicePresidentName: '',
    startDate: '',
    endDate: '',
    legalDecreeNumber: '',
    description: '',
    isActive: false,
  });

  const [error, setError] = useState('');
  const [isSuccess, setIsSuccess] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    // Validasi form
    if (!formData.name || !formData.presidentName || !formData.startDate) {
      setError('Mohon lengkapi seluruh field wajib bertanda bintang (*).');
      return;
    }

    if (formData.endDate && formData.endDate < formData.startDate) {
      setError('Tanggal berakhir tidak boleh lebih awal dari tanggal mulai pelantikan.');
      return;
    }

    setIsSubmitting(true);
    setTimeout(() => {
      setIsSubmitting(false);
      setIsSuccess(true);
      setTimeout(() => {
        router.push('/cabinets');
      }, 1500);
    }, 600);
  };

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <PageHeader
        title="Tambah Kabinet Pemerintahan Baru"
        subtitle="Daftarkan era kabinet kepresidenan baru berdasarkan Keputusan Presiden Republik Indonesia."
        breadcrumbs={[
          { label: 'Master Data', href: '/cabinets' },
          { label: 'Kabinet', href: '/cabinets' },
          { label: 'Tambah Baru' },
        ]}
      />

      {isSuccess && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-900 flex items-center gap-3 animate-in fade-in">
          <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
          <p className="text-xs font-semibold">
            Data kabinet berhasil disimpan ke sistem. Mengalihkan ke daftar kabinet...
          </p>
        </div>
      )}

      {error && (
        <div className="p-4 bg-red-50 border border-red-200 rounded-xl text-red-900 flex items-center gap-3 animate-in fade-in">
          <AlertCircle className="w-5 h-5 text-red-600 shrink-0" />
          <p className="text-xs font-semibold">{error}</p>
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <Card>
          <CardHeader>
            <CardTitle className="text-base font-bold flex items-center gap-2">
              <Landmark className="w-4 h-4 text-primary-800" />
              Identitas dan Dasar Hukum Kabinet
            </CardTitle>
          </CardHeader>

          <CardContent className="space-y-4">
            <Input
              label="Nama Resmi Kabinet"
              required
              placeholder="Contoh: Kabinet Merah Putih"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            />

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Presiden Republik Indonesia"
                required
                placeholder="Nama Presiden"
                value={formData.presidentName}
                onChange={(e) => setFormData({ ...formData, presidentName: e.target.value })}
              />

              <Input
                label="Wakil Presiden Republik Indonesia"
                placeholder="Nama Wakil Presiden"
                value={formData.vicePresidentName}
                onChange={(e) => setFormData({ ...formData, vicePresidentName: e.target.value })}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Input
                label="Tanggal Awal Masa Jabatan (Pelantikan)"
                type="date"
                required
                value={formData.startDate}
                onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
              />

              <Input
                label="Tanggal Akhir Masa Jabatan"
                type="date"
                helperText="Biarkan kosong jika kabinet masih aktif berjalan."
                value={formData.endDate}
                onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
              />
            </div>

            <Input
              label="Nomor Keppres Pembentukan"
              placeholder="Contoh: Keppres No. 133/P Tahun 2024"
              value={formData.legalDecreeNumber}
              onChange={(e) => setFormData({ ...formData, legalDecreeNumber: e.target.value })}
            />

            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Deskripsi / Catatan Tambahan
              </label>
              <textarea
                rows={3}
                placeholder="Tuliskan keterangan mengenai pembentukan kabinet..."
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                className="w-full p-3 text-xs bg-white border border-slate-300 rounded-md focus:ring-2 focus:ring-primary-800 focus:outline-none"
              />
            </div>

            <div className="pt-2 flex items-center gap-2">
              <input
                type="checkbox"
                id="isActiveCabinet"
                checked={formData.isActive}
                onChange={(e) => setFormData({ ...formData, isActive: e.target.checked })}
                className="w-4 h-4 rounded text-primary-900 focus:ring-primary-800 border-slate-300"
              />
              <label htmlFor="isActiveCabinet" className="text-xs font-semibold text-slate-800 cursor-pointer">
                Tetapkan sebagai Kabinet Aktif Utama Pemerintahan (Default System)
              </label>
            </div>
          </CardContent>

          <CardFooter>
            <Link href="/cabinets">
              <Button variant="ghost" size="sm" leftIcon={<ArrowLeft className="w-3.5 h-3.5" />}>
                Batal
              </Button>
            </Link>
            <Button type="submit" variant="primary" size="sm" isLoading={isSubmitting}>
              Simpan Data Kabinet
            </Button>
          </CardFooter>
        </Card>
      </form>
    </div>
  );
}
