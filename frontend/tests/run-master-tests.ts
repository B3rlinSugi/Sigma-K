import { runPhase14AFoundationTests } from '../src/services/http/__tests__/foundation.test';
import { runPhase14BAuthTests } from '../src/services/http/__tests__/auth.test';
import { runPhase14BSecurityTests } from '../src/services/http/__tests__/auth-security.test';
import { runPhase14CMasterDataTests } from '../src/services/http/__tests__/master-data.test';

try {
  const fOk = runPhase14AFoundationTests();
  const aOk = runPhase14BAuthTests();
  const sOk = runPhase14BSecurityTests();
  const mOk = runPhase14CMasterDataTests();

  if (fOk && aOk && sOk && mOk) {
    console.log('\n✅ ALL PHASE 14A, 14B & 14C TEST SUITES: 100% PASS');
    process.exit(0);
  } else {
    console.error('\n❌ TEST SUITE FAILED');
    process.exit(1);
  }
} catch (error) {
  console.error('\n❌ Error executing master data tests:', error);
  process.exit(1);
}
