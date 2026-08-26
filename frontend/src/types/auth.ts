/**
 * Authentication & Identity Types
 * Supports both Production RBAC Roles and Prototype Persona Aliases.
 */

// Production Roles from Backend RBAC
export type ProductionRole = 'USER' | 'ADMIN' | 'VERIFIER' | 'SUPER_ADMIN';

// Legacy / Prototype Aliases
export type PrototypeRoleAlias = 'VERIFIKATOR' | 'SESDEP';

export type UserRole = ProductionRole | PrototypeRoleAlias;

// Alias for domain consistency
export type Role = UserRole;

export interface HomeInstitutionInfo {
  id: number;
  code: string;
  name: string;
}

export interface User {
  id: string;
  username: string;
  fullName: string;
  nip: string;
  email: string;
  role: UserRole;
  institutionId?: string;
  institutionName?: string;
  institutionCode?: string;
  avatarUrl?: string;
  permissions?: string[];
  activeScopes?: number[];
  activeGrants?: any[];
}

export interface UserScope {
  userId: string;
  institutionId: string;
  institutionName: string;
  permissions: string[];
}

export interface AuthLoginCredentials {
  username: string;
  password: string;
}

export interface AuthUserDto {
  id: number;
  username: string;
  email: string;
  fullName: string;
  nip: string | null;
  role: string;
  homeInstitution?: HomeInstitutionInfo | null;
}

export interface AuthLoginResponseDto {
  accessToken: string;
  tokenType: string;
  expiresIn: number;
  user: AuthUserDto;
}

export interface AuthProfileResponseDto {
  id: number;
  username: string;
  email: string;
  fullName: string;
  nip: string | null;
  role: string;
  homeInstitution?: HomeInstitutionInfo | null;
  permissions: string[];
  activeScopes: number[];
  activeGrants: any[];
}
