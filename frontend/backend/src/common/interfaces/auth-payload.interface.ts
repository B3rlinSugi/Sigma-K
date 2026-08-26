export type UserRole = 'USER' | 'VERIFIKATOR' | 'ADMIN' | 'SESDEP';

export interface JwtPayload {
  sub: string; // User ID
  username: string;
  email: string;
  fullName: string;
  role: UserRole;
  institutionId?: string;
  permissions?: string[];
  iat?: number;
  exp?: number;
}

export interface AuthenticatedUser {
  id: string;
  username: string;
  email: string;
  fullName: string;
  role: UserRole;
  institutionId?: string;
  institutionName?: string;
  permissions: string[];
}
