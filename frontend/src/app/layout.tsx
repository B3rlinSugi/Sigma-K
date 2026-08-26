import type { Metadata } from 'next';
import { Inter, Outfit } from 'next/font/google';
import './globals.css';
import { RoleProvider } from '@/context/RoleContext';
import { NotificationProvider } from '@/context/NotificationContext';

const inter = Inter({
  subsets: ['latin'],
  variable: '--font-inter',
  display: 'swap',
});

const outfit = Outfit({
  subsets: ['latin'],
  variable: '--font-outfit',
  display: 'swap',
});

export const metadata: Metadata = {
  title: 'SIGMA-K — Sistem Pengelolaan Kelembagaan Kementerian PANRB',
  description: 'Sistem Pengelolaan Data Kementerian/Lembaga/Pemerintah Daerah dan Struktur Kelembagaan — Modernisasi E-SKLD KemenPANRB.',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="id" className={`${inter.variable} ${outfit.variable}`}>
      <body className={inter.className}>
        <RoleProvider>
          <NotificationProvider>
            {children}
          </NotificationProvider>
        </RoleProvider>
      </body>
    </html>
  );
}
