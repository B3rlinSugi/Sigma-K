/**
 * Phase 14B Authentication & JWT Integration Unit Tests
 */

import { normalizeRoleCode, mapAuthProfileToDomain, AuthService } from '../../api/auth.service';
import { authTokenProvider } from '../token-provider';
import { AuthProfileResponseDto, AuthLoginResponseDto } from '@/types/auth';

export function runPhase14BAuthTests(): boolean {
  console.log('--- Starting Phase 14B Authentication Tests ---');
  let passed = 0;
  let total = 0;

  function assert(condition: boolean, testName: string) {
    total++;
    if (condition) {
      passed++;
      console.log(`[PASS] ${testName}`);
    } else {
      console.error(`[FAIL] ${testName}`);
      throw new Error(`Test failed: ${testName}`);
    }
  }

  // 1. Role Normalization Tests
  assert(normalizeRoleCode('ADMIN') === 'ADMIN', 'normalizeRoleCode: ADMIN maps to ADMIN');
  assert(normalizeRoleCode('VERIFIER') === 'VERIFIER', 'normalizeRoleCode: VERIFIER maps to VERIFIER');
  assert(normalizeRoleCode('VERIFIKATOR') === 'VERIFIER', 'normalizeRoleCode: VERIFIKATOR alias maps to VERIFIER');
  assert(normalizeRoleCode('SUPER_ADMIN') === 'SUPER_ADMIN', 'normalizeRoleCode: SUPER_ADMIN maps to SUPER_ADMIN');
  assert(normalizeRoleCode('SESDEP') === 'SUPER_ADMIN', 'normalizeRoleCode: SESDEP persona maps to SUPER_ADMIN');
  assert(normalizeRoleCode('USER') === 'USER', 'normalizeRoleCode: USER maps to USER');

  // 2. Auth Profile DTO Mapping Tests
  const loginUserDto: AuthLoginResponseDto['user'] = {
    id: 42,
    username: 'test_user_a',
    email: 'user.a@test.go.id',
    fullName: 'Budi Santoso',
    nip: '198501012010011001',
    role: 'USER',
    homeInstitution: {
      id: 1,
      code: 'TEST-INST-A',
      name: 'Kementerian Test A',
    },
  };

  const domainUser = mapAuthProfileToDomain(loginUserDto);
  assert(domainUser.id === '42', 'ProfileMapper: Converts numeric user id to string');
  assert(domainUser.username === 'test_user_a', 'ProfileMapper: Maps username');
  assert(domainUser.role === 'USER', 'ProfileMapper: Maps role');
  assert(domainUser.institutionId === '1', 'ProfileMapper: Maps homeInstitution id');
  assert(domainUser.institutionName === 'Kementerian Test A', 'ProfileMapper: Maps homeInstitution name');
  assert(domainUser.institutionCode === 'TEST-INST-A', 'ProfileMapper: Maps homeInstitution code');

  const fullProfileDto: AuthProfileResponseDto = {
    id: 99,
    username: 'test_verifier',
    email: 'verifier@test.go.id',
    fullName: 'Ahmad Fauzi, S.Kom.',
    nip: '198701012011011002',
    role: 'VERIFIER',
    homeInstitution: {
      id: 1,
      code: 'TEST-INST-A',
      name: 'Kementerian Test A',
    },
    permissions: ['verifier:review', 'submission:approve'],
    activeScopes: [1, 2],
    activeGrants: [],
  };

  const verifierUser = mapAuthProfileToDomain(fullProfileDto);
  assert(verifierUser.role === 'VERIFIER', 'ProfileMapper: Maps VERIFIER profile');
  assert(verifierUser.permissions?.includes('submission:approve') === true, 'ProfileMapper: Preserves permissions array');
  assert(verifierUser.activeScopes?.includes(2) === true, 'ProfileMapper: Preserves activeScopes array');

  // 3. Mock AuthService Login / Logout Cycle Tests
  authTokenProvider.clearAccessToken();
  assert(authTokenProvider.getAccessToken() === null, 'AuthToken: Initially cleared');

  console.log(`--- All ${passed}/${total} Phase 14B Authentication Tests Passed ---`);
  return true;
}
