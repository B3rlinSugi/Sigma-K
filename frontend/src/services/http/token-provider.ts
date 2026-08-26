/**
 * Authentication Token Provider Interface & Storage Implementation
 * Provides access token abstraction for HTTP Client without coupling to UI context.
 */

export interface AuthTokenProvider {
  getAccessToken(): string | null;
  setAccessToken(token: string | null): void;
  clearAccessToken(): void;
}

const TOKEN_STORAGE_KEY = 'eskld_access_token';

export class BrowserStorageTokenProvider implements AuthTokenProvider {
  private memoryToken: string | null = null;

  public getAccessToken(): string | null {
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        return window.localStorage.getItem(TOKEN_STORAGE_KEY) || this.memoryToken;
      } catch {
        return this.memoryToken;
      }
    }
    return this.memoryToken;
  }

  public setAccessToken(token: string | null): void {
    this.memoryToken = token;
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        if (token) {
          window.localStorage.setItem(TOKEN_STORAGE_KEY, token);
        } else {
          window.localStorage.removeItem(TOKEN_STORAGE_KEY);
        }
      } catch {
        // Fallback to memory
      }
    }
  }

  public clearAccessToken(): void {
    this.setAccessToken(null);
  }
}

export const authTokenProvider: AuthTokenProvider = new BrowserStorageTokenProvider();
