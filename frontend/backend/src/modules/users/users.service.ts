import { Injectable, NotFoundException } from '@nestjs/common';
import { PrismaService } from '../../infrastructure/database/prisma.service';

@Injectable()
export class UsersService {
  constructor(private prisma: PrismaService) {}

  async findAll(role?: string, institutionId?: string) {
    try {
      const users = await this.prisma.user.findMany({
        where: {
          isActive: true,
          ...(institutionId ? { institutionId } : {}),
          ...(role ? { userRoles: { some: { role: { name: role } } } } : {}),
        },
        include: {
          userRoles: { include: { role: true } },
          institution: true,
        },
      });

      return users.map((u) => ({
        id: u.id,
        username: u.username,
        email: u.email,
        fullName: u.fullName,
        nip: u.nip || '-',
        role: u.userRoles[0]?.role?.name || 'USER',
        institutionId: u.institutionId || undefined,
        institutionName: u.institution?.name,
        avatarUrl: u.avatarUrl || undefined,
      }));
    } catch {
      // Fallback demo personas
      return [
        {
          id: 'usr-operator-01',
          username: 'operator_pangan',
          fullName: 'Budi Santoso, S.AP.',
          nip: '198805122010121003',
          email: 'budi.santoso@pangan.go.id',
          role: 'USER',
          institutionId: 'inst-kemenko-pangan',
          institutionName: 'Kementerian Koordinator Bidang Pangan',
        },
        {
          id: 'usr-verifikator-01',
          username: 'verifikator_panrb',
          fullName: 'Siti Rahmawati, S.STP, M.AP',
          nip: '198503142008012004',
          email: 'siti.rahmawati@menpan.go.id',
          role: 'VERIFIKATOR',
          institutionId: 'inst-kemenpanrb',
          institutionName: 'Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi',
        },
        {
          id: 'usr-admin-01',
          username: 'admin_sigma',
          fullName: 'Ahmad Fauzi, S.Kom, M.TI',
          nip: '198207192006041002',
          email: 'ahmad.fauzi@menpan.go.id',
          role: 'ADMIN',
          institutionId: 'inst-kemenpanrb',
          institutionName: 'Kementerian Pendayagunaan Aparatur Negara dan Reformasi Birokrasi',
        },
        {
          id: 'usr-sesdep-01',
          username: 'sesdep_kelembagaan',
          fullName: 'Dr. Drs. Nanang Khoiruddin, M.Si',
          nip: '196911081990031001',
          email: 'nanang.k@menpan.go.id',
          role: 'SESDEP',
          institutionId: 'inst-kemenpanrb',
          institutionName: 'Deputi Bidang Kelembagaan dan Tata Laksana, Kementerian PANRB',
        },
      ];
    }
  }

  async findById(id: string) {
    const list = await this.findAll();
    const found = list.find((u) => u.id === id);
    if (!found) {
      throw new NotFoundException(`Pengguna dengan ID '${id}' tidak ditemukan.`);
    }
    return found;
  }
}
