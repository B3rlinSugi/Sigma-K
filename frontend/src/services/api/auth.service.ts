import { 
  User, 
  UserRole, 
  AuthLoginCredentials, 
  AuthLoginResponseDto, 
  AuthProfileResponseDto,
  ProductionRole
} from '@/types/auth';
import { MOCK_USERS } from '@/data/mock/users';
import { envConfig } from '@/config/env.config';
import { httpClient } from '@/services/http/client';
import { authTokenProvider } from '@/services/http/token-provider';

const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms));

/**
 * Normalizes backend role string to UserRole domain type
 */
export function normalizeRoleCode(role: string): UserRole {
  const upper = (role || '').toUpperCase();
  if (upper === 'ADMIN') return 'ADMIN';
  if (upper === 'VERIFIER' || upper === 'VERIFIKATOR') return 'VERIFIER';
  if (upper === 'SUPER_ADMIN' || upper === 'SESDEP') return 'SUPER_ADMIN';
  return 'USER';
}

/**
 * Maps Backend User DTO to Domain User
 */
export function mapAuthProfileToDomain(dto: AuthProfileResponseDto | AuthLoginResponseDto['user']): User {
  const role = normalizeRoleCode(dto.role);
  const profileDto = dto as AuthProfileResponseDto;

  return {
    id: String(dto.id),
    username: dto.username,
    fullName: dto.fullName,
    nip: dto.nip || '-',
    email: dto.email,
    role,
    institutionId: dto.homeInstitution ? String(dto.homeInstitution.id) : undefined,
    institutionName: dto.homeInstitution?.name,
    institutionCode: dto.homeInstitution?.code,
    permissions: profileDto.permissions || [],
    activeScopes: profileDto.activeScopes || [],
    activeGrants: profileDto.activeGrants || [],
  };
}

/**
 * Mock Implementation for isolated UI demo
 */
class MockAuthService {
  static async getUsers(): Promise<User[]> {
    await delay(50);
    return [...MOCK_USERS];
  }

  static async getUserById(id: string): Promise<User | null> {
    await delay(50);
    return MOCK_USERS.find((u) => u.id === id) || null;
  }

  static async getUserByRole(role: UserRole): Promise<User | null> {
    await delay(50);
    return MOCK_USERS.find((u) => u.role === role) || null;
  }

  static async login(credentials: AuthLoginCredentials): Promise<User> {
    await delay(100);
    const user = MOCK_USERS.find(
      (u) => u.username.toLowerCase() === credentials.username.toLowerCase()
    ) || MOCK_USERS[0];

    authTokenProvider.setAccessToken(`mock-token-${user.id}`);
    return { ...user };
  }

  static async logout(): Promise<void> {
    await delay(50);
    authTokenProvider.clearAccessToken();
  }

  static async getProfile(): Promise<User | null> {
    await delay(50);
    return MOCK_USERS[0] || null;
  }
}

/**
 * Live API Implementation connected to CodeIgniter 4 backend
 */
class ApiAuthService {
  static async login(credentials: AuthLoginCredentials): Promise<User> {
    const res = await httpClient.post<AuthLoginResponseDto>('auth/login', credentials, {
      skipAuth: true,
    });

    if (res && res.accessToken) {
      authTokenProvider.setAccessToken(res.accessToken);
      return mapAuthProfileToDomain(res.user);
    }

    throw new Error('Invalid login response from server.');
  }

  static async getProfile(): Promise<User | null> {
    const token = authTokenProvider.getAccessToken();
    if (!token) return null;

    try {
      const dto = await httpClient.get<AuthProfileResponseDto>('auth/me');
      return dto ? mapAuthProfileToDomain(dto) : null;
    } catch (err) {
      // If token expired or unauthorized, clear token
      authTokenProvider.clearAccessToken();
      throw err;
    }
  }

  static async logout(): Promise<void> {
    try {
      await httpClient.post('auth/logout');
    } catch {
      // Ignore network error on logout
    } finally {
      authTokenProvider.clearAccessToken();
    }
  }

  static async getUsers(): Promise<User[]> {
    return MockAuthService.getUsers();
  }

  static async getUserById(id: string): Promise<User | null> {
    return MockAuthService.getUserById(id);
  }

  static async getUserByRole(role: UserRole): Promise<User | null> {
    return MockAuthService.getUserByRole(role);
  }
}

/**
 * Unified Facade dispatching based on environment mode
 */
export class AuthService {
  static async login(credentials: AuthLoginCredentials): Promise<User> {
    if (envConfig.isApiMode) {
      return ApiAuthService.login(credentials);
    }
    return MockAuthService.login(credentials);
  }

  static async getProfile(): Promise<User | null> {
    if (envConfig.isApiMode) {
      try {
        return await ApiAuthService.getProfile();
      } catch (err) {
        console.warn('API error in getProfile, falling back to mock:', err);
        return MockAuthService.getProfile();
      }
    }
    return MockAuthService.getProfile();
  }

  static async logout(): Promise<void> {
    if (envConfig.isApiMode) {
      return ApiAuthService.logout();
    }
    return MockAuthService.logout();
  }

  static async getUsers(): Promise<User[]> {
    return MockAuthService.getUsers();
  }

  static async getUserById(id: string): Promise<User | null> {
    return MockAuthService.getUserById(id);
  }

  static async getUserByRole(role: UserRole): Promise<User | null> {
    return MockAuthService.getUserByRole(role);
  }
}
