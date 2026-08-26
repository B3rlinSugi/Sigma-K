'use client';

import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { User, UserRole, AuthLoginCredentials } from '@/types/auth';
import { AuthService } from '@/services/api/auth.service';
import { authTokenProvider } from '@/services/http/token-provider';
import { envConfig } from '@/config/env.config';

export interface AuthContextType {
  currentUser: User;
  currentRole: UserRole;
  isAuthenticated: boolean;
  isLoading: boolean;
  isApiMode: boolean;
  availableUsers: User[];
  login: (credentials: AuthLoginCredentials) => Promise<User>;
  logout: () => Promise<void>;
  switchRole: (role: UserRole) => void;
  refreshProfile: () => Promise<void>;
}

// Backward-compatible alias
export type RoleContextType = AuthContextType;

const DEFAULT_USER: User = {
  id: '1',
  username: 'operator_pangan',
  fullName: 'Budi Santoso, S.AP.',
  nip: '198805122010121003',
  email: 'budi.santoso@pangan.go.id',
  role: 'USER',
  institutionId: '1',
  institutionName: 'Kementerian Koordinator Bidang Pangan',
  institutionCode: 'TEST-INST-A',
};

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [users, setUsers] = useState<User[]>([DEFAULT_USER]);
  const [currentUser, setCurrentUser] = useState<User>(DEFAULT_USER);
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(false);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  // Load active session or mock list on mount
  useEffect(() => {
    async function initAuth() {
      setIsLoading(true);
      try {
        const userList = await AuthService.getUsers();
        if (userList.length > 0) {
          setUsers(userList);
        }

        if (envConfig.isApiMode) {
          const token = authTokenProvider.getAccessToken();
          if (token) {
            try {
              const profile = await AuthService.getProfile();
              if (profile) {
                setCurrentUser(profile);
                setIsAuthenticated(true);
              }
            } catch (err) {
              console.warn('Session expired or invalid, clearing token:', err);
              setIsAuthenticated(false);
            }
          }
        } else {
          // Mock / Demo mode: restore selected persona
          const savedRole = localStorage.getItem('sigma_demo_role');
          if (savedRole) {
            const found = userList.find((u) => u.role === savedRole);
            if (found) setCurrentUser(found);
            else if (userList[0]) setCurrentUser(userList[0]);
          } else if (userList[0]) {
            setCurrentUser(userList[0]);
          }
          setIsAuthenticated(true);
        }
      } catch (err) {
        console.error('Failed to initialize auth:', err);
      } finally {
        setIsLoading(false);
      }
    }

    initAuth();
  }, []);

  const login = useCallback(async (credentials: AuthLoginCredentials): Promise<User> => {
    setIsLoading(true);
    try {
      const user = await AuthService.login(credentials);
      setCurrentUser(user);
      setIsAuthenticated(true);
      return user;
    } finally {
      setIsLoading(false);
    }
  }, []);

  const logout = useCallback(async (): Promise<void> => {
    setIsLoading(true);
    try {
      await AuthService.logout();
      setIsAuthenticated(false);
      if (users.length > 0) {
        setCurrentUser(users[0]);
      }
    } finally {
      setIsLoading(false);
    }
  }, [users]);

  /**
   * Persona switcher: Strictly disabled in API mode to preserve Zero-Trust authorization.
   */
  const switchRole = useCallback((role: UserRole) => {
    if (envConfig.isApiMode) {
      console.warn('Security Policy: Role switching is disabled in API mode. Authenticated backend role is authoritative.');
      return;
    }

    const found = users.find((u) => u.role === role);
    if (found) {
      setCurrentUser(found);
      localStorage.setItem('sigma_demo_role', role);
    }
  }, [users]);

  const refreshProfile = useCallback(async (): Promise<void> => {
    if (envConfig.isApiMode) {
      const profile = await AuthService.getProfile();
      if (profile) {
        setCurrentUser(profile);
      }
    }
  }, []);

  return (
    <AuthContext.Provider
      value={{
        currentUser,
        currentRole: currentUser.role,
        isAuthenticated,
        isLoading,
        isApiMode: envConfig.isApiMode,
        availableUsers: users,
        login,
        logout,
        switchRole,
        refreshProfile,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}

// Backward-compatible alias
export const RoleProvider = AuthProvider;

export function useAuth(): AuthContextType {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

// Backward-compatible alias
export const useRole = useAuth;
